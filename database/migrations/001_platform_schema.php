<?php
/**
 * Plataforma — migração 001: salas, jogadores, partidas, pontuação, histórico e as tabelas
 * genéricas de rodadas (respostas, votos, papéis secretos) usadas pelos jogos.
 * Idempotente: pode ser executada novamente sem apagar dados.
 */
defined('APP_ROOT') || exit;

use App\Core\Database;

return [
    'name' => 'platform_schema',
    'up' => static function (Database $db): void {
        $opts = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $db->exec("CREATE TABLE IF NOT EXISTS app_meta (
            meta_key VARCHAR(64) NOT NULL PRIMARY KEY,
            meta_value VARCHAR(255) NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $opts");

        // Horários são inteiros Unix (UTC). 'phase' e 'settings' (JSON) pertencem ao jogo da sala.
        $db->exec("CREATE TABLE IF NOT EXISTS rooms (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            code CHAR(6) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            name VARCHAR(60) NULL,
            game VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            password_hash VARCHAR(255) NULL,
            host_player_id INT UNSIGNED NULL,
            status ENUM('lobby','playing','finished') NOT NULL DEFAULT 'lobby',
            phase VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NULL,
            phase_ends_at INT UNSIGNED NULL,
            session_id INT UNSIGNED NULL,
            round_number INT UNSIGNED NOT NULL DEFAULT 0,
            current_round_id INT UNSIGNED NULL,
            min_players TINYINT UNSIGNED NOT NULL,
            max_players TINYINT UNSIGNED NOT NULL,
            settings MEDIUMTEXT NOT NULL,
            state_version INT UNSIGNED NOT NULL DEFAULT 1,
            notice VARCHAR(255) NULL,
            end_reason VARCHAR(32) NULL,
            sweep_at INT UNSIGNED NOT NULL DEFAULT 0,
            created_at INT UNSIGNED NOT NULL,
            last_activity INT UNSIGNED NOT NULL,
            finished_at INT UNSIGNED NULL,
            UNIQUE KEY uq_rooms_code (code),
            KEY idx_rooms_game (game),
            KEY idx_rooms_status_activity (status, last_activity),
            KEY idx_rooms_finished (status, finished_at)
        ) $opts");

        $db->exec("CREATE TABLE IF NOT EXISTS players (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            room_id INT UNSIGNED NOT NULL,
            nickname VARCHAR(40) NOT NULL,
            token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            identity_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            status ENUM('active','left','abandoned') NOT NULL DEFAULT 'active',
            connection ENUM('online','offline') NOT NULL DEFAULT 'online',
            joined_at INT UNSIGNED NOT NULL,
            last_seen INT UNSIGNED NOT NULL,
            left_at INT UNSIGNED NULL,
            UNIQUE KEY uq_players_token (token_hash),
            UNIQUE KEY uq_players_room_identity (room_id, identity_hash),
            KEY idx_players_room_status (room_id, status, last_seen),
            CONSTRAINT fk_players_room FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE CASCADE
        ) $opts");

        // Uma partida = um jogo jogado do início ao fim dentro da sala (a sala pode ter várias).
        $db->exec("CREATE TABLE IF NOT EXISTS game_sessions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            room_id INT UNSIGNED NOT NULL,
            number INT UNSIGNED NOT NULL,
            game VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            settings MEDIUMTEXT NOT NULL,
            status ENUM('active','finished') NOT NULL DEFAULT 'active',
            end_reason VARCHAR(32) NULL,
            started_at INT UNSIGNED NOT NULL,
            ended_at INT UNSIGNED NULL,
            UNIQUE KEY uq_game_sessions_room_number (room_id, number),
            CONSTRAINT fk_game_sessions_room FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE CASCADE
        ) $opts");

        // Pontos concedidos. ref_key único por jogador e partida: o mesmo ponto nunca é contado duas vezes.
        $db->exec("CREATE TABLE IF NOT EXISTS score_entries (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            session_id INT UNSIGNED NOT NULL,
            player_id INT UNSIGNED NOT NULL,
            round_number INT UNSIGNED NOT NULL,
            points INT NOT NULL,
            reason VARCHAR(120) NOT NULL,
            ref_key VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            created_at INT UNSIGNED NOT NULL,
            UNIQUE KEY uq_score_entries_ref (session_id, player_id, ref_key),
            KEY idx_score_entries_round (session_id, round_number),
            CONSTRAINT fk_score_entries_session FOREIGN KEY (session_id) REFERENCES game_sessions (id) ON DELETE CASCADE,
            CONSTRAINT fk_score_entries_player FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE CASCADE
        ) $opts");

        // Histórico público da partida (resumos já revelados). Nunca guarde segredos aqui.
        $db->exec("CREATE TABLE IF NOT EXISTS game_events (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            session_id INT UNSIGNED NOT NULL,
            round_number INT UNSIGNED NOT NULL,
            type VARCHAR(40) NOT NULL,
            ref_key VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            payload MEDIUMTEXT NOT NULL,
            created_at INT UNSIGNED NOT NULL,
            UNIQUE KEY uq_game_events_ref (session_id, ref_key),
            KEY idx_game_events_session (session_id, id),
            CONSTRAINT fk_game_events_session FOREIGN KEY (session_id) REFERENCES game_sessions (id) ON DELETE CASCADE
        ) $opts");

        // Rodadas genéricas. public_data pode ir ao navegador; secret_data nunca sai do servidor.
        $db->exec("CREATE TABLE IF NOT EXISTS game_rounds (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            session_id INT UNSIGNED NOT NULL,
            room_id INT UNSIGNED NOT NULL,
            number INT UNSIGNED NOT NULL,
            phase VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            phase_started_at INT UNSIGNED NOT NULL,
            phase_ends_at INT UNSIGNED NULL,
            prompt_key VARCHAR(120) NULL,
            public_data MEDIUMTEXT NOT NULL,
            secret_data MEDIUMTEXT NOT NULL,
            created_at INT UNSIGNED NOT NULL,
            ended_at INT UNSIGNED NULL,
            UNIQUE KEY uq_game_rounds_session_number (session_id, number),
            KEY idx_game_rounds_prompt (session_id, prompt_key),
            KEY idx_game_rounds_room (room_id),
            CONSTRAINT fk_game_rounds_session FOREIGN KEY (session_id) REFERENCES game_sessions (id) ON DELETE CASCADE,
            CONSTRAINT fk_game_rounds_room FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE CASCADE
        ) $opts");

        // Quem participa da rodada, com papel e informação secreta individual (ex.: palavra do Infiltrado).
        $db->exec("CREATE TABLE IF NOT EXISTS game_round_players (
            round_id INT UNSIGNED NOT NULL,
            player_id INT UNSIGNED NOT NULL,
            role VARCHAR(30) NULL,
            secret MEDIUMTEXT NULL,
            PRIMARY KEY (round_id, player_id),
            KEY idx_game_round_players_player (player_id),
            CONSTRAINT fk_grp_round FOREIGN KEY (round_id) REFERENCES game_rounds (id) ON DELETE CASCADE,
            CONSTRAINT fk_grp_player FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE CASCADE
        ) $opts");

        // Envios dos jogadores (resposta, dica, afirmação...). UNIQUE: um envio por tipo e rodada.
        $db->exec("CREATE TABLE IF NOT EXISTS game_entries (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            round_id INT UNSIGNED NOT NULL,
            player_id INT UNSIGNED NOT NULL,
            kind VARCHAR(20) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            body VARCHAR(500) NOT NULL,
            meta TEXT NULL,
            public_code CHAR(6) CHARACTER SET ascii COLLATE ascii_bin NULL,
            position SMALLINT UNSIGNED NULL,
            created_at INT UNSIGNED NOT NULL,
            UNIQUE KEY uq_game_entries_player (round_id, player_id, kind),
            UNIQUE KEY uq_game_entries_code (round_id, public_code),
            KEY idx_game_entries_player (player_id),
            CONSTRAINT fk_game_entries_round FOREIGN KEY (round_id) REFERENCES game_rounds (id) ON DELETE CASCADE,
            CONSTRAINT fk_game_entries_player FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE CASCADE
        ) $opts");

        // Votos. UNIQUE (rodada, eleitor, cédula): um voto por votação, mesmo com requisições simultâneas.
        $db->exec("CREATE TABLE IF NOT EXISTS game_votes (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            round_id INT UNSIGNED NOT NULL,
            voter_id INT UNSIGNED NOT NULL,
            ballot VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            target VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            created_at INT UNSIGNED NOT NULL,
            UNIQUE KEY uq_game_votes_ballot (round_id, voter_id, ballot),
            KEY idx_game_votes_voter (voter_id),
            CONSTRAINT fk_game_votes_round FOREIGN KEY (round_id) REFERENCES game_rounds (id) ON DELETE CASCADE,
            CONSTRAINT fk_game_votes_voter FOREIGN KEY (voter_id) REFERENCES players (id) ON DELETE CASCADE
        ) $opts");

        $db->exec("CREATE TABLE IF NOT EXISTS rate_limits (
            bucket CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
            hits INT UNSIGNED NOT NULL DEFAULT 0,
            window_start INT UNSIGNED NOT NULL,
            KEY idx_rate_limits_window (window_start)
        ) $opts");

        $db->execute(
            'INSERT IGNORE INTO app_meta (meta_key, meta_value) VALUES (?, ?), (?, ?)',
            ['installed_at', gmdate('Y-m-d H:i:s'), 'app_secret', bin2hex(random_bytes(32))]
        );
    },
];
