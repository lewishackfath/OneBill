<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap/init.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

redirect('login.php');
