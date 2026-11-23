<?php

declare(strict_types=1);

/** Wajib masuk sebelum mengakses halaman. */
function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? url('/pages/riwayat.php');
        flash('error', 'Silakan masuk terlebih dahulu untuk membuka halaman ini.');
        redirect('/pages/login.php');
    }
}

/** Wajib memiliki peran admin. */
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        flash('error', 'Halaman tersebut hanya dapat diakses oleh admin.');
        redirect('/index.php');
    }
}
