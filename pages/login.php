<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('/pages/riwayat.php');
}

$error = '';
$email = '';

if (is_post()) {
    csrf_verify();
    $email = strtolower(post_str('email'));
    $password = post_str('password');

    if ($email === '' || $password === '') {
        $error = 'Email dan kata sandi wajib diisi.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'    => (int) $user['id'],
                'nama'  => $user['nama'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ];

            $target = $_SESSION['redirect_after_login'] ?? url('/pages/riwayat.php');
            unset($_SESSION['redirect_after_login']);
            flash('success', 'Selamat datang kembali, ' . $user['nama'] . '.');
            header('Location: ' . $target);
            exit;
        }

        $error = 'Email atau kata sandi tidak cocok.';
    }
}

$pageTitle = 'Masuk';
require __DIR__ . '/../includes/header.php';
?>

<div class="container auth-wrap">
    <div class="auth-card">
        <h1>Masuk ke FIXIT</h1>
        <p>Simpan riwayat diagnosis dan akses fitur yang membutuhkan akun.</p>

        <?php if ($error): ?>
            <div class="flash flash-error" style="margin-bottom:1.2rem"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>

            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
            </div>

            <div class="field">
                <label for="password">Kata sandi</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                    <button type="button" class="password-toggle" data-password-toggle="password">Tampilkan</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Masuk</button>
        </form>

        <div class="auth-foot">
            Belum punya akun? <a href="<?= url('/pages/daftar.php') ?>">Daftar sekarang</a>
        </div>

        <div class="form-note" style="margin-top:1rem;margin-bottom:0">
            Akun contoh untuk mencoba: <span class="mono">user@fixit.test</span> / <span class="mono">user123</span>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
