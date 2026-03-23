<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap/init.php';

if (is_logged_in()) {
    audit_log(auth_user_id(), null, 'logout', 'auth', (string) auth_user_id(), 'User signed out');
}

logout_user();
flash('success', 'You have been signed out.');
redirect('login.php');
