<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap/init.php';

if (is_logged_in()) {
    audit_log((int) auth_user()['id'], current_client_id(), 'auth.logout', 'user', (string) auth_user()['id'], 'User signed out');
}
logout_user();
session_name((string) app_config('session.name', 'cdr_processor_session'));
session_start();
flash('success', 'You have been signed out.');
redirect('/login.php');
