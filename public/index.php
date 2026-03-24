<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap/init.php';
redirect(is_logged_in() ? '/dashboard.php' : '/login.php');
