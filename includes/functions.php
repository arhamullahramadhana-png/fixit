<?php

declare(strict_types=1);

/** Escape output untuk mencegah XSS. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Bangun URL internal aplikasi. */
function url(string $path = ''): string
{
    if ($path === '') {
        return BASE_URL . '/';
    }
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Alias untuk aset statis, dengan versi berdasarkan waktu ubah berkas. */
function asset(string $path): string
{
    $url = url($path);
    $file = dirname(__DIR__) . '/' . ltrim($path, '/');

    if (is_file($file)) {
        $url .= '?v=' . filemtime($file);
    }

    return $url;
}

/** Redirect ke halaman internal. */
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Simpan pesan sekali tampil. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/** Token CSRF. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
}

/** Data pengguna yang sedang masuk. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']['id']);
}

function is_admin(): bool
{
    return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'admin';
}

/** Nilai POST sebagai string aman. */
function post_str(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

/** Nilai POST sebagai integer. */
function post_int(string $key, int $default = 0): int
{
    return (int) ($_POST[$key] ?? $default);
}

/** Kumpulan id integer dari input checkbox. */
function post_ids(string $key): array
{
    $value = $_POST[$key] ?? [];
    if (!is_array($value)) {
        return [];
    }
    return array_values(array_unique(array_filter(array_map('intval', $value))));
}

function format_tanggal(?string $datetime, bool $dengan_jam = false): string
{
    if (!$datetime) {
        return '-';
    }
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    $hasil = date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    if ($dengan_jam) {
        $hasil .= ', ' . date('H.i', $ts);
    }
    return $hasil;
}

function excerpt(string $html, int $limit = 150): string
{
    $teks = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
    if (mb_strlen($teks) <= $limit) {
        return $teks;
    }
    return rtrim(mb_substr($teks, 0, $limit)) . '...';
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

function score_persen(float|string $score): int
{
    return (int) round((float) $score * 100);
}

/** Label dan kelas CSS untuk tingkat risiko. */
function risk_meta(string $level): array
{
    return match ($level) {
        'tinggi' => ['label' => 'Risiko tinggi', 'class' => 'risk-tinggi'],
        'rendah' => ['label' => 'Risiko rendah', 'class' => 'risk-rendah'],
        default  => ['label' => 'Risiko sedang', 'class' => 'risk-sedang'],
    };
}

/** Ikon SVG kategori berdasarkan slug. */
function category_icon(string $slug): string
{
    $paths = [
        'monitor' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
        'battery' => '<rect x="2" y="7" width="16" height="10" rx="2"/><path d="M22 11v2"/><path d="M6 11v2M10 11v2"/>',
        'gauge' => '<path d="M12 14l4-4"/><path d="M3.5 18a9 9 0 1 1 17 0"/><circle cx="12" cy="14" r="1.5"/>',
        'disk' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 4v6h10V4M7 16h10"/>',
        'wifi' => '<path d="M5 12.5a10 10 0 0 1 14 0M8 16a6 6 0 0 1 8 0"/><circle cx="12" cy="19" r="1"/>',
    ];
    $path = $paths[$slug] ?? '<circle cx="12" cy="12" r="8"/>';
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" '
        . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

/** URL gambar artikel dengan cadangan placeholder. */
function article_image_url(?string $gambar): string
{
    return asset($gambar ?: '/assets/img/artikel-placeholder.svg');
}

/** Tandai menu navigasi yang aktif. Menerima satu atau beberapa nama file. */
function nav_active(string ...$files): string
{
    return in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), $files, true) ? ' is-active' : '';
}

/** Bungkus pesan galat validasi. */
function validasi_gagal(string $pesan, string $kembali): never
{
    flash('error', $pesan);
    redirect($kembali);
}

/**
 * Susun ringkasan diagnosis sebagai teks polos untuk disalin atau dibagikan.
 *
 * @param array $items  hasil diagnosis, masing-masing berisi nama_penyebab, skor, dan solusi
 * @param array $gejala daftar gejala yang dipilih (memiliki kunci pertanyaan)
 */
function diagnosis_summary_text(array $items, array $gejala, ?string $waktu = null): string
{
    $baris = ['FIXIT - Ringkasan Diagnosis'];

    if ($waktu) {
        $baris[] = 'Waktu: ' . format_tanggal($waktu, true);
    }

    $baris[] = '';
    $baris[] = 'Gejala yang dipilih:';
    foreach ($gejala as $item) {
        $baris[] = '- ' . $item['pertanyaan'];
    }

    $baris[] = '';
    $baris[] = 'Kemungkinan penyebab:';
    foreach ($items as $index => $item) {
        $baris[] = ($index + 1) . '. ' . $item['nama_penyebab']
            . ' (' . score_persen($item['skor']) . '%)';
        foreach (($item['solusi'] ?? []) as $langkah) {
            $baris[] = '   - ' . $langkah['langkah'];
        }
    }

    $baris[] = '';
    $baris[] = 'Catatan: hasil ini adalah perkiraan awal, bukan diagnosis pasti.';
    $baris[] = 'Untuk kerusakan berisiko seperti baterai menggembung atau bau terbakar, hentikan pemakaian dan hubungi teknisi.';

    return implode("\n", $baris);
}
