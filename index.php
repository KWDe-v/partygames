<?php
/**
 * PartyGames — plataforma de jogos para jogar com os amigos. Ponto de entrada da interface.
 * Na primeira visita o banco é preparado automaticamente (ver app/Core/Migrator.php).
 */
declare(strict_types=1);

define('APP_ROOT', __DIR__);
require APP_ROOT . '/app/bootstrap.php';

\App\Controllers\PageController::handle();
