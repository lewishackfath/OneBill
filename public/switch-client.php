<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';

if (!submitted('POST')) {
    redirect('dashboard.php');
}

verify_csrf();

$clientId = (int) ($_POST['current_client_id'] ?? 0);
$redirectTo = (string) ($_POST['redirect_to'] ?? base_url('dashboard.php'));

if (!auth_has_client_access($clientId)) {
    flash('error', 'That client is not available for your account.');
    redirect('dashboard.php');
}

set_current_client_id($clientId);
flash('success', 'Current client updated.');
redirect($redirectTo);
