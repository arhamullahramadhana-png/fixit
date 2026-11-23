<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Chatbot konsultasi';
$withChatScript = true;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <nav class="breadcrumb" aria-label="Remah roti">
            <a href="<?= url('/index.php') ?>">Beranda</a>
            <span>/</span>
            <span>Chatbot</span>
        </nav>
        <h1>Ceritakan gejala Anda</h1>
        <p>Tulis dengan bahasa sehari-hari. Chatbot akan menanyakan beberapa gejala tambahan sebelum memberi kemungkinan penyebab.</p>
    </div>
</div>

<div class="container chat-page">
    <div class="chat-shell"
         id="chat-shell"
         data-api="<?= url('/api/chatbot.php') ?>"
         data-token="<?= e(csrf_token()) ?>"
         data-login="<?= url('/pages/login.php') ?>"
         data-riwayat="<?= url('/pages/riwayat.php') ?>">

        <div class="chat-card">
            <div class="chat-header">
                <span class="chat-avatar" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="7" width="16" height="12" rx="3"/>
                        <path d="M9 12h.01M15 12h.01M9 16h6M12 7V4"/>
                    </svg>
                </span>
                <div>
                    <h1>FIXIT Assistant</h1>
                    <p>Konsultasi gejala kerusakan</p>
                </div>
                <span class="chat-progress" id="chat-progress">Pertanyaan 0/5</span>
            </div>

            <div class="chat-body" id="chat-body">
                <div class="chat-messages" id="chat-messages" aria-live="polite"></div>
            </div>

            <form class="chat-input" id="chat-form" autocomplete="off">
                <input type="text" id="chat-input" placeholder="Contoh: laptop menyala tapi layar hitam" aria-label="Tulis pesan" maxlength="500">
                <button type="submit" class="chat-send" aria-label="Kirim pesan">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m4 4 16 8-16 8 3-8-3-8z"/>
                    </svg>
                </button>
            </form>
        </div>

        <aside class="chat-aside">
            <div class="side-card">
                <h2>Cara menulis gejala</h2>
                <ul>
                    <li>Sebutkan kondisi yang paling mengganggu, misalnya layar hitam atau baterai cepat habis.</li>
                    <li>Boleh menambahkan detail: bunyi beep, kipas berputar, atau pesan galat.</li>
                    <li>Jawab pertanyaan lanjutan dengan ya, tidak, atau tidak tahu.</li>
                </ul>
            </div>

            <div class="side-card">
                <h2>Batasan chatbot</h2>
                <p class="small muted mb-0">
                    Chatbot mencocokkan kata kunci dengan daftar gejala. Bila kalimat Anda tidak dikenali,
                    pilih kategori yang muncul atau gunakan halaman diagnosis.
                </p>
                <p class="small" style="margin-top:.7rem;margin-bottom:0">
                    <a href="<?= url('/pages/diagnosis.php') ?>">Buka diagnosis berbasis pilihan gejala &rarr;</a>
                </p>
            </div>

            <div class="side-card">
                <h2>Ingat</h2>
                <p class="small muted mb-0">
                    Hasil konsultasi adalah perkiraan awal. Untuk baterai menggembung, bau terbakar,
                    atau percikan listrik, hentikan pemakaian dan hubungi teknisi.
                </p>
            </div>
        </aside>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
