<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Config;
use App\Core\Database;
use App\Core\Input;
use App\Core\Request;
use App\Core\Text;
use App\Models\Player;
use App\Models\Room;
use App\Platform\GameRegistry;
use App\Platform\RoomEngine;
use App\Platform\Settings;

/**
 * Criação de salas (de qualquer jogo), entrada, reconexão e saída.
 */
final class RoomService
{
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /** @var Database */
    private $db;
    /** @var Room */
    private $rooms;
    /** @var Player */
    private $players;
    /** @var RoomEngine */
    private $engine;
    /** @var RateLimiter */
    private $limiter;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->rooms = new Room($db);
        $this->players = new Player($db);
        $this->engine = new RoomEngine($db);
        $this->limiter = new RateLimiter($db);
    }

    /** @return array{code:string, token:string, nickname:string, game:string} */
    public function create(Input $in): array
    {
        $ip = Request::ip();
        $bucket = 'create|' . $ip;
        $window = Config::int('security.create_window', 600);
        $this->limiter->assertAllowed($bucket, Config::int('security.create_max_attempts', 10), $window);

        $nickname = Text::nickname($in->string('nickname', 200, 'apelido'));
        $password = $this->validatePassword($in->string('password', 300, 'senha'));
        $name = $this->roomName($in, $nickname);
        [$slug, $minPlayers, $maxPlayers, $settings] = $this->gameRules($in);

        $this->limiter->hit($bucket, $window);
        $this->leaveCurrentRoom();

        $token = Auth::newToken();
        $result = $this->db->transaction(function () use ($name, $slug, $nickname, $password, $minPlayers, $maxPlayers, $settings, $token): array {
            $now = time();
            $code = $this->generateCode();
            $roomId = $this->rooms->create([
                'code' => $code,
                'name' => $name,
                'game' => $slug,
                'password_hash' => $password !== null ? password_hash($password, PASSWORD_DEFAULT) : null,
                'min_players' => $minPlayers,
                'max_players' => $maxPlayers,
                'settings' => $settings,
                'now' => $now,
            ]);
            $playerId = $this->players->create([
                'room_id' => $roomId,
                'nickname' => $nickname,
                'token_hash' => Auth::hashToken($token),
                'identity_hash' => Auth::identityHash(),
                'now' => $now,
            ]);
            $this->rooms->update($roomId, ['host_player_id' => $playerId, 'sweep_at' => $now + 5]);
            return ['code' => $code, 'player' => $this->players->find($playerId)];
        });

        Auth::login($result['player']);
        return ['code' => $result['code'], 'token' => $token, 'nickname' => $nickname, 'game' => $slug];
    }

    /** Anfitrião troca o jogo e/ou as regras enquanto a sala está no lobby. */
    public function configure(array $player, Input $in): array
    {
        [$slug, $minPlayers, $maxPlayers, $settings] = $this->gameRules($in);
        $this->engine->withRoomLock((int) $player['room_id'], function (array $room) use ($player, $slug, $minPlayers, $maxPlayers, $settings): void {
            if ((int) $room['host_player_id'] !== (int) $player['id']) {
                throw new ApiException('Apenas o anfitrião pode fazer isso.', 403, 'not_host');
            }
            if ($room['status'] !== 'lobby') {
                throw new ApiException('O jogo só pode ser trocado no lobby, entre as partidas.', 409, 'invalid_state');
            }
            if ($this->players->countActive((int) $room['id']) > $maxPlayers) {
                throw new ApiException('Há mais jogadores na sala do que o máximo escolhido.', 422, 'invalid_input');
            }
            $this->rooms->update((int) $room['id'], [
                'game' => $slug,
                'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
                'min_players' => $minPlayers,
                'max_players' => $maxPlayers,
                'notice' => null,
            ]);
            $this->rooms->bump((int) $room['id'], time());
        });
        return ['game' => $slug];
    }

    /**
     * Jogo escolhido, limites de jogadores (os do jogo, nunca acima do teto da plataforma) e
     * configurações validadas pelo manifesto do jogo.
     *
     * @return array{0:string, 1:int, 2:int, 3:array}
     */
    private function gameRules(Input $in): array
    {
        $slug = $in->string('game', 60, 'jogo');
        $manifest = GameRegistry::get($slug);
        $cap = max(2, min(50, Config::int('platform.max_players_limit', 20)));
        $gameMin = max(2, (int) $manifest['players'][0]);
        $gameMax = max($gameMin, min($cap, (int) $manifest['players'][1]));
        $minPlayers = $in->int('min_players', $gameMin, $gameMax, max($gameMin, (int) ($manifest['min_players_default'] ?? $gameMin)), 'mínimo de jogadores');
        $maxPlayers = $in->int('max_players', $gameMin, $gameMax, min($gameMax, (int) $manifest['max_players_default']), 'máximo de jogadores');
        if ($minPlayers > $maxPlayers) {
            throw new ApiException('O mínimo de jogadores não pode ser maior que o máximo.', 422, 'invalid_input');
        }
        $settings = Settings::validate($manifest, $in);
        $settings = $this->engine->game(['game' => $slug])->normalizeSettings($settings);
        return [$slug, $minPlayers, $maxPlayers, $settings];
    }

    private function roomName(Input $in, string $nickname): string
    {
        $raw = $in->has('room_name') ? Text::normalize($in->string('room_name', 300, 'nome da sala')) : '';
        if ($raw === '') {
            return Text::truncate('Sala de ' . $nickname, 60);
        }
        return Text::limited($raw, 3, 40, 'O nome da sala');
    }

    /** @return array{code:string, token:string, nickname:string, reconnected:bool} */
    public function join(Input $in): array
    {
        $code = Text::roomCode($in->string('code', 32, 'código da sala'));
        $password = $in->string('password', 300, 'senha');
        $nicknameRaw = $in->string('nickname', 200, 'apelido');

        $ip = Request::ip();
        $roomBucket = 'join|' . $ip . '|' . $code;
        $globalBucket = 'join|' . $ip;
        $roomWindow = Config::int('security.join_window', 300);
        $globalWindow = Config::int('security.join_global_window', 900);
        $this->limiter->assertAllowed($roomBucket, Config::int('security.join_max_attempts', 8), $roomWindow);
        $this->limiter->assertAllowed($globalBucket, Config::int('security.join_global_max_attempts', 30), $globalWindow);

        $room = $this->rooms->findByCode($code);
        if ($room !== null && $room['password_hash'] === null) {
            $valid = true; // sala sem senha: basta o código
        } else {
            // password_verify também roda quando a sala não existe, para não revelar quais códigos existem.
            $hash = $room['password_hash'] ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $valid = password_verify($password, $hash) && $room !== null;
        }
        if (!$valid) {
            $this->limiter->hit($roomBucket, $roomWindow);
            $this->limiter->hit($globalBucket, $globalWindow);
            throw new ApiException('Sala não encontrada ou senha incorreta.', 403, 'invalid_credentials');
        }
        $this->limiter->clear($roomBucket);
        if ($room['status'] === 'finished') {
            throw new ApiException('Esta partida já foi encerrada.', 410, 'room_finished');
        }

        $nickname = Text::nickname($nicknameRaw);
        $current = Auth::current($this->db);
        if ($current !== null && (int) $current['room_id'] !== (int) $room['id']) {
            $this->leaveCurrentRoom();
        }

        $token = Auth::newToken();
        $result = $this->engine->withRoomLock((int) $room['id'], function (array $room) use ($nickname, $token): array {
            $now = time();
            $roomId = (int) $room['id'];
            if ($room['status'] === 'finished') {
                throw new ApiException('Esta partida já foi encerrada.', 410, 'room_finished');
            }
            $existing = $this->players->findByIdentity($roomId, Auth::identityHash());

            if ($existing !== null) {
                // Mesma identidade de sessão: reconecta ao registro existente, nunca duplica o jogador.
                if ($existing['status'] !== 'active') {
                    $this->assertCanEnter($room, true);
                }
                if ($this->players->nicknameTaken($roomId, $nickname, (int) $existing['id'])) {
                    $nickname = $existing['nickname'];
                }
                $this->players->update((int) $existing['id'], [
                    'nickname' => $nickname,
                    'token_hash' => Auth::hashToken($token),
                    'status' => 'active',
                    'connection' => 'online',
                    'last_seen' => $now,
                    'left_at' => null,
                ]);
                $playerId = (int) $existing['id'];
                $reconnected = $existing['status'] === 'active';
            } else {
                $this->assertCanEnter($room, false);
                if ($this->players->nicknameTaken($roomId, $nickname)) {
                    throw new ApiException('Já existe um jogador com esse apelido na sala. Escolha outro.', 409, 'nickname_taken');
                }
                $playerId = $this->players->create([
                    'room_id' => $roomId,
                    'nickname' => $nickname,
                    'token_hash' => Auth::hashToken($token),
                    'identity_hash' => Auth::identityHash(),
                    'now' => $now,
                ]);
                $reconnected = false;
            }
            if (!$room['host_player_id']) {
                $this->rooms->update($roomId, ['host_player_id' => $playerId]);
            }
            $this->rooms->bump($roomId, $now);
            return ['player' => $this->players->find($playerId), 'reconnected' => $reconnected];
        });

        Auth::login($result['player']);
        return [
            'code' => $code,
            'token' => $token,
            'nickname' => (string) $result['player']['nickname'],
            'reconnected' => $result['reconnected'],
        ];
    }

    /**
     * Recupera a sessão com o token salvo no navegador (ex.: sessão expirada ou navegador reiniciado).
     *
     * @return array{code:string, token:string, nickname:string}
     */
    public function reconnect(Input $in): array
    {
        $code = Text::roomCode($in->string('code', 32, 'código da sala'));
        $token = $in->string('token', 128, 'token');
        $bucket = 'reconnect|' . Request::ip();
        $window = Config::int('security.reconnect_window', 300);
        $this->limiter->assertAllowed($bucket, Config::int('security.reconnect_max_attempts', 20), $window);

        $player = preg_match('/^[a-f0-9]{64}$/', $token) ? $this->players->findByTokenHash(Auth::hashToken($token)) : null;
        $room = $player !== null ? $this->rooms->find((int) $player['room_id']) : null;
        if ($player === null || $room === null || $room['code'] !== $code || $player['status'] === 'left') {
            $this->limiter->hit($bucket, $window);
            throw new ApiException('Não foi possível recuperar sua participação nesta sala.', 403, 'invalid_token');
        }
        if ($room['status'] === 'finished') {
            throw new ApiException('Esta partida já foi encerrada.', 410, 'room_finished');
        }

        $newToken = Auth::newToken();
        $player = $this->engine->withRoomLock((int) $room['id'], function (array $room) use ($player, $newToken): array {
            $now = time();
            $fresh = $this->players->find((int) $player['id']);
            if ($fresh === null || $fresh['status'] === 'left') {
                throw new ApiException('Não foi possível recuperar sua participação nesta sala.', 403, 'invalid_token');
            }
            if ($fresh['status'] === 'abandoned') {
                $this->assertCanEnter($room, true);
            }
            $fields = [
                'token_hash' => Auth::hashToken($newToken),
                'status' => 'active',
                'connection' => 'online',
                'last_seen' => $now,
                'left_at' => null,
            ];
            $other = $this->players->findByIdentity((int) $room['id'], Auth::identityHash());
            if ($other === null) {
                $fields['identity_hash'] = Auth::identityHash();
            }
            $this->players->update((int) $fresh['id'], $fields);
            $this->rooms->bump((int) $room['id'], $now);
            return $this->players->find((int) $fresh['id']);
        });

        Auth::login($player);
        return ['code' => $code, 'token' => $newToken, 'nickname' => (string) $player['nickname']];
    }

    public function leaveCurrentRoom(): void
    {
        $current = Auth::current($this->db);
        if ($current !== null) {
            $this->engine->leave($current);
        }
        Auth::logout();
    }

    private function assertCanEnter(array $room, bool $returning): void
    {
        // Novos jogadores entram no lobby ou nas fases que o jogo permitir (participam da próxima rodada).
        $joinable = $room['status'] === 'lobby'
            || ($room['status'] === 'playing' && in_array($room['phase'], $this->engine->game($room)->joinablePhases(), true));
        if (!$returning && !$joinable) {
            throw new ApiException('Uma rodada está em andamento. Tente entrar novamente quando ela terminar.', 409, 'round_in_progress');
        }
        if ($this->players->countActive((int) $room['id']) >= (int) $room['max_players']) {
            throw new ApiException('A sala está cheia.', 409, 'room_full');
        }
    }

    /** Senha opcional: vazia (ou só espaços) significa sala sem senha. */
    private function validatePassword(string $password): ?string
    {
        if (trim($password) === '') {
            return null;
        }
        $min = Config::int('platform.room_password_min_length', 4);
        $max = Config::int('platform.room_password_max_length', 64);
        if (!preg_match('//u', $password)) {
            throw new ApiException('A senha contém caracteres inválidos.', 422, 'invalid_password');
        }
        $len = Text::length($password);
        if ($len < $min || $len > $max) {
            throw new ApiException(sprintf('A senha da sala deve ter entre %d e %d caracteres.', $min, $max), 422, 'invalid_password');
        }
        return $password;
    }

    private function generateCode(): string
    {
        $alphabetLength = strlen(self::CODE_ALPHABET);
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, $alphabetLength - 1)];
            }
            if (!$this->rooms->codeExists($code)) {
                return $code;
            }
        }
        throw new \RuntimeException('Não foi possível gerar um código de sala único.');
    }
}
