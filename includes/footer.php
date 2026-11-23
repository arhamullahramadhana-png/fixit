</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand brand-light" href="<?= url('/index.php') ?>">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.3 2.3-2.7-.7-.7-2.7 2.7-2.7z"/>
                    </svg>
                </span>
                <span class="brand-text">FIXIT</span>
            </a>
            <p>Kenali gejalanya, temukan penyebabnya, mulai dari solusi awal.</p>
        </div>

        <div class="footer-col">
            <h2>Jelajahi</h2>
            <a href="<?= url('/pages/diagnosis.php') ?>">Diagnosis gejala</a>
            <a href="<?= url('/pages/chat.php') ?>">Chatbot konsultasi</a>
            <a href="<?= url('/pages/artikel.php') ?>">Artikel panduan</a>
            <?php if (is_logged_in()): ?>
                <a href="<?= url('/pages/riwayat.php') ?>">Riwayat diagnosis</a>
            <?php endif; ?>
        </div>

        <div class="footer-col footer-safety">
            <h2>Catatan keselamatan</h2>
            <p>Hasil FIXIT adalah perkiraan awal, bukan diagnosis pasti. Untuk kerusakan berisiko seperti baterai menggembung, bau terbakar, atau percikan listrik, hentikan pemakaian dan hubungi teknisi.</p>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>&copy; <?= date('Y') ?> FIXIT. Proyek pembelajaran pemrograman web.</span>
        <span>Dibuat dengan PHP dan MySQL.</span>
    </div>
</footer>

<script src="<?= asset('/assets/js/main.js') ?>" defer></script>
<?php if (!empty($withChatScript)): ?>
    <script src="<?= asset('/assets/js/chat.js') ?>" defer></script>
<?php endif; ?>
</body>
</html>
