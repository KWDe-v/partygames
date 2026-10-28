<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Models\Player;
use App\Models\Room;

/**
 * Identifica o jogador exclusivamente pela sessão do servidor — nunca por dados enviados pelo navegador.
 */
final class Auth
{
    /** @return array<string, mixed>|null linha do jogador ativo da sessão */
    public static function current(Database $db): ?array
    {
        $pid = $_SESSION['player_id'] ?? null;
        if (!is_int($pid) || $pid <= 0) {
            return null;
        }
        $player = (new Player($db))->find($pid);
        if ($player === null || $player['status'] !== 'active') {
            self::logout();
            return null;
        }
        return $player;
    }

    public static function login(array $player): void
    {
        Session::regenerate();
        $_SESSION['player_id'] = (int) $player['id'];
        $_SESSION['room_id'] = (int) $player['room_id'];
    }

    public static function logout(): void
    {
        unset($_SESSION['player_id'], $_SESSION['room_id']);
    }

    /** Registra atividade; se o jogador voltou de um estado desconectado, a lista de todos é atualizada. */
    public static function touch(Database $db, array $player): void
    {
        $now = time();
        if ((new Player($db))->touch($player, $now)) {
            (new Room($db))->bump((int) $player['room_id'], $now);
        }
    }

    public static function newToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', 'player-token|' . $token);
    }

    public static function identityHash(): string
    {
        return hash('sha256', 'session-identity|' . Session::identity());
    }
}
