<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_post()) {
    csrf_verify();
    unset($_SESSION['user'], $_SESSION['chat']);
    session_regenerate_id(true);
    flash('success', 'Anda telah keluar.');
}

redirect('/index.php');
