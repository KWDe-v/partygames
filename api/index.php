<?php
/**
 * API JSON do jogo: api/index.php?action=<ação>
 */
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

\App\Controllers\ApiRouter::dispatch();
