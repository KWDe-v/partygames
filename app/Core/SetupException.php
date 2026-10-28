<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Falha de configuração/inicialização. A mensagem é segura para ser exibida ao público:
 * nunca contém credenciais, caminhos internos ou detalhes do banco.
 */
final class SetupException extends \RuntimeException
{
}
