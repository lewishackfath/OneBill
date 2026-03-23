<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
http_response_code(501);
echo 'Not implemented yet.';
