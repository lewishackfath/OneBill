<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/middleware/require_role.php';
require_once APP_PATH . '/repositories/PhoneSystemRepository.php';

require_phone_system_admin_access();
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$repo = new PhoneSystemRepository();
$phoneSystem = $repo->findVisibleById($id, auth_user());
if ($phoneSystem === null) {
    http_response_code(404);
    exit('Phone system not found.');
}

$repo->recordConnectionTest($id, true);
audit_log(auth_user_id(), (int) $phoneSystem['client_id'], 'phone_system_tested', 'phone_system', (string) $id, 'Recorded connection test for phone system ' . $phoneSystem['system_name'], [
    'result' => 'placeholder_success',
]);
flash('success', 'Connection test placeholder recorded successfully. Live 3CX connectivity checks will be wired in during the CDR integration step.');
redirect('phone-systems/index.php');
