<?php
declare(strict_types=1);

use Cake\Http\Client;
use function Cake\Core\env;

set_time_limit(12);

// Validate IP
$ipAddress = $_GET['host'] ?? null;
if (!is_string($ipAddress) || !filter_var($ipAddress, FILTER_VALIDATE_IP)) {
    http_response_code(400);
    exit;
}

$agentUrl = rtrim((string)env('WATCHER_AGENT_URL'), '/');
$agentToken = (string)env('WATCHER_AGENT_TOKEN');

$unknown = false;

try {
    // Create HTTP client
    $http = new Client([
        'headers' => [
            'Authorization' => 'Bearer ' . $agentToken,
            'Accept' => 'application/json',
        ],
        'timeout' => 11,
    ]);

    $response = $http->post(
        $agentUrl . '/api/ping',
        [
            'host' => $ipAddress,
            'count' => 10,
            'timeout_ms' => 1000,
        ],
        [
            'type' => 'json',
        ],
    );
} catch (Throwable $e) {
    $unknown = true;
}

// Output headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Content-Type: image/png');

// Handle non‑200 responses
if ($unknown || !$response->isOk()) {
    readfile(__DIR__ . '/unknown.png');
    exit;
}

$data = $response->getJson();

// Render status image
if (!empty($data['reachable']) && ($data['loss_pct'] ?? 100) === 0) {
    readfile(__DIR__ . '/up.png');
} elseif (!empty($data['reachable'])) {
    readfile(__DIR__ . '/bad.png');
} else {
    readfile(__DIR__ . '/down.png');
}
