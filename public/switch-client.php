<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap/init.php';
require_login();

if (!is_post()) {
    redirect('/dashboard.php');
}

validate_csrf();

$clientId = (int) ($_POST['current_client_id'] ?? 0);
if ($clientId > 0 && can_access_client($clientId)) {
    set_current_client_context($clientId);
    audit_log((int) auth_user()['id'], $clientId, 'client.switch_context', 'client', (string) $clientId, 'Switched current client context');
    flash('success', 'Client context updated.');
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? base_url('/dashboard.php')));
exit;
