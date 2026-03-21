<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/vendor/autoload.php';
require dirname(__DIR__, 3) . '/config/paths.php';

use josegonzalez\Dotenv\Loader;
use function Cake\Core\env;

if (!env('APP_NAME') && file_exists(CONFIG . '.env')) {
    $dotenv = new Loader([CONFIG . '.env']);
    $dotenv->parse()
        ->putenv()
        ->toEnv()
        ->toServer();
}

$agentEnabled = filter_var(
    env('WATCHER_AGENT_ENABLED', false),
    FILTER_VALIDATE_BOOLEAN,
);

if ($agentEnabled) {
    require __DIR__ . '/status-agent.inc.php';
} else {
    require __DIR__ . '/status-local.inc.php';
}
