<?php
/**
 * "O que você faria se..." — migração 001: rodadas, perguntas, respostas e resultados.
 * Idempotente: pode ser executada novamente sem apagar dados.
 */
defined('APP_ROOT') || exit;

use App\Core\Database;

return [
    'name' => 'oqvf_schema',
    'up' => static function (Database $db): void {
        $opts = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $db->exec("CREATE TABLE IF NOT EXISTS oqvf_rounds (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            room_id INT UNSIGNED NOT NULL,
            session_id INT UNSIGNED NOT NULL,
            number INT UNSIGNED NOT NULL,
            status ENUM('question_writing','answer_writing','results','completed','cancelled') NOT NULL,
            phase_started_at INT UNSIGNED NOT NULL,
            phase_ends_at INT UNSIGNED NULL,
            result_position INT UNSIGNED NOT NULL DEFAULT 0,
            result_count INT UNSIGNED NOT NULL DEFAULT 0,
            note VARCHAR(255) NULL,
            created_at INT UNSIGNED NOT NULL,
            ended_at INT UNSIGNED NULL,
            UNIQUE KEY uq_oqvf_rounds_session_number (session_id, number),
            KEY idx_oqvf_rounds_room (room_id),
            CONSTRAINT fk_oqvf_rounds_room FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE CASCADE,
            CONSTRAINT fk_oqvf_rounds_session FOREIGN KEY (session_id) REFERENCES game_sessions (id) ON DELETE CASCADE
        ) $opts");

        // Quem participa de cada rodada e em que ponto está (distingue "não enviou" de "desconectado").
        $db->exec("CREATE TABLE IF NOT EXISTS oqvf_participants (
            round_id INT UNSIGNED NOT NULL,
            player_id INT UNSIGNED NOT NULL,
            question_status ENUM('pending','submitted','missed') NOT NULL DEFAULT 'pending',
            answer_status ENUM('not_eligible','pending','submitted','missed') NOT NULL DEFAULT 'not_eligible',
            PRIMARY KEY (round_id, player_id),
            KEY idx_oqvf_participants_player (player_id),
            CONSTRAINT fk_oqvf_participants_round FOREIGN KEY (round_id) REFERENCES oqvf_rounds (id) ON DELETE CASCADE,
            CONSTRAINT fk_oqvf_participants_player FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE CASCADE
        ) $opts");

        $db->exec("CREATE TABLE IF NOT EXISTS oqvf_questions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            round_id INT UNSIGNED NOT NULL,
            author_id INT UNSIGNED NOT NULL,
            body VARCHAR(500) NOT NULL,
            recipient_id INT UNSIGNED NULL,
            status ENUM('submitted','assigned','answered','unanswered','discarded') NOT NULL DEFAULT 'submitted',
            submitted_at INT UNSIGNED NOT NULL,
            UNIQUE KEY uq_oqvf_questions_round_author (round_id, author_id),
            UNIQUE KEY uq_oqvf_questions_round_recipient (round_id, recipient_id),
            KEY idx_oqvf_questions_author (author_id),
            KEY idx_oqvf_questions_recipient (recipient_id),
            CONSTRAINT fk_oqvf_questions_round FOREIGN KEY (round_id) REFERENCES oqvf_rounds (id) ON DELETE CASCADE,
            CONSTRAINT fk_oqvf_questions_author FOREIGN KEY (author_id) REFERENCES players (id) ON DELETE CASCADE,
            CONSTRAINT fk_oqvf_questions_recipient FOREIGN KEY (recipient_id) REFERENCES players (id) ON DELETE CASCADE
        ) $opts");

        $db->exec("CREATE TABLE IF NOT EXISTS oqvf_answers (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            question_id INT UNSIGNED NOT NULL,
            round_id INT UNSIGNED NOT NULL,
            player_id INT UNSIGNED NOT NULL,
            body VARCHAR(500) NOT NULL,
            status ENUM('valid','discarded') NOT NULL DEFAULT 'valid',
            submitted_at INT UNSIGNED NOT NULL,
            UNIQUE KEY uq_oqvf_answers_question (question_id),
            UNIQUE KEY uq_oqvf_answers_round_player (round_id, player_id),
            KEY idx_oqvf_answers_player (player_id),
            CONSTRAINT fk_oqvf_answers_question FOREIGN KEY (question_id) REFERENCES oqvf_questions (id) ON DELETE CASCADE,
            CONSTRAINT fk_oqvf_answers_round FOREIGN KEY (round_id) REFERENCES oqvf_rounds (id) ON DELETE CASCADE,
            CONSTRAINT fk_oqvf_answers_player FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE CASCADE
        ) $opts");

        $db->exec("CREATE TABLE IF NOT EXISTS oqvf_results (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            round_id INT UNSIGNED NOT NULL,
            position INT UNSIGNED NOT NULL,
            question_id INT UNSIGNED NOT NULL,
            answer_id INT UNSIGNED NOT NULL,
            UNIQUE KEY uq_oqvf_results_position (round_id, position),
            UNIQUE KEY uq_oqvf_results_question (round_id, question_id),
            UNIQUE KEY uq_oqvf_results_answer (round_id, answer_id),
            KEY idx_oqvf_results_question (question_id),
            KEY idx_oqvf_results_answer (answer_id),
            CONSTRAINT fk_oqvf_results_round FOREIGN KEY (round_id) REFERENCES oqvf_rounds (id) ON DELETE CASCADE,
            CONSTRAINT fk_oqvf_results_question FOREIGN KEY (question_id) REFERENCES oqvf_questions (id) ON DELETE CASCADE,
            CONSTRAINT fk_oqvf_results_answer FOREIGN KEY (answer_id) REFERENCES oqvf_answers (id) ON DELETE CASCADE
        ) $opts");
    },
];
