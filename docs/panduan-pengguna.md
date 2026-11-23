# Panduan Penggunaan FIXIT

## 1. Menjalankan Aplikasi

1. Jalankan **Apache** dan **MySQL** dari XAMPP.
2. Pastikan database `fixit` sudah diimpor dari `database/fixit.sql`.
3. Buka `http://localhost/fixit` di browser.

Jika aplikasi dipasang di folder lain, sesuaikan URL dan konfigurasi di `config/database.php`.

## 2. Halaman Pengguna

### 2.1 Beranda

Beranda menampilkan penjelasan singkat, statistik basis pengetahuan, daftar kategori, dan artikel terbaru.

### 2.2 Diagnosis Berbasis Gejala

1. Buka menu **Diagnosis**.
2. Pilih kategori atau gunakan kolom pencarian.
3. Centang gejala yang benar-benar dialami.
4. Klik **Lihat kemungkinan penyebab**.

Hasil menampilkan maksimal tiga penyebab, skor kepastian, solusi bertahap, dan alasan aturan. Gunakan tombol:

- **Cetak hasil** untuk mencetak.
- **Salin ringkasan** untuk menyalin ringkasan ke clipboard.
- **Ubah gejala** untuk kembali mengedit pilihan.

### 2.3 Chatbot

1. Buka menu **Chatbot**.
2. Tulis gejala dengan bahasa sehari-hari, misalnya:
   - "laptop menyala tapi layar hitam"
   - "kipas berisik dan laptop panas"
3. Jawab pertanyaan lanjutan dengan **Ya**, **Tidak**, atau **Tidak tahu**.
4. Setelah cukup, chatbot menampilkan kemungkinan penyebab dan solusi awal.

Jika pesan tidak dikenali, pilih kategori yang muncul atau gunakan halaman diagnosis.

### 2.4 Riwayat Diagnosis

Riwayat hanya tersedia setelah login. Setiap hasil diagnosis yang dibuat saat login akan tersimpan otomatis.

1. Masuk melalui menu **Masuk**.
2. Buka menu **Riwayat**.
3. Klik salah satu riwayat untuk melihat detail.
4. Di halaman detail, Anda dapat mengubah umpan balik.

### 2.5 Umpan Balik Diagnosis

Pada halaman hasil atau detail riwayat:

1. Pilih **Tepat** atau **Kurang tepat**.
2. Tambahkan catatan bila perlu.
3. Klik tombol penilaian untuk mengirim.

Umpan balik boleh dikirim tanpa login. Feedback digunakan admin untuk mengevaluasi bobot aturan, bukan untuk mengubah hasil secara otomatis.

### 2.6 Artikel

Menu **Artikel** berisi panduan perbaikan per kategori. Klik artikel untuk membaca isi lengkap.

## 3. Panel Admin

Masuk dengan akun admin, lalu buka menu **Admin**.

### 3.1 Ringkasan

Menampilkan jumlah gejala, penyebab, aturan, diagnosis, umpan balik, penyebab yang sering dinilai kurang tepat, dan log chatbot terbaru.

### 3.2 Kategori

Mengelola kategori kerusakan: nama, slug, ikon, dan urutan tampil.

### 3.3 Gejala

Mengelola gejala: kategori, kode, pernyataan, kata kunci chatbot, dan status aktif.

> Kata kunci dipisahkan dengan koma. Contoh: `layar hitam, blank, layar gelap`.

### 3.4 Penyebab

Mengelola kemungkinan penyebab: kode, nama, deskripsi, dan tingkat risiko.

### 3.5 Aturan

Menghubungkan satu penyebab dengan satu atau beberapa gejala, serta menentukan bobot 0–1. Aturan nonaktif tidak dipakai dalam diagnosis.

### 3.6 Solusi

Mengelola langkah solusi per penyebab. Tandai **butuh teknisi** untuk langkah berisiko.

### 3.7 Artikel

Mengelola artikel: kategori, judul, slug, isi HTML sederhana, dan gambar utama (maksimal 2 MB).

### 3.8 Log Chatbot

Menampilkan pesan yang tidak dikenali chatbot. Gunakan daftar ini untuk menambah kata kunci atau gejala baru.

### 3.9 Umpan Balik

Menampilkan statistik feedback dan daftar penilaian pengguna. Gunakan informasi ini untuk meninjau aturan yang terlalu luas atau bobot yang perlu disesuaikan.

## 4. Pemeriksaan Basis Pengetahuan

Jalankan dari terminal:

```bash
php tools/seed_check.php
```

Perintah ini memeriksa aturan tanpa gejala, penyebab tanpa solusi, gejala yatim, bobot tidak valid, contoh diagnosis, dan ekstraksi chatbot.

## 5. Migrasi Database Lama

Jika database sudah ada sebelum fitur umpan balik ditambahkan:

```bash
mysql -u root fixit < database/migrasi_umpan_balik.sql
```

Atau impor file tersebut melalui phpMyAdmin.

## 6. Masalah Umum

| Masalah | Solusi |
|---|---|
| Koneksi database gagal | Pastikan MySQL berjalan dan database `fixit` sudah diimpor |
| Halaman admin menolak akses | Login dengan akun berperan `admin` |
| Chatbot tidak mengenali gejala | Tambahkan kata kunci pada menu admin > Gejala |
| Hasil diagnosis kosong | Pilih lebih banyak gejala yang relevan |
| Umpan balik gagal dikirim | Muat ulang halaman agar token sesi diperbarui |
| Gambar artikel tidak muncul | Pastikan folder `assets/img` dapat ditulis dan format gambar didukung |

## 7. Catatan Keselamatan

- Hasil FIXIT adalah perkiraan awal, bukan diagnosis pasti.
- Hentikan pemakaian dan hubungi teknisi jika baterai menggembung, tercium bau terbakar, atau ada percikan listrik.
- Jangan membongkar komponen yang masih dalam masa garansi.
