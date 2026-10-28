<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Erro esperado da API: a mensagem é exibida ao jogador.
 */
final class ApiException extends \RuntimeException
{
    /** @var int */
    private $status;
    /** @var string */
    private $errorCode;

    public function __construct(string $message, int $status = 400, string $errorCode = 'bad_request')
    {
        parent::__construct($message);
        $this->status = $status;
        $this->errorCode = $errorCode;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
