# LAPORAN PROYEK AKHIR

## FIXIT: Asisten Pemecahan Masalah Perangkat Berbasis Rule-Based dan Certainty Factor

> **Catatan:** Ganti bagian identitas di bawah dengan data Anda sebelum mencetak atau mengumpulkan laporan.

| Item | Keterangan |
|---|---|
| Nama | ................................ |
| NIM | ................................ |
| Program Studi | ................................ |
| Mata Kuliah | Pemrograman Web |
| Dosen Pengampu | ................................ |
| Tahun | 2026 |

---

## Abstrak

FIXIT adalah aplikasi web yang membantu pengguna mengidentifikasi kemungkinan penyebab kerusakan laptop berdasarkan gejala yang dialami. Sistem menggunakan metode *rule-based* dengan pembobotan *certainty factor* untuk menghitung tingkat kecocokan gejala terhadap setiap penyebab. Selain diagnosis berbasis pilihan gejala, FIXIT menyediakan chatbot yang mengenali kata kunci gejala dan menanyakan gejala tambahan sebelum menampilkan hasil. Aplikasi dilengkapi riwayat diagnosis, artikel panduan, umpan balik pengguna, serta panel admin untuk mengelola basis pengetahuan. Pengujian basis pengetahuan dan pengujian black-box menunjukkan seluruh fungsi utama berjalan sesuai rancangan.

**Kata kunci:** sistem pakar, rule-based, certainty factor, diagnosis laptop, chatbot, PHP, MySQL.

---

## BAB I PENDAHULUAN

### 1.1 Latar Belakang

Pengguna laptop sering tidak mengetahui penyebab ketika perangkatnya bermasalah. Mereka cenderung menebak langkah perbaikan, membongkar perangkat tanpa pengetahuan yang cukup, atau langsung membawanya ke servis. Padahal, sebagian masalah dapat dikenali lebih awal dari gejala yang muncul.

FIXIT hadir sebagai pendamping awal yang memandu pengguna dari gejala menuju kemungkinan penyebab dan solusi bertahap. Sistem tidak menggantikan teknisi, tetapi membantu pengguna memahami masalah dan menentukan langkah pertama yang aman.

### 1.2 Rumusan Masalah

1. Bagaimana membangun aplikasi yang dapat memperkirakan penyebab kerusakan laptop berdasarkan gejala?
2. Bagaimana menerapkan metode *rule-based* dan *certainty factor* pada proses diagnosis?
3. Bagaimana menyediakan chatbot yang dapat memahami cerita gejala dengan bahasa sehari-hari?
4. Bagaimana menyediakan pengelolaan basis pengetahuan dan evaluasi hasil diagnosis bagi admin?

### 1.3 Tujuan

1. Membangun aplikasi web FIXIT untuk diagnosis awal kerusakan laptop.
2. Menerapkan perhitungan kecocokan gejala dan penggabungan *certainty factor*.
3. Menyediakan chatbot konsultasi berbasis pencocokan kata kunci.
4. Menyediakan riwayat diagnosis, artikel panduan, umpan balik, dan panel admin.

### 1.4 Batasan Masalah

- Perangkat yang dibahas pada tahap ini adalah **laptop**.
- Basis pengetahuan berisi 5 kategori, 33 gejala, 15 penyebab, dan 30 aturan.
- Hasil diagnosis bersifat **perkiraan awal**, bukan diagnosis pasti.
- Chatbot menggunakan pencocokan kata kunci, bukan model bahasa besar.
- Dukungan PC desktop dan integrasi dengan layanan eksternal berada di luar ruang lingkup.

### 1.5 Manfaat

- **Bagi pengguna:** memperoleh gambaran awal penyebab dan langkah aman yang dapat dicoba.
- **Bagi admin/teknisi:** menyediakan basis aturan yang mudah dikelola dan umpan balik untuk evaluasi bobot.
- **Bagi penulis:** menjadi sarana penerapan sistem pakar sederhana pada aplikasi web.

---

## BAB II LANDASAN TEORI

### 2.1 Sistem Pakar

Sistem pakar adalah sistem komputer yang meniru kemampuan seorang ahli dalam menyelesaikan masalah pada domain tertentu. Sistem pakar umumnya terdiri atas basis pengetahuan, mesin inferensi, dan antarmuka pengguna. Pada FIXIT, basis pengetahuan disimpan di tabel `symptoms`, `causes`, `rules`, `rule_symptoms`, dan `solutions`, sedangkan mesin inferensi diimplementasikan pada kelas `DiagnosisEngine`.

### 2.2 Rule-Based Reasoning

Metode *rule-based* merepresentasikan pengetahuan dalam bentuk aturan **IF–THEN**. Pada FIXIT, setiap aturan menghubungkan sekumpulan gejala dengan satu penyebab dan memiliki bobot kepastian.

Contoh:

> **IF** layar hitam **AND** kipas berputar **AND** lampu indikator menyala **AND** gambar tampil di monitor eksternal
> **THEN** kerusakan panel atau kabel fleksibel layar (bobot 0,80).

### 2.3 Certainty Factor

Certainty factor (CF) digunakan untuk menyatakan tingkat keyakinan suatu aturan. Perhitungan pada FIXIT dilakukan bertahap:

1. **Kecocokan aturan**

   ```text
   kecocokan = jumlah gejala cocok / jumlah gejala yang dibutuhkan aturan
   ```

2. **Skor aturan**

   ```text
   skor aturan = kecocokan x bobot aturan
   ```

3. **Penggabungan beberapa aturan untuk penyebab yang sama**

   ```text
   CF_gabungan = CF1 + CF2 x (1 - CF1)
   ```

4. Penyebab dengan skor di bawah **0,25** tidak ditampilkan sebagai hasil. Hasil diurutkan dari skor tertinggi dan dibatasi maksimal tiga penyebab.

### 2.4 Chatbot Berbasis Kata Kunci

Chatbot FIXIT menormalisasi pesan pengguna (huruf kecil, menghapus tanda baca), lalu mencocokkannya dengan kolom `kata_kunci` pada tabel `symptoms`. Sistem juga mendeteksi negasi sederhana seperti "tidak", "belum", dan "tanpa". Setelah gejala dikenali, chatbot memilih pertanyaan lanjutan yang paling membedakan kandidat penyebab. Konsultasi berhenti ketika skor teratas mencapai **0,75**, jumlah pertanyaan mencapai **5**, atau tidak ada pertanyaan yang relevan.

### 2.5 Teknologi yang Digunakan

| Komponen | Teknologi |
|---|---|
| Bahasa backend | PHP 8 |
| Database | MySQL/MariaDB |
| Frontend | HTML, CSS, JavaScript |
| Server lokal | XAMPP |
| Arsitektur | Server-side rendering + API JSON |
| Keamanan | Session, password hashing, CSRF token, PDO prepared statement, escaping output |

---

## BAB III ANALISIS DAN PERANCANGAN

### 3.1 Kebutuhan Fungsional

| Kode | Kebutuhan |
|---|---|
| F01 | Pengguna dapat melihat kategori dan gejala kerusakan |
| F02 | Pengguna dapat memilih gejala dan memperoleh peringkat penyebab |
| F03 | Sistem menghitung skor dengan rule-based dan certainty factor |
| F04 | Pengguna dapat melihat solusi bertahap dan peringatan teknisi |
| F05 | Pengguna dapat berkonsultasi melalui chatbot |
| F06 | Pengguna dapat mendaftar, login, dan logout |
| F07 | Pengguna yang login dapat menyimpan dan membuka riwayat diagnosis |
| F08 | Pengguna dapat membaca artikel panduan |
| F09 | Pengguna dapat mengirim umpan balik tepat/kurang tepat |
| F10 | Admin dapat mengelola kategori, gejala, penyebab, aturan, solusi, dan artikel |
| F11 | Admin dapat meninjau log chatbot yang tidak dikenali |
| F12 | Admin dapat melihat statistik diagnosis dan rekap umpan balik |

### 3.2 Kebutuhan Non-Fungsional

- **Keamanan:** kata sandi disimpan dengan `password_hash`, query memakai prepared statement, form memakai CSRF token, output di-escape.
- **Kegunaan:** antarmuka responsif, navigasi jelas, dan pesan galat berbahasa Indonesia.
- **Kinerja:** query basis pengetahuan sederhana dan jumlah data kecil sehingga dapat berjalan pada XAMPP.
- **Pemeliharaan:** pemisahan lapisan database (`Repository`), mesin diagnosis (`DiagnosisEngine`), chatbot (`Chatbot`), dan tampilan.

### 3.3 Use Case

**Pengguna umum**

1. Melihat beranda dan kategori.
2. Memilih gejala.
3. Melihat hasil diagnosis dan solusi.
4. Membaca artikel.
5. Berkomunikasi dengan chatbot.
6. Mengirim umpan balik.

**Pengguna terdaftar**

1. Login/daftar.
2. Menyimpan diagnosis.
3. Melihat riwayat dan detailnya.
4. Mengubah umpan balik.

**Admin**

1. Login sebagai admin.
2. Mengelola basis pengetahuan.
3. Meninjau log chatbot.
4. Melihat statistik dan umpan balik.

### 3.4 Arsitektur Aplikasi

```text
Browser
   |
   | HTTP
   v
PHP (index.php, pages/, api/, admin/)
   |
   +-- includes/bootstrap.php  -> session, konfigurasi, repository, engine
   +-- includes/classes/       -> Repository, DiagnosisEngine, Chatbot
   |
   v
MySQL/MariaDB (database fixit)
```

### 3.5 Alur Diagnosis

```text
Mulai
  -> pilih kategori/gejala
  -> kirim daftar gejala
  -> untuk setiap aturan aktif:
       hitung kecocokan dan skor aturan
  -> gabungkan skor per penyebab dengan rumus CF
  -> buang skor < 0,25
  -> urutkan dan ambil maksimal 3
  -> ambil solusi tiap penyebab
  -> simpan diagnosis
  -> tampilkan hasil + umpan balik
Selesai
```

### 3.6 Alur Chatbot

```text
Mulai
  -> muat percakapan dari session
  -> normalisasi pesan
  -> cocokkan kata kunci gejala (dengan deteksi negasi)
  -> jika tidak dikenali: tampilkan fallback + catat log
  -> jika dikenali: hitung diagnosis sementara
  -> pilih pertanyaan yang paling membedakan kandidat
  -> berhenti jika skor >= 0,75 / 5 pertanyaan / tidak ada pertanyaan
  -> tampilkan penyebab dan solusi
Selesai
```

### 3.7 Rancangan Database

| Tabel | Fungsi |
|---|---|
| `users` | Akun pengguna dan admin |
| `categories` | Kategori kerusakan |
| `symptoms` | Gejala beserta kata kunci chatbot |
| `causes` | Kemungkinan penyebab |
| `rules` | Aturan dan bobot certainty factor |
| `rule_symptoms` | Relasi banyak-ke-banyak aturan dan gejala |
| `solutions` | Solusi bertahap per penyebab |
| `diagnoses` | Header riwayat diagnosis |
| `diagnosis_details` | Gejala yang dipilih pada diagnosis |
| `diagnosis_results` | Skor penyebab pada diagnosis |
| `diagnosis_feedback` | Umpan balik tepat/kurang tepat |
| `articles` | Artikel panduan |
| `chat_logs` | Log pesan chatbot |

### 3.8 Basis Pengetahuan

Data awal yang disediakan:

- 5 kategori: layar, daya, performa, penyimpanan, jaringan/periferal.
- 33 gejala.
- 15 penyebab.
- 30 aturan.
- 59 langkah solusi.
- 5 artikel panduan.

---

## BAB IV IMPLEMENTASI

### 4.1 Lingkungan Implementasi

- Windows + XAMPP (Apache, PHP, MySQL/MariaDB).
- PHP 8.5.12 dan MariaDB 10.11.16 pada saat pengujian.
- Editor kode dan browser modern.

### 4.2 Struktur Folder Utama

```text
fixit/
├── index.php
├── config/database.php
├── includes/
│   ├── bootstrap.php
│   ├── functions.php
│   ├── auth.php
│   ├── header.php
│   ├── footer.php
│   ├── feedback_form.php
│   └── classes/
│       ├── Repository.php
│       ├── DiagnosisEngine.php
│       └── Chatbot.php
├── pages/
│   ├── diagnosis.php
│   ├── hasil.php
│   ├── feedback.php
│   ├── chat.php
│   ├── riwayat.php
│   ├── artikel.php
│   ├── artikel_detail.php
│   ├── login.php
│   ├── daftar.php
│   └── logout.php
├── api/
│   ├── chatbot.php
│   ├── diagnose.php
│   └── symptoms.php
├── admin/
│   ├── _layout.php
│   ├── _crud.php
│   ├── index.php
│   ├── kategori.php
│   ├── gejala.php
│   ├── penyebab.php
│   ├── aturan.php
│   ├── solusi.php
│   ├── artikel.php
│   ├── log_chat.php
│   └── umpan_balik.php
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   ├── js/chat.js
│   └── img/
├── database/
│   ├── fixit.sql
│   └── migrasi_umpan_balik.sql
├── docs/
└── tools/seed_check.php
```

### 4.3 Modul Utama

1. **Repository** — memusatkan query database untuk kategori, gejala, aturan, diagnosis, artikel, log, dan umpan balik.
2. **DiagnosisEngine** — menghitung kecocokan, skor aturan, dan penggabungan certainty factor.
3. **Chatbot** — mengekstrak gejala dari pesan, memilih pertanyaan lanjutan, dan menyusun hasil konsultasi.
4. **Halaman diagnosis dan hasil** — menampilkan pilihan gejala, peringkat penyebab, solusi, alasan aturan, serta tombol cetak/salin ringkasan.
5. **Panel admin** — CRUD basis pengetahuan, log chatbot, statistik, dan rekap umpan balik.
6. **API JSON** — menyediakan data gejala, diagnosis, dan chatbot untuk kebutuhan antarmuka.

### 4.4 Contoh Implementasi Perhitungan

Potongan inti `DiagnosisEngine`:

```php
$kecocokan = count($cocok) / count($butuh);
$skorAturan = $kecocokan * (float) $rule['bobot'];

$cf = 0.0;
foreach ($data['aturan'] as $aturan) {
    $cf = $cf + $aturan['skor'] * (1 - $cf);
}
```

### 4.5 Keamanan Implementasi

- Password: `password_hash()` dan `password_verify()`.
- CSRF: token per session pada seluruh form dan API.
- SQL injection: PDO prepared statement.
- XSS: helper `e()` untuk escaping output.
- Otorisasi: `require_login()`, `require_admin()`, dan pengecekan kepemilikan riwayat/umpan balik.
- Upload gambar: validasi MIME, ukuran maksimal 2 MB, dan nama file unik.

---

## BAB V PENGUJIAN DAN EVALUASI

### 5.1 Metode Pengujian

1. **Pengujian unit logika** melalui `tools/seed_check.php` untuk memeriksa konsistensi basis pengetahuan dan hasil diagnosis.
2. **Pengujian black-box** terhadap halaman publik, autentikasi, riwayat, chatbot, API, admin, dan umpan balik.
3. **Pemeriksaan sintaks** seluruh file PHP dengan `php -l`.
4. **Pengujian manual UI** untuk tampilan responsif, cetak, dan salin ringkasan.

### 5.2 Hasil Pengujian Basis Pengetahuan

Perintah `php tools/seed_check.php` menghasilkan:

| Pemeriksaan | Hasil |
|---|---|
| Jumlah aturan | 30 |
| Jumlah gejala | 33 |
| Jumlah penyebab | 15 |
| Aturan tanpa gejala | 0 |
| Penyebab tanpa solusi | 0 |
| Gejala tanpa aturan | 0 |
| Bobot di luar 0–1 | 0 |
| Kasus diagnosis uji | 9 kasus sesuai |
| Ekstraksi chatbot | 2 kalimat uji berhasil |
| Status akhir | **SEMUA PEMERIKSAAN LULUS** |

### 5.3 Hasil Pengujian Black-Box

Ringkasan: **35 skenario lulus, 0 gagal**. Rincian lengkap tersedia di `docs/pengujian-blackbox.md`.

Contoh hasil diagnosis:

| Gejala | Penyebab teratas | Skor |
|---|---|---|
| Layar hitam, kipas berputar, lampu menyala, monitor eksternal tampil | Kerusakan panel atau kabel fleksibel layar | 80% |
| Layar hitam, kipas berputar, monitor eksternal tidak tampil | RAM longgar atau bermasalah | 70% |
| Panas, kipas berisik, hang | Overheating akibat debu/thermal paste kering | 91% |
| Bunyi klik, file hilang | Hard disk atau SSD bermasalah | 91% |
| Baterai menggembung | Baterai rusak atau sudah aus | 90% |

### 5.4 Pengujian Umpan Balik

- Feedback anonim tersimpan dengan `user_id = NULL`.
- Feedback pengguna login tersimpan dengan `user_id` pengguna.
- Feedback dapat diubah tanpa membuat duplikat.
- Pengguna lain tidak dapat mengirim feedback untuk diagnosis yang bukan miliknya.
- Admin dapat melihat rekap dan penyebab yang paling sering dinilai kurang tepat.

### 5.5 Keterbatasan

- Basis pengetahuan masih terbatas pada laptop dan perlu divalidasi teknisi.
- Chatbot bergantung pada kata kunci, belum memahami konteks percakapan yang kompleks.
- Bobot aturan belum dioptimalkan secara statistik dari data feedback.
- Pengujian usability dengan responden (SUS) belum dilakukan.

---

## BAB VI KESIMPULAN DAN SARAN

### 6.1 Kesimpulan

1. FIXIT berhasil dibangun sebagai aplikasi web diagnosis awal kerusakan laptop.
2. Metode rule-based dengan certainty factor mampu menghasilkan peringkat penyebab berdasarkan gejala yang dipilih.
3. Chatbot dapat mengenali gejala dari kalimat sederhana dan menanyakan gejala tambahan.
4. Fitur riwayat, artikel, umpan balik, dan panel admin mendukung penggunaan dan evaluasi sistem.
5. Seluruh pengujian fungsional utama dan pengujian basis pengetahuan lulus.

### 6.2 Saran

1. Validasi basis aturan dan solusi bersama teknisi atau dosen.
2. Tambahkan dukungan PC desktop.
3. Gunakan data umpan balik untuk menyetel bobot aturan secara berkala.
4. Tambahkan pengujian usability (SUS) dan pengujian akurasi dengan kasus nyata.
5. Kembangkan chatbot dengan pemrosesan bahasa alami yang lebih baik.

---

## Daftar Pustaka

> Lengkapi sesuai pedoman kampus. Contoh acuan yang relevan:

1. Giarratano, J. C., & Riley, G. D. *Expert Systems: Principles and Programming*.
2. Shortliffe, E. H., & Buchanan, B. G. *A Model of Inexact Reasoning in Medicine*.
3. Dokumentasi PHP. https://www.php.net/docs.php
4. Dokumentasi MySQL/MariaDB. https://mariadb.com/kb/en/documentation/
5. Dokumentasi XAMPP. https://www.apachefriends.org/

---

## Lampiran

### A. Akun Contoh

| Peran | Email | Kata Sandi |
|---|---|---|
| Admin | `admin@fixit.test` | `admin123` |
| Pengguna | `user@fixit.test` | `user123` |

> Ganti kata sandi sebelum menggunakan aplikasi di lingkungan nyata.

### B. Cara Menjalankan

1. Jalankan Apache dan MySQL pada XAMPP.
2. Impor `database/fixit.sql` ke database `fixit`.
3. Sesuaikan `config/database.php` bila perlu.
4. Buka `http://localhost/fixit`.
5. Untuk database lama, impor `database/migrasi_umpan_balik.sql`.

### C. Perintah Pemeriksaan

```bash
php tools/seed_check.php
```
