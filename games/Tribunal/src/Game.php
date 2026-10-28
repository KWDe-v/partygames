<?php
declare(strict_types=1);

namespace Games\Tribunal;

use App\Core\ApiException;
use App\Core\Input;
use App\Core\Text;
use App\Platform\RoundGame;

/**
 * "O Tribunal"
 *
 * Rodada (um caso): briefing ─▶ accusation ─▶ defense ─▶ testimony ─▶ final ─▶ voting ─▶ reveal
 * Etapas de fala sem ninguém ativo para falar são puladas, e todas têm prazo: o jogo nunca trava.
 *
 * Papéis por nº de jogadores (sorteados no servidor):
 *   3: réu, promotor, jurado (o réu faz a própria defesa)
 *   4: + defesa     5: + testemunha     6: + juiz     8+: + 2ª testemunha     demais: jurados
 * A verdade do caso (culpado/inocente) é sorteada e só o réu a conhece; as testemunhas recebem
 * fatos verdadeiros. Papéis são públicos; informações e votos são secretos.
 *
 * Veredito: maioria dos votos de jurados e testemunhas. Empate (inclusive sem votos): decide o
 * voto do juiz; sem juiz ou sem voto dele, o réu é absolvido.
 */
final class Game extends RoundGame
{
    private const BRIEFING = 25;
    private const STAGES = [
        'accusation' => ['promotor'],
        'defense' => ['defesa'],
        'testimony' => ['testemunha'],
        'final' => ['reu'],
    ];
    public const ROLE_NAMES = [
        'reu' => 'Réu', 'promotor' => 'Promotor', 'defesa' => 'Advogado de defesa',
        'testemunha' => 'Testemunha', 'juiz' => 'Juiz', 'jurado' => 'Jurado',
    ];
    private const VOTERS = ['jurado', 'testemunha', 'juiz'];
    private const MIN_LEN = 10;
    private const MAX_LEN = 400;

    protected function skippablePhases(): array
    {
        return ['briefing', 'reveal'];
    }

    /** Papéis para N jogadores, na ordem em que são distribuídos. */
    public static function rolesFor(int $n): array
    {
        $roles = ['reu', 'promotor'];
        if ($n >= 4) {
            $roles[] = 'defesa';
        }
        if ($n >= 5) {
            $roles[] = 'testemunha';
        }
        if ($n >= 6) {
            $roles[] = 'juiz';
        }
        if ($n >= 8) {
            $roles[] = 'testemunha';
        }
        while (count($roles) < $n) {
            $roles[] = 'jurado';
        }
        return $roles;
    }

    private static function cases(): array
    {
        return require dirname(__DIR__) . '/content/cases.php';
    }

    protected function planRound(array $room, array $players, int $number, int $now): array
    {
        $cases = self::cases();
        $pool = [];
        foreach ($cases as $id => $c) {
            $pool[] = ['key' => $id];
        }
        $caseId = $this->pickPrompt($room, $pool)['key'];
        $case = $cases[$caseId];
        $truth = random_int(0, 1) === 1 ? 'culpado' : 'inocente';
        $ids = self::shuffle(array_map('intval', array_column($players, 'id')));
        $roles = self::rolesFor(count($ids));
        $facts = self::shuffle($case['fatos'][$truth]);

        $assign = [];
        $roleMap = [];
        $witness = 0;
        foreach ($ids as $i => $pid) {
            $role = $roles[$i];
            $roleMap[$pid] = $role;
            switch ($role) {
                case 'reu':
                    $info = [$case['reu'][$truth]];
                    if (!in_array('defesa', $roles, true)) {
                        $info[] = 'Sem advogado nesta partida: você mesmo faz a sua defesa.';
                    }
                    break;
                case 'promotor':
                    $info = $case['provas'];
                    break;
                case 'defesa':
                    $info = $case['argumentos'];
                    break;
                case 'testemunha':
                    $info = ['O que você viu: ' . $facts[$witness++ % count($facts)]];
                    break;
                case 'juiz':
                    $info = ['Você conduz o julgamento e vota. Em caso de empate entre os jurados, o seu voto decide.'];
                    break;
                default:
                    $info = ['Ouça a acusação, a defesa e as testemunhas. Depois, vote em segredo.'];
            }
            $assign[$pid] = ['role' => $role, 'secret' => ['info' => $info]];
        }
        return [
            'phase' => 'briefing',
            'seconds' => self::BRIEFING,
            'prompt_key' => $caseId,
            'public' => ['case' => $caseId, 'title' => $case['title'], 'charge' => $case['charge'], 'roles' => $roleMap],
            'secret' => ['truth' => $truth],
            'players' => $assign,
        ];
    }

    /** Quem fala em cada etapa (sem advogado, o réu faz a defesa e não tem palavra final separada). */
    private function speakers(array $players, string $stage): array
    {
        $hasDefense = false;
        foreach ($players as $rp) {
            $hasDefense = $hasDefense || $rp['role'] === 'defesa';
        }
        $roles = self::STAGES[$stage];
        if ($stage === 'defense' && !$hasDefense) {
            $roles = ['reu'];
        }
        if ($stage === 'final' && !$hasDefense) {
            return [];
        }
        $out = [];
        foreach ($players as $pid => $rp) {
            if (in_array($rp['role'], $roles, true) && $rp['active']) {
                $out[] = (int) $pid;
            }
        }
        return $out;
    }

    /** Abre a próxima etapa com alguém para falar; sem nenhuma, vai para a votação. */
    private function nextStage(array $room, array $round, array $players, ?string $after, int $now): void
    {
        $stages = array_keys(self::STAGES);
        $start = $after === null ? 0 : array_search($after, $stages, true) + 1;
        for ($i = $start; $i < count($stages); $i++) {
            if ($this->speakers($players, $stages[$i])) {
                $this->phase($room, $round, $stages[$i], $this->int($room, 'speech_time', 75), $now);
                return;
            }
        }
        $this->phase($room, $round, 'voting', $this->int($room, 'vote_time', 40), $now);
    }

    protected function step(array $room, array $round, int $now): bool
    {
        $roundId = (int) $round['id'];
        $players = $this->repo()->players($roundId);
        $phase = (string) $round['phase'];
        if ($phase === 'briefing') {
            if (!$this->expired($round, $now)) {
                return false;
            }
            $this->nextStage($room, $round, $players, null, $now);
            return true;
        }
        if (isset(self::STAGES[$phase])) {
            $speakers = $this->speakers($players, $phase);
            if (!$this->expired($round, $now) && $speakers && !$this->allSubmitted($roundId, $speakers, $phase)) {
                return false;
            }
            $this->nextStage($room, $round, $players, $phase, $now);
            return true;
        }
        if ($phase === 'voting') {
            $voters = $this->voterIds($players);
            $voted = array_map('intval', array_column($this->repo()->votes($roundId, 'verdict'), 'voter_id'));
            if (!$this->expired($round, $now) && array_diff($voters, $voted)) {
                return false;
            }
            $this->reveal($room, $round, $players, $now);
            return true;
        }
        if ($phase === 'reveal' && $this->expired($round, $now)) {
            $this->finishRound($room, $round, $now);
            return true;
        }
        return false;
    }

    private function voterIds(array $players): array
    {
        $out = [];
        foreach ($players as $pid => $rp) {
            if ($rp['active'] && in_array($rp['role'], self::VOTERS, true)) {
                $out[] = (int) $pid;
            }
        }
        return $out;
    }

    /**
     * @param array<int, array{role:string, vote:string}> $votes
     * @return array{verdict:string, guilty:int, innocent:int, by_judge:bool}
     */
    public static function verdict(array $votes): array
    {
        $g = 0;
        $i = 0;
        $judge = null;
        foreach ($votes as $v) {
            if ($v['role'] === 'juiz') {
                $judge = $v['vote'];
                continue;
            }
            $v['vote'] === 'guilty' ? $g++ : $i++;
        }
        if ($g !== $i) {
            return ['verdict' => $g > $i ? 'culpado' : 'inocente', 'guilty' => $g, 'innocent' => $i, 'by_judge' => false];
        }
        if ($judge !== null) {
            return ['verdict' => $judge === 'guilty' ? 'culpado' : 'inocente', 'guilty' => $g, 'innocent' => $i, 'by_judge' => true];
        }
        return ['verdict' => 'inocente', 'guilty' => $g, 'innocent' => $i, 'by_judge' => false];
    }

    private function reveal(array $room, array $round, array $players, int $now): void
    {
        $roundId = (int) $round['id'];
        $number = (int) $round['number'];
        $truth = (string) $round['secret']['truth'];
        $votes = [];
        foreach ($this->repo()->votes($roundId, 'verdict') as $v) {
            $pid = (int) $v['voter_id'];
            $votes[] = ['pid' => $pid, 'role' => (string) ($players[$pid]['role'] ?? 'jurado'), 'vote' => (string) $v['target']];
        }
        $result = self::verdict($votes);
        $side = $this->int($room, 'points_side', 3);
        foreach ($players as $pid => $rp) {
            $wins = $result['verdict'] === 'culpado' ? $rp['role'] === 'promotor' : in_array($rp['role'], ['defesa', 'reu'], true);
            if ($wins) {
                $this->ctx->award($room, $number, (int) $pid, $side, 'Venceu o julgamento', "r$number:side");
            }
        }
        foreach ($votes as $v) {
            if (($v['vote'] === 'guilty') === ($truth === 'culpado')) {
                $this->ctx->award($room, $number, $v['pid'], $this->int($room, 'points_truth', 1), 'Votou de acordo com a verdade', "r$number:truth");
            }
        }
        $reu = array_keys(array_filter($players, static function (array $rp): bool {
            return $rp['role'] === 'reu';
        }));
        $public = $round['public'];
        $public['result'] = $result + ['truth' => $truth, 'reu' => self::names($players, $reu)];
        $this->repo()->savePublic($roundId, $public);
        $this->ctx->event($room, $number, $public['title'], sprintf(
            'Veredito: %s (%d x %d)%s. Na verdade, %s era %s.',
            strtoupper($result['verdict']), $result['guilty'], $result['innocent'], $result['by_judge'] ? ', decidido pelo juiz' : '',
            self::names($players, $reu), $truth
        ), "r$number");
        $this->phase($room, $round, 'reveal', $this->int($room, 'reveal_time', 20), $now);
    }

    protected function handle(string $op, array $room, array $round, array $player, Input $in): array
    {
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        $now = time();
        if ($op === 'speak') {
            $stage = (string) $round['phase'];
            if (!isset(self::STAGES[$stage])) {
                throw new ApiException('Não é hora de falar.', 409, 'invalid_state');
            }
            $this->requirePhase($round, $stage, 'Não é hora de falar.');
            $this->requireParticipant($round, $player);
            if (!in_array($pid, $this->speakers($this->repo()->players($roundId), $stage), true)) {
                throw new ApiException('Esta etapa não é sua: aguarde a sua vez de falar.', 403, 'not_your_turn');
            }
            $text = Text::limited($in->string('text', 3000, 'fala'), self::MIN_LEN, self::MAX_LEN, 'A fala');
            $this->repo()->addEntry($roundId, $pid, $stage, $text, [], $now);
            $this->ctx->bump((int) $room['id'], $now);
            return ['said' => $text];
        }
        if ($op === 'vote') {
            $this->requirePhase($round, 'voting', 'A votação não está aberta.');
            $rp = $this->requireParticipant($round, $player);
            if (!in_array($rp['role'], self::VOTERS, true)) {
                throw new ApiException('Só jurados, testemunhas e o juiz votam.', 403, 'not_voter');
            }
            $this->repo()->castVote($roundId, $pid, 'verdict', $in->bool('guilty') ? 'guilty' : 'innocent', $now);
            $this->ctx->bump((int) $room['id'], $now);
            return ['voted' => true];
        }
        throw new ApiException('Ação desconhecida.', 400, 'invalid_op');
    }

    protected function roundView(array $room, array $round, array $player, array $roundPlayers): array
    {
        $roundId = (int) $round['id'];
        $pid = (int) $player['id'];
        $phase = (string) $round['phase'];
        $mine = $roundPlayers[$pid] ?? null;
        $cast = [];
        foreach ($roundPlayers as $id => $rp) {
            $cast[] = ['id' => (int) $id, 'nickname' => (string) $rp['nickname'], 'role' => (string) $rp['role'], 'role_name' => self::ROLE_NAMES[$rp['role']] ?? ''];
        }
        // Falas são públicas: é um julgamento.
        $speeches = [];
        foreach (array_keys(self::STAGES) as $stage) {
            foreach ($this->repo()->entries($roundId, $stage) as $e) {
                $speeches[] = ['stage' => $stage, 'nickname' => (string) $e['nickname'], 'role' => self::ROLE_NAMES[$roundPlayers[(int) $e['player_id']]['role'] ?? 'jurado'], 'text' => (string) $e['body']];
            }
        }
        $voters = $this->voterIds($roundPlayers);
        $voted = array_map('intval', array_column($this->repo()->votes($roundId, 'verdict'), 'voter_id'));
        $speakers = isset(self::STAGES[$phase]) ? $this->speakers($roundPlayers, $phase) : [];
        $progress = [];
        foreach ($roundPlayers as $id => $rp) {
            if ($phase === 'voting' && in_array((int) $id, $voters, true)) {
                $progress[(int) $id] = in_array((int) $id, $voted, true) ? 'submitted' : 'pending';
            } elseif ($speakers && in_array((int) $id, $speakers, true)) {
                $progress[(int) $id] = $this->repo()->entryOf($roundId, (int) $id, $phase) ? 'submitted' : 'pending';
            }
        }
        $myVote = $this->repo()->votesOf($roundId, $pid)['verdict'] ?? null;
        $view = [
            'title' => (string) $round['public']['title'],
            'charge' => (string) $round['public']['charge'],
            'cast' => $cast,
            'my_role' => $mine ? ['id' => $mine['role'], 'name' => self::ROLE_NAMES[$mine['role']], 'info' => $mine['secret']['info'] ?? []] : null,
            'speeches' => $speeches,
            'speakers' => $speakers,
            'my_turn' => in_array($pid, $speakers, true) && !$this->repo()->entryOf($roundId, $pid, $phase),
            'can_vote' => $mine !== null && in_array($mine['role'], self::VOTERS, true),
            'my_vote' => $myVote,
            'votes_cast' => count(array_intersect($voted, $voters)),
            'voters' => count($voters),
            'min_len' => self::MIN_LEN,
            'max_len' => self::MAX_LEN,
            'progress' => $progress,
        ];
        if ($phase === 'reveal') {
            $view['result'] = $round['public']['result'] ?? null;
        }
        return $view;
    }
}
