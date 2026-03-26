<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/middleware/require_role.php';
require_once APP_PATH . '/repositories/PhoneSystemRepository.php';
require_once APP_PATH . '/repositories/CdrImportRunRepository.php';

require_phone_system_admin_access();
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$repo = new PhoneSystemRepository();
$eventRepo = new CdrImportRunRepository();
$phoneSystem = $repo->findVisibleById($id, auth_user());
if ($phoneSystem === null) {
    http_response_code(404);
    exit('Phone system not found.');
}

$host = trim((string) ($phoneSystem['host'] ?? ''));
$port = (int) ($phoneSystem['port'] ?? 0);
$timeout = max(2, (int) ($phoneSystem['socket_timeout_seconds'] ?? 10));

if ($host === '' || $port <= 0) {
    $message = 'Socket host and port must be configured before testing the connection.';
    $repo->recordConnectionTest($id, false, $message);
    $eventRepo->logListenerEvent((int) $phoneSystem['id'], (int) $phoneSystem['client_id'], 'manual_socket_test', 'error', $message);
    flash('error', $message);
    redirect('phone-systems/index.php');
}

$errorNumber = 0;
$errorText = '';
$stream = @fsockopen($host, $port, $errorNumber, $errorText, $timeout);

if (!is_resource($stream)) {
    $message = sprintf('Passive socket connection failed: %s (%d).', $errorText !== '' ? $errorText : 'Unknown error', $errorNumber);
    $repo->recordConnectionTest($id, false, $message);
    $eventRepo->logListenerEvent((int) $phoneSystem['id'], (int) $phoneSystem['client_id'], 'manual_socket_test', 'error', $message, [
        'host' => $host,
        'port' => $port,
        'timeout_seconds' => $timeout,
    ]);
    audit_log(auth_user_id(), (int) $phoneSystem['client_id'], 'phone_system_tested', 'phone_system', (string) $id, 'Passive socket test failed for ' . $phoneSystem['system_name'], [
        'result' => 'failed',
        'host' => $host,
        'port' => $port,
        'message' => $message,
    ]);
    flash('error', $message);
    redirect('phone-systems/index.php');
}

stream_set_timeout($stream, $timeout);
fclose($stream);

$message = sprintf('Passive socket connection succeeded to %s:%d.', $host, $port);
$repo->recordConnectionTest($id, true, $message);
$eventRepo->logListenerEvent((int) $phoneSystem['id'], (int) $phoneSystem['client_id'], 'manual_socket_test', 'info', $message, [
    'host' => $host,
    'port' => $port,
    'timeout_seconds' => $timeout,
]);
audit_log(auth_user_id(), (int) $phoneSystem['client_id'], 'phone_system_tested', 'phone_system', (string) $id, 'Passive socket test succeeded for ' . $phoneSystem['system_name'], [
    'result' => 'success',
    'host' => $host,
    'port' => $port,
]);
flash('success', $message);
redirect('phone-systems/index.php');
