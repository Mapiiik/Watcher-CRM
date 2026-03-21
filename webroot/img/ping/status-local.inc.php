<?php
declare(strict_types=1);

// Prevent the script from running indefinitely
set_time_limit(5);

// Validate the 'host' GET parameter as a valid IP address
$ipAddress = $_GET['host'] ?? null;
if (!is_string($ipAddress) || !filter_var($ipAddress, FILTER_VALIDATE_IP)) {
    http_response_code(400); // Bad Request
    exit('Invalid IP address');
}

// Build the ping command safely to avoid shell injection
$cmd = sprintf(
    'ping -c 10 -W 1 -f -i 0.2 %s 2>&1',
    escapeshellarg($ipAddress), // Escape the IP address for shell safety
);

// Execute the ping command and capture the output
$pingOutput = shell_exec($cmd) ?: '';

// Set HTTP headers to prevent caching and indicate PNG image output
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Content-Type: image/png');

// Choose the correct image based on ping results
if (strpos($pingOutput, ' 0% packet loss') !== false) {
    // Host is reachable
    readfile(__DIR__ . '/up.png');
} elseif (strpos($pingOutput, ' 100% packet loss') !== false) {
    // Host is unreachable
    readfile(__DIR__ . '/down.png');
} else {
    // Partial packet loss or unexpected result
    readfile(__DIR__ . '/bad.png');
}
