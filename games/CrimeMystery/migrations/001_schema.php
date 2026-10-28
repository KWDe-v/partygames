<?php
/**
 * "Quem Cometeu o Crime?" — migração 001: pistas liberadas, interrogatórios e acusações.
 * Os casos (história, suspeitos, pistas, diálogos, solução) ficam em games/CrimeMystery/cases/*.php.
 */
defined('APP_ROOT') || exit;

use App\Core\Database;

return [
    'name' => 'crime_schema',
    'up' => static function (Database $db): void {
        $opts = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        // Pista liberada: player_id = 0 ⇒ pública; senão, só aquele jogador a vê.
        $db->exec("CREATE TABLE IF NOT EXISTS crime_unlocks (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            round_id INT UNSIGNED NOT NULL,
            clue_id VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            player_id INT UNSIGNED NOT NULL DEFAULT 0,
            source ENUM('start','time','role','search','interrogation') NOT NULL,
            found_by INT UNSIGNED NULL,
            created_at INT UNSIGNED NOT NULL,
            UNIQUE KEY uq_crime_unlocks (round_id, clue_id, player_id),
            CONSTRAINT fk_crime_unlocks_round FOREIGN KEY (round_id) REFERENCES game_rounds (id) ON DELETE CASCADE
        ) $opts");

        // Ações de investigação: cada pergunta/local uma única vez por jogador.
        $db->exec("CREATE TABLE IF NOT EXISTS crime_actions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            round_id INT UNSIGNED NOT NULL,
            player_id INT UNSIGNED NOT NULL,
            kind ENUM('question','search') NOT NULL,
            target VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            item VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            created_at INT UNSIGNED NOT NULL,
            UNIQUE KEY uq_crime_actions (round_id, player_id, kind, item),
            CONSTRAINT fk_crime_actions_round FOREIGN KEY (round_id) REFERENCES game_rounds (id) ON DELETE CASCADE,
            CONSTRAINT fk_crime_actions_player FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE CASCADE
        ) $opts");

        // Acusação final: uma por jogador, secreta até a revelação.
        $db->exec("CREATE TABLE IF NOT EXISTS crime_accusations (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            round_id INT UNSIGNED NOT NULL,
            player_id INT UNSIGNED NOT NULL,
            suspect_id VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            motive_id VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            created_at INT UNSIGNED NOT NULL,
            UNIQUE KEY uq_crime_accusations (round_id, player_id),
            CONSTRAINT fk_crime_accusations_round FOREIGN KEY (round_id) REFERENCES game_rounds (id) ON DELETE CASCADE,
            CONSTRAINT fk_crime_accusations_player FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE CASCADE
        ) $opts");
    },
];
