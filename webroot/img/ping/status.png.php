<?php
declare(strict_types=1);

if (isset($_GET['host']) && filter_var($_GET['host'], FILTER_VALIDATE_IP)) {
    $ip = $_GET['host'];
} else {
    http_response_code(400);
    die;
}

$ping = shell_exec('ping -c 10 -W 1 -f -i 0.2 ' . $ip);

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Content-type: image/png');

if (strpos($ping, ' 0% packet loss')) {
    include './up.png';
} elseif (strpos($ping, ' 100% packet loss')) {
    include './down.png';
} else {
    include './bad.png';
}
