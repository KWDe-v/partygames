<?php
declare(strict_types=1);

namespace Games\OQueVoceFaria;

use App\Core\ApiException;
use App\Core\Input;
use App\Core\Text;
use App\Platform\AbstractGame;
use App\Services\Shuffler;
use Games\OQueVoceFaria\Models\Answer;
use Games\OQueVoceFaria\Models\Participant;
use Games\OQueVoceFaria\Models\Question;
use Games\OQueVoceFaria\Models\Result;
use Games\OQueVoceFaria\Models\Round;

/**
 * "O que você faria se..."
 *
 * Fases de cada rodada:
 *   question_writing ──▶ answer_writing ──▶ results ──▶ (nova rodada | fim)
 *          │ (< 3 perguntas válidas)
 *          └──────▶ lobby
 *
 * - Distribuição das perguntas por desarranjo: ninguém recebe a própria.
 * - Resultados: cada pergunta exibida com a resposta de OUTRA pergunta (novo desarranjo).
 * - Antes da etapa de respostas há a tela "Embaralhando perguntas" (shuffle_seconds).
 */
final class Game extends AbstractGame
{
    private const MIN_ROUND_PLAYERS = 3;

    /** @var Round|null */
    private $roundsModel;

    public function joinablePhases(): array
    {
        return ['results'];
    }

    public function normalizeSettings(array $s): array
    {
        if ($s['result_time'] > 0 && $s['result_time'] < 4) {
            throw new ApiException('O tempo por resultado deve ser 0 (manual) ou pelo menos 4 segundos.', 422, 'invalid_input');
        }
        return $s;
    }

    public function start(array $room, int $now): void
    {
        $this->startRound($room, $now);
    }

    public function action(string $op, array $room, array $player, Input $in): array
    {
        switch ($op) {
            case 'submit_question':
                return ['question' => $this->submitQuestion($room, $player, $in->string('text', 4000, 'texto da pergunta'))];
            case 'submit_answer':
                return ['answer' => $this->submitAnswer($room, $player, $in->string('text', 4000, 'texto da resposta'))];
            case 'advance':
                $this->ctx->assertHost($room, $player);
                $this->hostAdvance($room, $in->int('position', 0, 10000, null, 'posição'));
                return ['advanced' => true];
        }
        throw new ApiException('Ação desconhecida.', 400, 'invalid_op');
    }

    public function advance(array $room, int $now): bool
    {
        $round = $room['current_round_id'] ? $this->rounds()->find((int) $room['current_round_id']) : null;
        if ($round === null || $round['status'] !== $room['phase']) {
            return false;
        }
        $roundId = (int) $round['id'];
        $expired = $round['phase_ends_at'] !== null && $now >= (int) $round['phase_ends_at'];
        $parts = new Participant($this->ctx->db());

        switch ($room['phase']) {
            case 'question_writing':
                if ($expired || $parts->countByStatus($roundId, 'question_status', 'pending') === 0) {
                    $this->endQuestionPhase($room, $round, $now);
                    return true;
                }
                return false;
            case 'answer_writing':
                if ($expired || $parts->countByStatus($roundId, 'answer_status', 'pending') === 0) {
                    $this->endAnswerPhase($room, $round, $now);
                    return true;
                }
                return false;
            case 'results':
                if ($expired) {
                    $this->advanceResult($room, $round, $now);
                    return true;
                }
                return false;
        }
        return false;
    }

    /** Quem sai no meio de uma etapa deixa de ser aguardado. */
    public function playerLeft(array $room, int $playerId, int $now): void
    {
        if (!$room['current_round_id']) {
            return;
        }
        $parts = new Participant($this->ctx->db());
        $roundId = (int) $room['current_round_id'];
        if ($room['phase'] === 'question_writing') {
            $parts->markPendingAsMissed($roundId, 'question_status', $playerId);
        } elseif ($room['phase'] === 'answer_writing') {
            $parts->markPendingAsMissed($roundId, 'answer_status', $playerId);
        }
    }

    public function finished(array $room, int $now): void
    {
        $round = $room['current_round_id'] ? $this->rounds()->find((int) $room['current_round_id']) : null;
        if ($round !== null && in_array($round['status'], ['question_writing', 'answer_writing', 'results'], true)) {
            $this->rounds()->update((int) $round['id'], [
                'status' => $round['status'] === 'results' ? 'completed' : 'cancelled',
                'phase_ends_at' => null,
                'ended_at' => $now,
            ]);
        }
    }

    // ------------------------------------------------------------------ ações

    private function submitQuestion(array $room, array $player, string $raw): string
    {
        $round = $this->currentRound($room, 'question_writing', 'A etapa de perguntas não está aberta.');
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        $parts = new Participant($this->ctx->db());
        $part = $parts->get($roundId, $pid);
        if ($part === null) {
            throw new ApiException('Você não participa desta rodada. Aguarde a próxima.', 403, 'not_participant');
        }
        if ($part['question_status'] === 'submitted') {
            throw new ApiException('Você já enviou sua pergunta nesta rodada.', 409, 'already_submitted');
        }
        if ($part['question_status'] !== 'pending' || microtime(true) >= (int) $round['phase_ends_at']) {
            throw new ApiException('Tempo esgotado! Sua pergunta não foi aceita.', 409, 'time_over');
        }
        $text = $this->cleanQuestion($raw);
        $now = time();
        (new Question($this->ctx->db()))->create($roundId, $pid, $text, $now);
        $parts->setStatus($roundId, $pid, 'question_status', 'submitted');
        $this->ctx->bump((int) $room['id'], $now);
        return $text;
    }

    private function submitAnswer(array $room, array $player, string $raw): string
    {
        $round = $this->currentRound($room, 'answer_writing', 'A etapa de respostas não está aberta.');
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        $parts = new Participant($this->ctx->db());
        $part = $parts->get($roundId, $pid);
        if ($part === null || $part['answer_status'] === 'not_eligible') {
            throw new ApiException('Você não participa desta etapa da rodada.', 403, 'not_participant');
        }
        if ($part['answer_status'] === 'submitted') {
            throw new ApiException('Você já enviou sua resposta nesta rodada.', 409, 'already_submitted');
        }
        if ($part['answer_status'] !== 'pending' || microtime(true) >= (int) $round['phase_ends_at']) {
            throw new ApiException('Tempo esgotado! Sua resposta não foi aceita.', 409, 'time_over');
        }
        if (microtime(true) < (int) $round['phase_started_at']) {
            throw new ApiException('As perguntas ainda estão sendo embaralhadas. Aguarde um instante.', 409, 'not_started');
        }
        // O servidor confere que a pergunta foi realmente atribuída a este jogador.
        $questions = new Question($this->ctx->db());
        $question = $questions->findForRecipient($roundId, $pid);
        if ($question === null || $question['status'] !== 'assigned' || (int) $question['author_id'] === $pid) {
            throw new ApiException('Nenhuma pergunta foi atribuída a você nesta rodada.', 409, 'no_question');
        }
        $text = Text::limited(
            $raw,
            $this->ctx->configInt('answer_min_length', 2),
            $this->ctx->configInt('answer_max_length', 250),
            'A resposta'
        );
        $now = time();
        (new Answer($this->ctx->db()))->create((int) $question['id'], $roundId, $pid, $text, $now);
        $questions->update((int) $question['id'], ['status' => 'answered']);
        $parts->setStatus($roundId, $pid, 'answer_status', 'submitted');
        $this->ctx->bump((int) $room['id'], $now);
        return $text;
    }

    /** O anfitrião avança a apresentação; $expected evita avanço duplo por cliques repetidos. */
    private function hostAdvance(array $room, int $expected): void
    {
        $round = $this->currentRound($room, 'results', 'Não há resultados sendo apresentados.');
        if ((int) $round['result_position'] === $expected) {
            $this->advanceResult($room, $round, time());
        }
    }

    /** Remove "O que você faria se" digitado por engano e o "?" final. */
    private function cleanQuestion(string $raw): string
    {
        $s = Text::normalize($raw);
        $s = (string) preg_replace('/^o\s+que\s+voc[eê]\s+faria\s+se\b\s*/iu', '', $s);
        $s = (string) preg_replace('/^[.…,\s]+/u', '', $s);
        $s = (string) preg_replace('/[?\s]+$/u', '', $s);
        Text::requireLength(
            $s,
            $this->ctx->configInt('question_min_length', 5),
            $this->ctx->configInt('question_max_length', 250),
            'A pergunta'
        );
        return $s;
    }

    // ------------------------------------------------------------------ transições

    private function startRound(array $room, int $now): void
    {
        $roomId = (int) $room['id'];
        $need = max(self::MIN_ROUND_PLAYERS, $this->ctx->requiredPlayers($room));
        $online = $this->ctx->onlinePlayers($roomId, $now);
        if (count($online) < $need) {
            $this->ctx->backToLobby($roomId, sprintf(
                'Não há jogadores conectados suficientes para uma nova rodada (mínimo %d). O anfitrião pode iniciar quando houver jogadores.',
                $need
            ));
            $this->ctx->bump($roomId, $now);
            return;
        }
        $number = (int) $room['round_number'] + 1;
        $endsAt = $now + (int) $this->ctx->setting($room, 'question_time', 60);
        $roundId = $this->rounds()->create($roomId, $this->ctx->sessionId($room), $number, $now, $endsAt);
        (new Participant($this->ctx->db()))->addMany($roundId, array_map('intval', array_column($online, 'id')));
        $this->ctx->setPhase($roomId, 'question_writing', $endsAt, [
            'current_round_id' => $roundId,
            'round_number' => $number,
            'notice' => null,
        ]);
        $this->ctx->bump($roomId, $now);
    }

    /** Fim da etapa 1: exclui quem não enviou e distribui as perguntas por desarranjo. */
    private function endQuestionPhase(array $room, array $round, int $now): void
    {
        $roomId = (int) $room['id'];
        $roundId = (int) $round['id'];
        $db = $this->ctx->db();
        $parts = new Participant($db);
        $questions = new Question($db);
        $parts->markPendingAsMissed($roundId, 'question_status');

        // Autores que saíram da sala não podem receber pergunta: a deles é descartada.
        $eligible = [];
        foreach ($questions->submittedWithAuthorStatus($roundId) as $q) {
            if ($q['author_status'] === 'active') {
                $eligible[] = ['id' => (int) $q['id'], 'author_id' => (int) $q['author_id']];
            } else {
                $questions->update((int) $q['id'], ['status' => 'discarded']);
            }
        }

        if (count($eligible) < self::MIN_ROUND_PLAYERS) {
            $questions->markRoundStatus($roundId, 'submitted', 'discarded');
            $message = sprintf(
                'A rodada %d foi cancelada: apenas %d pergunta(s) válida(s) foram enviadas e são necessárias pelo menos %d. O anfitrião pode iniciar uma nova rodada.',
                (int) $round['number'],
                count($eligible),
                self::MIN_ROUND_PLAYERS
            );
            $this->rounds()->update($roundId, ['status' => 'cancelled', 'phase_ends_at' => null, 'ended_at' => $now, 'note' => $message]);
            $this->ctx->backToLobby($roomId, $message);
            $this->ctx->bump($roomId, $now);
            return;
        }

        foreach (self::assignRecipients($eligible) as $questionId => $recipientId) {
            $questions->update($questionId, ['recipient_id' => $recipientId, 'status' => 'assigned']);
            $parts->setStatus($roundId, $recipientId, 'answer_status', 'pending');
        }

        // A etapa de respostas começa depois da tela "Embaralhando perguntas"; o prazo conta a partir daí.
        $start = $now + max(0, min(15, $this->ctx->configInt('shuffle_seconds', 4)));
        $endsAt = $start + (int) $this->ctx->setting($room, 'answer_time', 60);
        $this->rounds()->update($roundId, ['status' => 'answer_writing', 'phase_started_at' => $start, 'phase_ends_at' => $endsAt]);
        $this->ctx->setPhase($roomId, 'answer_writing', $endsAt);
        $this->ctx->bump($roomId, $now);
    }

    /**
     * Cada pergunta vai para exatamente um outro autor; cada autor recebe exatamente uma.
     *
     * @param array<int, array{id:int, author_id:int}> $questions
     * @return array<int, int> question_id => recipient_id
     */
    public static function assignRecipients(array $questions): array
    {
        $questions = Shuffler::shuffle($questions);
        $n = count($questions);
        $perm = Shuffler::derangement($n);
        $assignment = [];
        $recipients = [];
        foreach ($questions as $i => $q) {
            $recipient = $questions[$perm[$i]]['author_id'];
            if ($recipient === $q['author_id'] || isset($recipients[$recipient]) || isset($assignment[$q['id']])) {
                throw new \LogicException('Distribuição de perguntas inválida.');
            }
            $assignment[$q['id']] = $recipient;
            $recipients[$recipient] = true;
        }
        if (count($assignment) !== $n || count($recipients) !== $n) {
            throw new \LogicException('Distribuição de perguntas incompleta.');
        }
        return $assignment;
    }

    /** Fim da etapa 2: monta e persiste a sequência embaralhada de resultados. */
    private function endAnswerPhase(array $room, array $round, int $now): void
    {
        $roomId = (int) $room['id'];
        $roundId = (int) $round['id'];
        $db = $this->ctx->db();
        (new Participant($db))->markPendingAsMissed($roundId, 'answer_status');
        (new Question($db))->markRoundStatus($roundId, 'assigned', 'unanswered');

        $pairs = array_map(static function (array $p): array {
            return ['question_id' => (int) $p['question_id'], 'answer_id' => (int) $p['answer_id']];
        }, (new Answer($db))->validPairs($roundId));

        $sequence = count($pairs) >= 2 ? self::mixResults($pairs) : [];
        (new Result($db))->insertSequence($roundId, $sequence);
        $count = count($sequence);

        $resultTime = (int) $this->ctx->setting($room, 'result_time', 12);
        $note = null;
        if ($count === 0) {
            $note = count($pairs) === 1
                ? 'Só uma resposta foi enviada nesta rodada — não há como misturar as combinações.'
                : 'Nenhuma resposta foi enviada nesta rodada.';
            $endsAt = $now + max(3, $this->ctx->configInt('empty_results_screen', 8));
        } else {
            $endsAt = $resultTime > 0 ? $now + $resultTime : null;
        }
        $this->rounds()->update($roundId, [
            'status' => 'results',
            'phase_started_at' => $now,
            'phase_ends_at' => $endsAt,
            'result_position' => 0,
            'result_count' => $count,
            'note' => $note,
        ]);
        $this->ctx->setPhase($roomId, 'results', $endsAt);
        $this->ctx->bump($roomId, $now);
    }

    /**
     * Embaralha a ordem das perguntas e associa a cada uma a resposta de OUTRA pergunta (desarranjo).
     *
     * @param array<int, array{question_id:int, answer_id:int}> $pairs pares originais
     * @return array<int, array{question_id:int, answer_id:int}>
     */
    public static function mixResults(array $pairs): array
    {
        $pairs = Shuffler::shuffle($pairs);
        $n = count($pairs);
        $perm = Shuffler::derangement($n);
        $sequence = [];
        $usedQ = [];
        $usedA = [];
        foreach ($pairs as $i => $pair) {
            $donor = $pairs[$perm[$i]];
            $questionId = $pair['question_id'];
            $answerId = $donor['answer_id'];
            if ($donor['question_id'] === $questionId || $answerId === $pair['answer_id']
                || isset($usedQ[$questionId]) || isset($usedA[$answerId])) {
                throw new \LogicException('Sequência de resultados inválida.');
            }
            $usedQ[$questionId] = true;
            $usedA[$answerId] = true;
            $sequence[] = ['question_id' => $questionId, 'answer_id' => $answerId];
        }
        if (count($sequence) !== $n) {
            throw new \LogicException('Sequência de resultados incompleta.');
        }
        return $sequence;
    }

    private function advanceResult(array $room, array $round, int $now): void
    {
        $roomId = (int) $room['id'];
        $next = (int) $round['result_position'] + 1;
        if ($next >= (int) $round['result_count']) {
            $this->completeRound($room, $round, $now);
            return;
        }
        $resultTime = (int) $this->ctx->setting($room, 'result_time', 12);
        $endsAt = $resultTime > 0 ? $now + $resultTime : null;
        $this->rounds()->update((int) $round['id'], [
            'result_position' => $next,
            'phase_started_at' => $now,
            'phase_ends_at' => $endsAt,
        ]);
        $this->ctx->setPhase($roomId, 'results', $endsAt);
        $this->ctx->bump($roomId, $now);
    }

    private function completeRound(array $room, array $round, int $now): void
    {
        $this->rounds()->update((int) $round['id'], ['status' => 'completed', 'phase_ends_at' => null, 'ended_at' => $now]);
        $room = $this->ctx->fresh($room);
        $maxRounds = (int) $this->ctx->setting($room, 'max_rounds', 0);
        if ($maxRounds > 0 && (int) $round['number'] >= $maxRounds) {
            $this->ctx->endMatch($room, 'rounds_done', $now, 'Fim de jogo! Todas as rodadas foram jogadas.');
            return;
        }
        $this->startRound($room, $now);
    }

    // ------------------------------------------------------------------ visão do jogador

    public function view(array $room, array $player, array $players): array
    {
        $round = $room['current_round_id'] ? $this->rounds()->find((int) $room['current_round_id']) : null;
        if ($round === null) {
            return [];
        }
        $db = $this->ctx->db();
        $roundId = (int) $round['id'];
        $phase = (string) $round['status'];
        $pid = (int) $player['id'];
        $parts = (new Participant($db))->forRound($roundId);
        $mine = $parts[$pid] ?? null;

        $view = [
            'round' => [
                'id' => $roundId,
                'number' => (int) $round['number'],
                'phase' => $phase,
                'started_at' => (int) $round['phase_started_at'] * 1000,
                'ends_at' => $round['phase_ends_at'] !== null ? (int) $round['phase_ends_at'] * 1000 : null,
                'done' => 0,
                'total' => 0,
            ],
            'my' => ['role' => $mine === null ? 'spectator' : 'player'],
            'limits' => [
                'qmin' => $this->ctx->configInt('question_min_length', 5),
                'qmax' => $this->ctx->configInt('question_max_length', 250),
                'amin' => $this->ctx->configInt('answer_min_length', 2),
                'amax' => $this->ctx->configInt('answer_max_length', 250),
            ],
            'progress' => [],
        ];

        foreach ($players as $p) {
            $part = $parts[(int) $p['id']] ?? null;
            if ($phase === 'question_writing') {
                $view['progress'][(int) $p['id']] = $part === null ? 'waiting' : $part['question_status'];
            } elseif ($phase === 'answer_writing') {
                $view['progress'][(int) $p['id']] = $part === null ? 'waiting'
                    : ($part['answer_status'] === 'not_eligible' ? 'excluded' : $part['answer_status']);
            }
        }

        if ($phase === 'question_writing') {
            $view['round']['total'] = count($parts);
            foreach ($parts as $p) {
                $view['round']['done'] += $p['question_status'] === 'submitted' ? 1 : 0;
            }
            if ($mine !== null) {
                $view['my']['status'] = $mine['question_status'];
                if ($mine['question_status'] === 'submitted') {
                    $q = (new Question($db))->findByAuthor($roundId, $pid);
                    $view['my']['question'] = $q !== null ? (string) $q['body'] : null;
                }
            }
        } elseif ($phase === 'answer_writing') {
            // Durante "Embaralhando perguntas" a pergunta recebida ainda não é enviada ao navegador.
            $shuffling = microtime(true) < (int) $round['phase_started_at'];
            $view['round']['shuffling'] = $shuffling;
            foreach ($parts as $p) {
                if ($p['answer_status'] !== 'not_eligible') {
                    $view['round']['total']++;
                    $view['round']['done'] += $p['answer_status'] === 'submitted' ? 1 : 0;
                }
            }
            if ($mine !== null) {
                if ($mine['answer_status'] === 'not_eligible') {
                    $view['my']['status'] = 'excluded';
                } else {
                    $view['my']['status'] = $mine['answer_status'];
                    $q = $shuffling ? null : (new Question($db))->findForRecipient($roundId, $pid);
                    $view['my']['question'] = $q !== null ? (string) $q['body'] : null;
                    if ($mine['answer_status'] === 'submitted') {
                        $a = (new Answer($db))->findByPlayer($roundId, $pid);
                        $view['my']['answer'] = $a !== null ? (string) $a['body'] : null;
                    }
                }
            }
        } elseif ($phase === 'results') {
            $count = (int) $round['result_count'];
            $position = (int) $round['result_position'];
            $item = $count > 0 ? (new Result($db))->at($roundId, $position) : null;
            $view['result'] = [
                'position' => $position,
                'total' => $count,
                'auto' => (int) $this->ctx->setting($room, 'result_time', 12) > 0 || $count === 0,
                'note' => $round['note'],
                'item' => $item !== null ? ['question' => (string) $item['question'], 'answer' => (string) $item['answer']] : null,
            ];
        }
        return $view;
    }

    // ------------------------------------------------------------------ auxiliares

    private function rounds(): Round
    {
        if ($this->roundsModel === null) {
            $this->roundsModel = new Round($this->ctx->db());
        }
        return $this->roundsModel;
    }

    private function currentRound(array $room, string $phase, string $message): array
    {
        if ($room['phase'] !== $phase || !$room['current_round_id']) {
            throw new ApiException($message, 409, 'invalid_state');
        }
        $round = $this->rounds()->find((int) $room['current_round_id']);
        if ($round === null || $round['status'] !== $phase) {
            throw new ApiException($message, 409, 'invalid_state');
        }
        return $round;
    }
}
