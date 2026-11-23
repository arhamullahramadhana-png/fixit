<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('/pages/riwayat.php');
}

$error = '';
$nama = '';
$email = '';

if (is_post()) {
    csrf_verify();
    $nama = post_str('nama');
    $email = strtolower(post_str('email'));
    $password = post_str('password');
    $konfirmasi = post_str('password_confirm');

    if ($nama === '' || $email === '' || $password === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (mb_strlen($nama) < 2) {
        $error = 'Nama minimal 2 karakter.';
    } elseif (strlen($password) < 8) {
        $error = 'Kata sandi minimal 8 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        $cek = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $cek->execute([$email]);

        if ($cek->fetch()) {
            $error = 'Email tersebut sudah terdaftar. Silakan masuk.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO users (nama, email, password_hash, role) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$nama, $email, password_hash($password, PASSWORD_DEFAULT), 'user']);
            $userId = (int) $pdo->lastInsertId();

            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'    => $userId,
                'nama'  => $nama,
                'email' => $email,
                'role'  => 'user',
            ];

            flash('success', 'Akun berhasil dibuat. Selamat datang di FIXIT!');
            redirect('/pages/riwayat.php');
        }
    }
}

$pageTitle = 'Daftar';
require __DIR__ . '/../includes/header.php';
?>

<div class="container auth-wrap">
    <div class="auth-card">
        <h1>Buat akun FIXIT</h1>
        <p>Dengan akun, hasil diagnosis otomatis tersimpan dan bisa dibuka kembali kapan saja.</p>

        <?php if ($error): ?>
            <div class="flash flash-error" style="margin-bottom:1.2rem"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>

            <div class="field">
                <label for="nama">Nama</label>
                <input type="text" id="nama" name="nama" value="<?= e($nama) ?>" autocomplete="name" required>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
            </div>

            <div class="field">
                <label for="password">Kata sandi</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" autocomplete="new-password" required>
                    <button type="button" class="password-toggle" data-password-toggle="password">Tampilkan</button>
                </div>
                <span class="hint">Minimal 8 karakter.</span>
            </div>

            <div class="field">
                <label for="password_confirm">Ulangi kata sandi</label>
                <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Daftar</button>
        </form>

        <div class="auth-foot">
            Sudah punya akun? <a href="<?= url('/pages/login.php') ?>">Masuk</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
