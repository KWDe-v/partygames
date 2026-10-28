<?php
declare(strict_types=1);

namespace App\Platform;

use App\Core\Input;

/**
 * Base de todo jogo da plataforma.
 *
 * A plataforma cuida de salas, entrada/saída, anfitrião, presença, reconexão, segurança e
 * polling. O jogo só implementa as próprias regras. Todos os métodos abaixo são chamados com a
 * sala BLOQUEADA dentro de uma transação, então o jogo não precisa se preocupar com concorrência.
 *
 * Ciclo de vida: lobby ──start()──▶ playing (fases do jogo, via ctx->setPhase) ──▶ finished
 * Prazos: o jogo grava o fim da fase atual em ctx->setPhase(..., $endsAt); quando ele vence,
 * a plataforma chama advance(). advance() também é chamado após cada action(), para encerrar
 * etapas antecipadamente (ex.: todos já responderam).
 */
abstract class AbstractGame
{
    /** @var GameContext */
    protected $ctx;
    /** @var array<string, mixed> */
    protected $manifest;

    final public function __construct(GameContext $ctx, array $manifest)
    {
        $this->ctx = $ctx;
        $this->manifest = $manifest;
    }

    public function slug(): string
    {
        return (string) $this->manifest['slug'];
    }

    /** Validação extra das configurações (os limites básicos já vêm do manifesto). */
    public function normalizeSettings(array $settings): array
    {
        return $settings;
    }

    /** Fases em que novos jogadores podem entrar (participam a partir da próxima rodada). */
    public function joinablePhases(): array
    {
        return [];
    }

    /** Sai do lobby e abre a primeira fase. */
    abstract public function start(array $room, int $now): void;

    /**
     * Ação de um jogador (enviar resposta, votar, avançar...). Lança ApiException para recusar.
     *
     * @return array dados devolvidos ao navegador
     */
    abstract public function action(string $op, array $room, array $player, Input $in): array;

    /** Transição de fase se o prazo venceu ou se a etapa já pode terminar. Retorna true se mudou algo. */
    public function advance(array $room, int $now): bool
    {
        return false;
    }

    /** Jogador saiu ou abandonou a sala no meio da partida. */
    public function playerLeft(array $room, int $playerId, int $now): void
    {
    }

    /** A sala foi encerrada (anfitrião, expiração, limite de rodadas). */
    public function finished(array $room, int $now): void
    {
    }

    /**
     * Visão do jogo para um jogador. Nunca inclua dados secretos de outros jogadores.
     * A chave opcional 'progress' (player_id => status) aparece na lista de jogadores.
     */
    abstract public function view(array $room, array $player, array $players): array;
}
