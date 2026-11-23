# Pengujian Black-Box FIXIT

Dokumen ini mencatat skenario pengujian fungsional FIXIT beserta hasil aktualnya.

## Lingkungan Pengujian

| Komponen | Keterangan |
|---|---|
| Sistem operasi | Windows |
| Web server | PHP built-in server (`php -S`) dan XAMPP Apache |
| PHP | 8.5.12 |
| Database | MariaDB 10.11.16 (MySQL compatible) |
| Database name | `fixit` |
| Tanggal pengujian | 9 November 2026 |
| Metode | Permintaan HTTP langsung, pemeriksaan status/isi halaman, dan verifikasi data di database |

## Ringkasan Hasil

- Total skenario: **35**
- Lulus: **35**
- Gagal: **0**
- Pengujian logika basis pengetahuan: `php tools/seed_check.php` → **SEMUA PEMERIKSAAN LULUS**

## Tabel Pengujian

| No | Modul | Skenario | Hasil yang Diharapkan | Hasil Aktual | Status |
|---:|---|---|---|---|---|
| 1 | Beranda | Membuka `/` | Halaman beranda tampil, statistik dan kategori muncul | HTTP 200, konten beranda tampil | Lulus |
| 2 | Navigasi | Membuka menu Diagnosis, Chatbot, Artikel | Ketiga halaman dapat dibuka | HTTP 200 untuk semua halaman | Lulus |
| 3 | Diagnosis | Membuka halaman diagnosis | Daftar kategori dan gejala tampil | HTTP 200, 33 gejala dimuat | Lulus |
| 4 | Diagnosis | Memfilter gejala dengan pencarian | Hanya gejala yang cocok ditampilkan | Filter berjalan di sisi klien | Lulus |
| 5 | Diagnosis | Memilih kategori tertentu | Hanya kelompok kategori terpilih yang tampil | Tab kategori berfungsi | Lulus |
| 6 | Diagnosis | Mengirim diagnosis tanpa memilih gejala | Kembali ke halaman diagnosis dengan pesan galat | HTTP 302 ke `diagnosis.php` + flash error | Lulus |
| 7 | Diagnosis | Mengirim gejala: layar hitam + kipas berputar + monitor eksternal tidak tampil | Hasil teratas RAM longgar/bermasalah | Skor 0,700; hasil dan solusi tampil | Lulus |
| 8 | Hasil | Menampilkan tiga kemungkinan penyebab | Peringkat, skor, solusi, dan alasan aturan tampil | Ketiga bagian tampil | Lulus |
| 9 | Hasil | Menekan tombol cetak | Dialog cetak browser terbuka | `window.print()` terpanggil | Lulus |
| 10 | Hasil | Menekan tombol salin ringkasan | Ringkasan diagnosis tersalin ke clipboard | Tombol dan status salin tampil; fallback tersedia | Lulus |
| 11 | Umpan balik | Mengirim feedback sebagai pengunjung anonim | Feedback tersimpan dan status tampil | Data tersimpan dengan `user_id = NULL` | Lulus |
| 12 | Umpan balik | Mengubah feedback yang sudah dikirim | Nilai feedback diperbarui, tidak membuat duplikat | `ON DUPLICATE KEY UPDATE` berhasil | Lulus |
| 13 | Umpan balik | Mengirim feedback untuk diagnosis milik pengguna lain | Permintaan ditolak | Redirect dengan pesan galat; data tidak berubah | Lulus |
| 14 | Umpan balik | Mengirim feedback tanpa token CSRF | Permintaan ditolak | HTTP 419 | Lulus |
| 15 | Riwayat | Membuka riwayat tanpa login | Diarahkan ke halaman login | HTTP 302 ke `login.php` | Lulus |
| 16 | Riwayat | Login sebagai pengguna lalu membuka riwayat | Daftar diagnosis pengguna tampil | HTTP 200, riwayat tampil | Lulus |
| 17 | Riwayat | Membuka detail diagnosis | Gejala, penyebab, skor, dan solusi tampil | HTTP 200, detail lengkap | Lulus |
| 18 | Riwayat | Membuka detail diagnosis milik pengguna lain | Data tidak dapat diakses | Redirect dengan pesan galat | Lulus |
| 19 | Autentikasi | Login dengan akun admin | Masuk dan diarahkan ke halaman tujuan | HTTP 302, sesi admin aktif | Lulus |
| 20 | Autentikasi | Login dengan kata sandi salah | Tetap di halaman login dan muncul pesan galat | Pesan "Email atau kata sandi tidak cocok" tampil | Lulus |
| 21 | Autentikasi | Daftar akun baru dengan data valid | Akun dibuat dan pengguna langsung masuk | Redirect ke riwayat, sesi aktif | Lulus |
| 22 | Autentikasi | Logout | Sesi dihapus dan kembali ke beranda | HTTP 302 ke beranda | Lulus |
| 23 | Artikel | Membuka daftar artikel | Artikel tampil dan dapat difilter kategori | HTTP 200, 5 artikel tampil | Lulus |
| 24 | Artikel | Membuka detail artikel | Isi artikel dan gambar tampil | HTTP 200 | Lulus |
| 25 | Chatbot | Membuka halaman chatbot | Pesan sambutan muncul | HTTP 200, pesan awal tampil | Lulus |
| 26 | Chatbot | Mengirim "laptop menyala tapi layar hitam dan kipas berputar" | Gejala dikenali dan pertanyaan lanjutan muncul | 2 gejala dikenali, chatbot melanjutkan pertanyaan | Lulus |
| 27 | Chatbot | Mengirim pesan yang tidak dikenali | Fallback kategori tampil dan pesan dicatat | Pesan fallback tampil, log `dikenali = 0` | Lulus |
| 28 | Admin | Membuka seluruh menu admin | Dashboard, kategori, gejala, penyebab, aturan, solusi, artikel, log, umpan balik tampil | HTTP 200 untuk 9 halaman | Lulus |
| 29 | Admin | Menambah, mengubah, dan menghapus data | Data tersimpan/berubah/terhapus | CRUD berjalan, konfirmasi hapus tampil | Lulus |
| 30 | Admin | Membuka rekap umpan balik | Statistik dan tabel feedback tampil | HTTP 200, data feedback tampil | Lulus |
| 31 | API | `GET /api/symptoms.php` | JSON daftar gejala | `ok = true`, 33 gejala | Lulus |
| 32 | API | `POST /api/diagnose.php` dengan gejala valid | JSON hasil diagnosis | `ok = true`, hasil teratas tampil | Lulus |
| 33 | API | `POST /api/diagnose.php` tanpa gejala | HTTP 422 dan pesan galat | HTTP 422 | Lulus |
| 34 | API | `POST /api/chatbot.php` dengan pesan valid | JSON percakapan chatbot | `ok = true`, balasan bot tampil | Lulus |
| 35 | API | `POST /api/chatbot.php` dengan pesan kosong | HTTP 422 | HTTP 422 | Lulus |

> Catatan: seluruh skenario di atas dijalankan berulang setelah perubahan terakhir.

## Pengujian Basis Pengetahuan

Perintah:

```bash
php tools/seed_check.php
```

Hasil aktual:

- 30 aturan, 33 gejala, 15 penyebab.
- Semua aturan memiliki gejala.
- Semua penyebab memiliki solusi.
- Tidak ada gejala yatim.
- Semua bobot berada pada rentang 0–1.
- Sembilan kasus diagnosis menghasilkan penyebab yang sesuai.
- Ekstraksi kata kunci chatbot berhasil pada dua kalimat uji.
- Status akhir: **SEMUA PEMERIKSAAN LULUS**.

## Catatan Pengujian Manual Tambahan

1. Tampilan responsif diuji pada lebar desktop dan mobile melalui browser.
2. Tombol salin ringkasan memakai Clipboard API dan fallback `document.execCommand('copy')`.
3. Cetak halaman hasil dan detail riwayat menggunakan dialog cetak bawaan browser.
4. Validasi sisi klien pada form diagnosis mencegah pengiriman tanpa gejala; validasi sisi server tetap tersedia.
