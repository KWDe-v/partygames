<?php
declare(strict_types=1);

namespace Games\CrimeMystery;

use App\Core\ApiException;
use App\Core\Input;
use App\Platform\RoundGame;

/**
 * "Quem Cometeu o Crime?"
 *
 * Uma partida = um caso:  briefing ─▶ investigation ─▶ accusation ─▶ reveal ─▶ fim (lobby)
 *
 * Papéis (sorteados no servidor, em ciclo):
 *   detetive     — pode fazer perguntas de "pressão" exclusivas;
 *   perito       — recebe o laudo técnico secreto (pista de papel);
 *   jornalista   — recebe os boatos (pista de papel);
 *   investigador — tem +2 ações de investigação.
 * As pistas-chave nunca são exclusivas de um papel (o validador garante): todos podem resolver.
 *
 * Pistas (crime_unlocks): 'start' e 'time' são públicas; 'role' só para o papel; 'search' só para
 * quem vasculhou o local; 'interrogation' fica pública quando alguém faz a pergunta certa.
 * O servidor só devolve uma pista a quem tem acesso a ela, e a solução só na revelação.
 *
 * Cada pergunta/local custa 1 ação e só pode ser usado uma vez por jogador (UNIQUE).
 * Acusação: suspeito + motivo, uma por jogador, secreta até a revelação.
 * Pontos (definidos no caso): culpado certo, motivo certo e — se ativado — primeira descoberta de
 * cada pista importante.
 */
final class Game extends RoundGame
{
    public const ROLE_INFO = [
        'detetive' => ['name' => 'Detetive', 'description' => 'Você pode pressionar os suspeitos com perguntas exclusivas.'],
        'perito' => ['name' => 'Perito', 'description' => 'Você recebe o laudo técnico secreto da cena do crime.'],
        'jornalista' => ['name' => 'Jornalista', 'description' => 'Você recebe os boatos que circulam sobre os suspeitos.'],
        'investigador' => ['name' => 'Investigador', 'description' => 'Você tem 2 ações de investigação a mais que os outros.'],
    ];
    private const REVEAL_SECONDS = 180;

    protected function totalRounds(array $room): int
    {
        return 1;
    }

    protected function skippablePhases(): array
    {
        return ['briefing', 'investigation', 'reveal'];
    }

    public function joinablePhases(): array
    {
        return [];
    }

    // ------------------------------------------------------------------ preparação

    protected function planRound(array $room, array $players, int $number, int $now): array
    {
        $chosen = (string) $this->ctx->setting($room, 'case', 'random');
        $cases = CaseLibrary::all();
        if (!$cases) {
            throw new ApiException('Nenhum caso válido está instalado.', 500, 'no_cases');
        }
        if (!isset($cases[$chosen])) {
            $pool = array_map(static function (string $id): array {
                return ['key' => $id];
            }, array_keys($cases));
            $chosen = $this->pickPrompt($room, $pool)['key'];
        }
        $roles = array_keys(self::ROLE_INFO);
        $assign = [];
        foreach (self::shuffle(array_map('intval', array_column($players, 'id'))) as $i => $pid) {
            $assign[$pid] = ['role' => $roles[$i % count($roles)]];
        }
        return [
            'phase' => 'briefing',
            'seconds' => $this->int($room, 'briefing_time', 90),
            'prompt_key' => $chosen,
            'public' => ['case_id' => $chosen],
            'players' => $assign,
        ];
    }

    private function caseOf(array $round): array
    {
        $case = CaseLibrary::get((string) $round['public']['case_id']);
        if ($case === null) {
            throw new ApiException('O caso desta partida não está mais disponível.', 500, 'case_missing');
        }
        return $case;
    }

    // ------------------------------------------------------------------ transições

    protected function step(array $room, array $round, int $now): bool
    {
        $roundId = (int) $round['id'];
        $players = $this->repo()->players($roundId);
        $active = self::activeIds($players);
        switch ($round['phase']) {
            case 'briefing':
                if (!$this->expired($round, $now) && array_diff($active, $this->readyIds($roundId, 'ready:brief'))) {
                    return false;
                }
                $this->startInvestigation($room, $round, $players, $now);
                return true;
            case 'investigation':
                $released = $this->releaseTimedClues($round, $now);
                if ($this->expired($round, $now) || !array_diff($active, $this->readyIds($roundId, 'ready:accuse'))) {
                    $this->phase($room, $round, 'accusation', $this->int($room, 'accuse_time', 120), $now);
                    return true;
                }
                if ($released) {
                    $this->wakeAt($room, $this->nextWake($round, $now));
                    $this->ctx->bump((int) $room['id'], $now);
                }
                return false;
            case 'accusation':
                $accused = array_map('intval', array_column($this->ctx->db()->fetchAll(
                    'SELECT player_id FROM crime_accusations WHERE round_id = ?',
                    [$roundId]
                ), 'player_id'));
                if (!$this->expired($round, $now) && array_diff($active, $accused)) {
                    return false;
                }
                $this->reveal($room, $round, $players, $now);
                return true;
            case 'reveal':
                if (!$this->expired($round, $now)) {
                    return false;
                }
                $this->finishRound($room, $round, $now);
                return true;
        }
        return false;
    }

    private function startInvestigation(array $room, array $round, array $players, int $now): void
    {
        $roundId = (int) $round['id'];
        $case = $this->caseOf($round);
        foreach ($case['clues'] as $cid => $clue) {
            if ($clue['unlock']['type'] === 'start') {
                $this->unlock($roundId, $cid, 0, 'start', null, $now);
            } elseif ($clue['unlock']['type'] === 'role') {
                foreach ($players as $pid => $rp) {
                    if ($rp['role'] === $clue['unlock']['role']) {
                        $this->unlock($roundId, $cid, (int) $pid, 'role', (int) $pid, $now);
                    }
                }
            }
        }
        $duration = 60 * $this->int($room, 'investigation_minutes', 12);
        $this->phase($room, $round, 'investigation', $duration, $now, $this->nextWake(['phase_started_at' => $now, 'phase_ends_at' => $now + $duration] + $round, $now));
    }

    /** Horários de liberação das pistas por tempo (fração da investigação). */
    private function timedClues(array $round): array
    {
        $start = (int) $round['phase_started_at'];
        $duration = max(1, (int) $round['phase_ends_at'] - $start);
        $out = [];
        foreach ($this->caseOf($round)['clues'] as $cid => $clue) {
            if ($clue['unlock']['type'] === 'time') {
                $out[$cid] = $start + (int) floor($clue['unlock']['at'] * $duration);
            }
        }
        asort($out);
        return $out;
    }

    private function releaseTimedClues(array $round, int $now): bool
    {
        $released = false;
        foreach ($this->timedClues($round) as $cid => $at) {
            if ($now >= $at && $this->unlock((int) $round['id'], $cid, 0, 'time', null, $now)) {
                $released = true;
            }
        }
        return $released;
    }

    /** Próximo momento em que o jogo precisa "acordar": a próxima pista por tempo ou o fim da fase. */
    private function nextWake(array $round, int $now): int
    {
        foreach ($this->timedClues($round) as $at) {
            if ($at > $now) {
                return min($at, (int) $round['phase_ends_at']);
            }
        }
        return (int) $round['phase_ends_at'];
    }

    private function reveal(array $room, array $round, array $players, int $now): void
    {
        $roundId = (int) $round['id'];
        $number = (int) $round['number'];
        $case = $this->caseOf($round);
        $sol = $case['solution'];
        $rows = $this->ctx->db()->fetchAll('SELECT player_id, suspect_id, motive_id FROM crime_accusations WHERE round_id = ?', [$roundId]);
        $accusations = [];
        $solvers = [];
        foreach ($rows as $r) {
            $pid = (int) $r['player_id'];
            $right = $r['suspect_id'] === $sol['culprit'];
            $rightMotive = $r['motive_id'] === $sol['motive'];
            if ($right) {
                $solvers[] = $pid;
                $this->ctx->award($room, $number, $pid, (int) $case['scoring']['culprit'], 'Acusou o culpado', 'crime:culprit');
            }
            if ($rightMotive) {
                $this->ctx->award($room, $number, $pid, (int) $case['scoring']['motive'], 'Acertou o motivo', 'crime:motive');
            }
            $accusations[] = [
                'nickname' => (string) ($players[$pid]['nickname'] ?? '?'),
                'suspect' => (string) $case['suspects'][$r['suspect_id']]['name'],
                'motive' => (string) $case['motives'][$r['motive_id']],
                'correct' => $right,
                'motive_correct' => $rightMotive,
            ];
        }
        $public = $round['public'];
        $public['result'] = [
            'culprit' => ['id' => $sol['culprit'], 'name' => $case['suspects'][$sol['culprit']]['name']],
            'motive' => $case['motives'][$sol['motive']],
            'explanation' => $sol['explanation'],
            'key_clues' => array_map(static function (string $cid) use ($case): array {
                return ['id' => $cid, 'title' => $case['clues'][$cid]['title'], 'text' => $case['clues'][$cid]['text']];
            }, $sol['key_clues']),
            'accusations' => $accusations,
        ];
        $this->repo()->savePublic($roundId, $public);
        $this->ctx->event(
            $room,
            $number,
            $case['title'],
            sprintf('Culpado: %s. %s', $case['suspects'][$sol['culprit']]['name'], $solvers ? 'Resolveram: ' . self::names($players, $solvers) . '.' : 'Ninguém acertou.'),
            'crime'
        );
        $this->phase($room, $round, 'reveal', self::REVEAL_SECONDS, $now);
    }

    // ------------------------------------------------------------------ ações

    protected function handle(string $op, array $room, array $round, array $player, Input $in): array
    {
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        $case = $this->caseOf($round);
        $rp = $this->requireParticipant($round, $player);
        $now = time();

        switch ($op) {
            case 'ready':
                $ballot = $round['phase'] === 'briefing' ? 'ready:brief' : ($round['phase'] === 'investigation' ? 'ready:accuse' : null);
                if ($ballot === null) {
                    throw new ApiException('Nada para confirmar agora.', 409, 'invalid_state');
                }
                $this->requirePhase($round, (string) $round['phase'], 'Etapa encerrada.');
                try {
                    $this->repo()->castVote($roundId, $pid, $ballot, '1', $now);
                } catch (ApiException $e) {
                    // já estava pronto: operação idempotente
                }
                $this->ctx->bump((int) $room['id'], $now);
                return ['ready' => true];

            case 'ask':
                $this->requirePhase($round, 'investigation', 'A investigação não está em andamento.');
                $suspect = $in->string('suspect', 40, 'suspeito');
                $qid = $in->string('question', 40, 'pergunta');
                $question = null;
                foreach ((array) ($case['interrogations'][$suspect] ?? []) as $q) {
                    if ($q['id'] === $qid) {
                        $question = $q;
                    }
                }
                if ($question === null) {
                    throw new ApiException('Pergunta não encontrada.', 422, 'invalid_target');
                }
                if (isset($question['role']) && $question['role'] !== $rp['role']) {
                    throw new ApiException('Só o ' . self::ROLE_INFO[$question['role']]['name'] . ' pode fazer esta pergunta.', 403, 'role_required');
                }
                if (isset($question['requires']) && !isset($this->visibleClues($roundId, $pid)[$question['requires']])) {
                    throw new ApiException('Você ainda não tem a pista necessária para esta pergunta.', 403, 'clue_required');
                }
                $this->spendAction($room, $roundId, $pid, (string) $rp['role'], 'question', $suspect, $qid, $now);
                $unlocked = null;
                if (isset($question['unlocks'])) {
                    $unlocked = $this->discover($room, $round, $case, (string) $question['unlocks'], 0, 'interrogation', $pid, $now);
                }
                $this->ctx->bump((int) $room['id'], $now);
                return ['answer' => $question['answer'], 'unlocked' => $unlocked];

            case 'search':
                $this->requirePhase($round, 'investigation', 'A investigação não está em andamento.');
                $lid = $in->string('location', 40, 'local');
                if (!isset($case['locations'][$lid])) {
                    throw new ApiException('Local não encontrado.', 422, 'invalid_target');
                }
                $this->spendAction($room, $roundId, $pid, (string) $rp['role'], 'search', $lid, $lid, $now);
                $cid = (string) $case['locations'][$lid]['clue'];
                $this->discover($room, $round, $case, $cid, $pid, 'search', $pid, $now);
                $this->ctx->bump((int) $room['id'], $now);
                return ['clue' => ['title' => $case['clues'][$cid]['title'], 'text' => $case['clues'][$cid]['text']]];

            case 'accuse':
                $this->requirePhase($round, 'accusation', 'A acusação não está aberta.');
                $suspect = $in->string('suspect', 40, 'suspeito');
                $motive = $in->string('motive', 40, 'motivo');
                if (!isset($case['suspects'][$suspect]) || !isset($case['motives'][$motive])) {
                    throw new ApiException('Escolha um suspeito e um motivo da lista.', 422, 'invalid_target');
                }
                try {
                    $this->ctx->db()->insert(
                        'INSERT INTO crime_accusations (round_id, player_id, suspect_id, motive_id, created_at) VALUES (?, ?, ?, ?, ?)',
                        [$roundId, $pid, $suspect, $motive, $now]
                    );
                } catch (\mysqli_sql_exception $e) {
                    if ($e->getCode() === 1062) {
                        throw new ApiException('Sua acusação já foi registrada e não pode ser alterada.', 409, 'already_submitted');
                    }
                    throw $e;
                }
                $this->ctx->bump((int) $room['id'], $now);
                return ['accused' => true];
        }
        throw new ApiException('Ação desconhecida.', 400, 'invalid_op');
    }

    private function actionsAllowed(array $room, string $role): int
    {
        return $this->int($room, 'actions', 6) + ($role === 'investigador' ? 2 : 0);
    }

    private function actionsUsed(int $roundId, int $pid): int
    {
        return (int) $this->ctx->db()->fetchValue('SELECT COUNT(*) FROM crime_actions WHERE round_id = ? AND player_id = ?', [$roundId, $pid]);
    }

    private function spendAction(array $room, int $roundId, int $pid, string $role, string $kind, string $target, string $item, int $now): void
    {
        if ($this->actionsUsed($roundId, $pid) >= $this->actionsAllowed($room, $role)) {
            throw new ApiException('Suas ações de investigação acabaram.', 409, 'no_actions');
        }
        try {
            $this->ctx->db()->insert(
                'INSERT INTO crime_actions (round_id, player_id, kind, target, item, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$roundId, $pid, $kind, $target, $item, $now]
            );
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                throw new ApiException($kind === 'search' ? 'Você já vasculhou este local.' : 'Você já fez esta pergunta.', 409, 'already_done');
            }
            throw $e;
        }
    }

    /** Libera uma pista e dá ponto ao primeiro que encontrou uma pista importante. */
    private function discover(array $room, array $round, array $case, string $cid, int $visibleTo, string $source, int $finder, int $now): array
    {
        $roundId = (int) $round['id'];
        $firstEver = (int) $this->ctx->db()->fetchValue('SELECT COUNT(*) FROM crime_unlocks WHERE round_id = ? AND clue_id = ?', [$roundId, $cid]) === 0;
        $this->unlock($roundId, $cid, $visibleTo, $source, $finder, $now);
        if ($firstEver && !empty($case['clues'][$cid]['important']) && $this->ctx->setting($room, 'clue_points', true)) {
            $this->ctx->award($room, (int) $round['number'], $finder, (int) $case['scoring']['clue'], 'Descobriu: ' . $case['clues'][$cid]['title'], 'clue:' . $cid);
        }
        return ['id' => $cid, 'title' => $case['clues'][$cid]['title'], 'text' => $case['clues'][$cid]['text'], 'public' => $visibleTo === 0];
    }

    private function unlock(int $roundId, string $cid, int $playerId, string $source, ?int $finder, int $now): bool
    {
        return $this->ctx->db()->execute(
            'INSERT IGNORE INTO crime_unlocks (round_id, clue_id, player_id, source, found_by, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$roundId, $cid, $playerId, $source, $finder, $now]
        ) > 0;
    }

    private function readyIds(int $roundId, string $ballot): array
    {
        return array_map('intval', array_column($this->repo()->votes($roundId, $ballot), 'voter_id'));
    }

    /** Pistas que ESTE jogador pode ver: públicas + as suas. */
    private function visibleClues(int $roundId, int $pid): array
    {
        $out = [];
        foreach ($this->ctx->db()->fetchAll(
            'SELECT clue_id, player_id, source, created_at FROM crime_unlocks WHERE round_id = ? AND (player_id = 0 OR player_id = ?) ORDER BY id',
            [$roundId, $pid]
        ) as $r) {
            if (!isset($out[$r['clue_id']]) || (int) $r['player_id'] === 0) {
                $out[(string) $r['clue_id']] = $r;
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------ visão do jogador

    protected function roundView(array $room, array $round, array $player, array $roundPlayers): array
    {
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        $phase = (string) $round['phase'];
        $case = $this->caseOf($round);
        $mine = $roundPlayers[$pid] ?? null;

        $suspects = [];
        foreach ($case['suspects'] as $sid => $s) {
            $suspects[] = ['id' => $sid, 'name' => $s['name'], 'role' => $s['role'], 'profile' => $s['profile'], 'motive' => $s['motive'], 'alibi' => $s['alibi']];
        }
        $team = [];
        foreach ($roundPlayers as $id => $rp) {
            $team[] = ['id' => (int) $id, 'nickname' => (string) $rp['nickname'], 'role' => self::ROLE_INFO[$rp['role']]['name'] ?? ''];
        }
        $view = [
            'case' => [
                'title' => $case['title'], 'difficulty' => $case['difficulty'], 'intro' => $case['intro'], 'crime' => $case['crime'],
                'victim' => $case['victim'], 'location' => $case['location'], 'time' => $case['time'], 'suspects' => $suspects,
            ],
            'team' => $team,
            'my_role' => $mine !== null ? ['id' => $mine['role']] + self::ROLE_INFO[$mine['role']] : null,
        ];

        $ballot = $phase === 'briefing' ? 'ready:brief' : 'ready:accuse';
        $ready = $this->readyIds($roundId, $ballot);
        $accused = array_map('intval', array_column($this->ctx->db()->fetchAll('SELECT player_id FROM crime_accusations WHERE round_id = ?', [$roundId]), 'player_id'));
        $progress = [];
        foreach ($roundPlayers as $id => $rp) {
            if ($phase === 'accusation') {
                $progress[(int) $id] = in_array((int) $id, $accused, true) ? 'submitted' : 'pending';
            } elseif ($phase !== 'reveal') {
                $progress[(int) $id] = in_array((int) $id, $ready, true) ? 'submitted' : 'pending';
            }
        }
        $view['progress'] = $progress;
        $view['ready'] = in_array($pid, $ready, true);
        $view['ready_count'] = count(array_intersect($ready, self::activeIds($roundPlayers)));

        if (in_array($phase, ['investigation', 'accusation'], true) && $mine !== null) {
            $clues = [];
            foreach ($this->visibleClues($roundId, $pid) as $cid => $u) {
                $clues[] = [
                    'id' => $cid,
                    'title' => $case['clues'][$cid]['title'],
                    'text' => $case['clues'][$cid]['text'],
                    'private' => (int) $u['player_id'] !== 0,
                    'source' => (string) $u['source'],
                ];
            }
            $view['clues'] = $clues;
            $visible = $this->visibleClues($roundId, $pid);

            $done = [];
            $log = [];
            foreach ($this->ctx->db()->fetchAll('SELECT kind, target, item FROM crime_actions WHERE round_id = ? AND player_id = ? ORDER BY id', [$roundId, $pid]) as $a) {
                $done[$a['kind'] . ':' . $a['item']] = true;
                if ($a['kind'] === 'question') {
                    foreach ($case['interrogations'][$a['target']] as $q) {
                        if ($q['id'] === $a['item']) {
                            $log[] = ['suspect' => $case['suspects'][$a['target']]['name'], 'question' => $q['question'], 'answer' => $q['answer']];
                        }
                    }
                }
            }
            $view['interrogations'] = $log;
            $questions = [];
            foreach ($case['interrogations'] as $sid => $qs) {
                foreach ($qs as $q) {
                    if (isset($done['question:' . $q['id']])
                        || (isset($q['role']) && $q['role'] !== $mine['role'])
                        || (isset($q['requires']) && !isset($visible[$q['requires']]))) {
                        continue;
                    }
                    $questions[] = ['suspect' => $sid, 'id' => $q['id'], 'question' => $q['question'], 'special' => isset($q['role'])];
                }
            }
            $view['questions'] = $questions;
            $view['locations'] = [];
            foreach ($case['locations'] as $lid => $l) {
                $view['locations'][] = ['id' => $lid, 'name' => $l['name'], 'searched' => isset($done['search:' . $lid])];
            }
            $view['actions_total'] = $this->actionsAllowed($room, (string) $mine['role']);
            $view['actions_left'] = max(0, $view['actions_total'] - count($done));
            $next = null;
            foreach ($this->timedClues($round) as $cid => $at) {
                if (!isset($visible[$cid]) && $phase === 'investigation') {
                    $next = $at * 1000;
                    break;
                }
            }
            $view['next_clue_at'] = $next;
        }
        if ($phase === 'accusation') {
            $view['motives'] = [];
            foreach ($case['motives'] as $mid => $label) {
                $view['motives'][] = ['id' => $mid, 'label' => $label];
            }
            $row = $this->ctx->db()->fetchOne('SELECT suspect_id, motive_id FROM crime_accusations WHERE round_id = ? AND player_id = ?', [$roundId, $pid]);
            $view['my_accusation'] = $row !== null ? [
                'suspect' => $case['suspects'][$row['suspect_id']]['name'],
                'motive' => $case['motives'][$row['motive_id']],
            ] : null;
            $view['accused_count'] = count($accused);
        }
        if ($phase === 'reveal') {
            $view['result'] = $round['public']['result'] ?? null;
        }
        return $view;
    }
}
