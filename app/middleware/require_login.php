<?php

declare(strict_types=1);

if (!is_logged_in()) {
    flash('error', 'Please sign in to continue.');
    redirect('login.php');
}
