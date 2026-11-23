# FIXIT: Asisten Pemecahan Masalah Perangkat

> **Kenali gejalanya, temukan penyebabnya, mulai dari solusi awal.**

FIXIT adalah website yang membantu pengguna mengidentifikasi kemungkinan penyebab kerusakan laptop atau komputer berdasarkan gejala yang dialami. Sistem menggunakan diagnosis berbasis aturan (*rule-based*) dengan skor kepastian (*certainty factor*), dilengkapi chatbot konsultasi yang menanyakan gejala tambahan sebelum memberi kemungkinan penyebab dan solusi awal.

---

## Daftar Isi

1. [Latar Belakang](#latar-belakang)
2. [Tujuan](#tujuan)
3. [Fitur Utama](#fitur-utama)
4. [Ruang Lingkup](#ruang-lingkup)
5. [Teknologi](#teknologi)
6. [Struktur Folder](#struktur-folder)
7. [Kategori dan Contoh Gejala](#kategori-dan-contoh-gejala)
8. [Contoh Basis Aturan](#contoh-basis-aturan)
9. [Mesin Diagnosis](#mesin-diagnosis)
10. [Alur Chatbot](#alur-chatbot)
11. [Rancangan Database](#rancangan-database)
12. [Struktur Halaman](#struktur-halaman)
13. [Instalasi (XAMPP)](#instalasi-xampp)
14. [Pengujian dan Evaluasi](#pengujian-dan-evaluasi)
15. [Rencana Pengembangan](#rencana-pengembangan)
16. [Catatan Keselamatan](#catatan-keselamatan)

---

## Latar Belakang

Pengguna sering tidak mengetahui penyebab kerusakan pada laptop atau komputernya. Akibatnya, mereka bingung menentukan langkah pertama, salah menangani, atau langsung membawa perangkat ke servis padahal masalahnya bisa diperiksa lebih dulu. FIXIT hadir sebagai pendamping yang memandu pengguna dari gejala menuju kemungkinan penyebab dan solusi awal.

## Tujuan

- Membantu pengguna mengidentifikasi kemungkinan penyebab kerusakan berdasarkan gejala.
- Memberikan rekomendasi solusi awal yang aman dan bertahap.
- Menyediakan chatbot konsultasi agar proses diagnosis terasa seperti berbicara dengan teknisi.
- Menyediakan riwayat diagnosis dan artikel panduan perbaikan sebagai referensi.
- Menyediakan pengelolaan basis pengetahuan (CRUD) bagi admin.

## Fitur Utama

### Pengguna
- **Pilihan kategori kerusakan**: layar, daya, performa, penyimpanan, jaringan/periferal.
- **Input gejala**: pilih gejala dari daftar atau tulis lewat chatbot.
- **Diagnosis rule-based**: peringkat kemungkinan penyebab dengan skor kepastian.
- **Rekomendasi solusi**: langkah bertahap, dengan penanda jika perlu teknisi.
- **Riwayat diagnosis**: simpan dan lihat kembali hasil sebelumnya.
- **Umpan balik diagnosis**: tandai hasil sebagai *tepat* atau *kurang tepat* beserta catatan, bahkan tanpa login.
- **Ringkasan diagnosis**: cetak atau salin ringkasan untuk dikirim ke teknisi.
- **Artikel panduan perbaikan**: panduan per kategori dengan gambar.
- **Chatbot konsultasi kerusakan**: menanyakan gejala tambahan secara bertahap.

### Admin
- CRUD kategori, gejala, penyebab, aturan, solusi, dan artikel.
- Peninjauan log chatbot (pesan yang tidak dikenali) untuk memperkaya basis pengetahuan.
- Rekap umpan balik pengguna dan daftar penyebab yang paling sering dinilai kurang tepat.
- Statistik: penyebab paling sering terdiagnosis dan gejala yang paling sering dipilih.

## Ruang Lingkup

Agar proyek terkendali, tahap awal dibatasi pada:
- Jenis perangkat: **laptop** (PC desktop menjadi pengembangan lanjutan).
- Sekitar **4-5 kategori** dan **25-40 gejala**.
- Diagnosis bersifat **perkiraan awal**, bukan pengganti pemeriksaan teknisi.

## Teknologi

| Komponen | Teknologi |
|---|---|
| Backend | PHP |
| Database | MySQL |
| Frontend | HTML, CSS, JavaScript |
| Server lokal | XAMPP (Apache + MySQL) |
| Metode diagnosis | Rule-based dengan *certainty factor* |
| Chatbot | Pencocokan kata kunci dan sinonim terhadap tabel gejala |

## Struktur Folder

```
fixit/
├── index.php
├── config/
│   └── database.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── functions.php
│   ├── auth.php
│   ├── bootstrap.php
│   ├── feedback_form.php   # formulir umpan balik diagnosis
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
│   └── daftar.php
├── api/
│   ├── chatbot.php
│   ├── diagnose.php
│   └── symptoms.php
├── admin/
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
│   ├── css/
│   ├── js/
│   └── img/
├── database/
│   ├── fixit.sql
│   └── migrasi_umpan_balik.sql
├── docs/
│   ├── laporan-proyek.md
│   ├── pengujian-blackbox.md
│   ├── instrumen-evaluasi.md
│   └── panduan-pengguna.md
├── tools/
│   └── seed_check.php
└── README.md
```

> Struktur ini bersifat rancangan awal dan dapat disesuaikan selama pengembangan.

## Kategori dan Contoh Gejala

| Kategori | Contoh gejala |
|---|---|
| Layar/tampilan | layar hitam, layar bergaris, redup, berkedip, tidak ada gambar di monitor eksternal |
| Daya/baterai | tidak mau menyala, mati sendiri, baterai cepat habis, charger tidak mengisi |
| Performa | lambat, hang, panas berlebih, kipas berisik, restart sendiri |
| Penyimpanan | bunyi klik pada HDD, file hilang, bootloop, "no bootable device" |
| Jaringan/periferal | Wi-Fi tidak terdeteksi, keyboard/touchpad mati, USB tidak terbaca |

## Contoh Basis Aturan

| Kode | Aturan (IF gejala) | Kemungkinan penyebab | Bobot |
|---|---|---|---|
| R01 | menyala + layar hitam + kipas berputar + monitor eksternal tampil | Kerusakan panel atau kabel fleksibel layar | 0,8 |
| R02 | menyala + layar hitam + kipas berputar + monitor eksternal tidak tampil | RAM longgar atau bermasalah | 0,7 |
| R03 | menyala + layar hitam + bunyi beep berulang | RAM atau VGA bermasalah | 0,8 |
| R04 | menyala + layar hitam + lampu indikator mati | Masalah daya atau mainboard | 0,6 |

Setiap penyebab memiliki solusi bertahap. Contoh untuk **RAM longgar atau bermasalah**:
1. Matikan laptop dan lepas charger.
2. Cabut dan pasang ulang modul RAM, bersihkan kontak.
3. Coba satu modul secara bergantian.
4. Jika tetap gagal, bawa ke teknisi.

## Mesin Diagnosis

1. Kumpulkan gejala yang dipilih pengguna (atau dikenali dari chatbot).
2. Untuk tiap aturan, hitung **kecocokan** = gejala terpenuhi ÷ gejala yang dibutuhkan aturan.
3. **Skor aturan** = kecocokan × bobot aturan.
4. Jika beberapa aturan mengarah ke penyebab yang sama, gabungkan dengan rumus certainty factor:

   ```
   CF_gabungan = CF1 + CF2 × (1 − CF1)
   ```

5. Urutkan penyebab dari skor tertinggi, tampilkan **3 teratas** beserta solusi bertahap.
6. Simpan hasil ke `diagnoses` dan `diagnosis_details` jika pengguna login.

## Alur Chatbot

**Contoh percakapan**

> **Pengguna:** Laptop saya menyala tetapi layarnya hitam.
> **Bot:** Saya catat: menyala, layar hitam. Apakah kipas atau lampu indikator menyala?
> **Pengguna:** Kipas berputar, lampu menyala.
> **Bot:** Apakah ada suara beep saat dinyalakan? Dan jika disambungkan ke monitor atau TV lewat HDMI, apakah gambar muncul?
> **Pengguna:** Tidak ada beep, di monitor tidak muncul.
> **Bot:** Kemungkinan penyebab: (1) RAM longgar atau bermasalah (skor 0,70), (2) VGA/mainboard (0,45). Solusi awal: cabut dan pasang ulang RAM, lalu coba satu modul saja. Jika tetap gagal, sebaiknya bawa ke teknisi.

**Langkah teknis**

1. **Ekstraksi gejala**: normalisasi pesan (huruf kecil, hapus tanda baca), lalu cocokkan kata kunci dan sinonim ke tabel `symptoms` (misalnya "blank", "hitam", "gelap" dipetakan ke gejala *layar hitam*).
2. **Pemilihan pertanyaan lanjutan**: pilih gejala yang paling membedakan antarpenyebab kandidat.
3. **Kriteria berhenti**: skor teratas melewati ambang batas atau jumlah pertanyaan mencapai batas (misalnya 5).
4. **Hasil**: tampilkan penyebab teratas dan solusi awal.
5. **Fallback**: jika input tidak dikenali, tampilkan pilihan kategori dan catat ke `chat_logs` dengan `dikenali = 0` untuk ditinjau admin.

## Rancangan Database

| Tabel | Isi utama |
|---|---|
| `users` | id, nama, email, password_hash, role |
| `categories` | id, nama_kategori |
| `symptoms` | id, category_id, kode, pertanyaan/gejala, kata_kunci |
| `causes` | id, kode, nama_penyebab, deskripsi, tingkat_risiko |
| `rules` | id, cause_id, bobot (certainty factor) |
| `rule_symptoms` | rule_id, symptom_id |
| `solutions` | id, cause_id, langkah, urutan, butuh_teknisi (0/1) |
| `diagnoses` | id, user_id, waktu, hasil_teratas |
| `diagnosis_details` | diagnosis_id, symptom_id, jawaban |
| `diagnosis_results` | diagnosis_id, cause_id, skor, urutan |
| `diagnosis_feedback` | diagnosis_id, user_id, penilaian, catatan, waktu |
| `articles` | id, category_id, judul, isi, gambar |
| `chat_logs` | id, user_id, pesan, balasan, dikenali (0/1), waktu |

## Struktur Halaman

| Halaman | Fungsi |
|---|---|
| `index` | Beranda dan pengantar |
| `diagnosis` | Pilih kategori, lalu centang gejala |
| `chat` | Konsultasi dengan chatbot |
| `hasil` | Peringkat penyebab, solusi bertahap, dan umpan balik |
| `riwayat` | Daftar diagnosis sebelumnya dan detail umpan balik |
| `artikel` | Daftar dan detail panduan perbaikan |
| `login` / `daftar` | Autentikasi pengguna |
| `admin/` | Pengelolaan basis pengetahuan, log chatbot, dan umpan balik |

## Instalasi (XAMPP)

1. Instal [XAMPP](https://www.apachefriends.org/) dan jalankan **Apache** serta **MySQL**.
2. Salin folder `fixit` ke direktori `htdocs`.
3. Buka `http://localhost/phpmyadmin`, buat database `fixit`, lalu impor `database/fixit.sql`.
4. Sesuaikan koneksi database di `config/database.php`:
   ```php
   $host = "localhost";
   $user = "root";
   $pass = "";
   $db   = "fixit";
   ```
5. Akses website di `http://localhost/fixit`.

### Akun contoh

| Peran | Email | Kata sandi |
|---|---|---|
| Admin | `admin@fixit.test` | `admin123` |
| Pengguna | `user@fixit.test` | `user123` |

> Ganti kata sandi akun contoh sebelum memakai aplikasi di luar lingkungan latihan.

### Memperbarui database lama

Bila database `fixit` sudah pernah diimpor sebelum fitur umpan balik ditambahkan, jalankan migrasi berikut sekali:

```bash
mysql -u root fixit < database/migrasi_umpan_balik.sql
```

Atau impor `database/migrasi_umpan_balik.sql` melalui phpMyAdmin. Mengimpor ulang `database/fixit.sql` juga bisa, tetapi akan menghapus seluruh data yang sudah ada.

## Dokumentasi

- [Laporan proyek](docs/laporan-proyek.md) — draf laporan akhir; lengkapi identitas dan daftar pustaka.
- [Pengujian black-box](docs/pengujian-blackbox.md) — 35 skenario pengujian dan hasilnya.
- [Instrumen evaluasi](docs/instrumen-evaluasi.md) — validasi ahli, akurasi diagnosis, dan kuesioner SUS.
- [Panduan pengguna](docs/panduan-pengguna.md) — cara memakai aplikasi dan panel admin.

## Pengujian dan Evaluasi

- **Akurasi diagnosis**: uji 30-50 kasus yang disusun bersama teknisi atau dosen, lalu bandingkan hasil sistem dengan penilaian pakar.
- **Black-box testing** untuk seluruh fitur, termasuk pengiriman umpan balik sebagai pengguna anonim dan pengguna yang login.
- **Kegunaan**: kuesioner *System Usability Scale* (SUS) pada 15-20 responden.
- **Validasi pakar**: teknisi servis memeriksa basis aturan, bobot, dan solusi.

## Rencana Pengembangan

- [x] Penyusunan basis pengetahuan (gejala, penyebab, aturan, solusi) bersama pakar
- [x] Database dan CRUD admin
- [x] Mesin diagnosis (rule + certainty factor)
- [x] Halaman diagnosis berbasis pilihan gejala
- [x] Chatbot konsultasi
- [x] Riwayat diagnosis dan artikel panduan
- [x] Login, polesan tampilan, dan pengujian
- [x] Draf laporan proyek
- [ ] (Opsional) Dukungan PC desktop — di luar ruang lingkup tahap ini
- [x] (Opsional) Tombol "diagnosis ini benar/salah" untuk menyempurnakan bobot aturan
- [x] (Opsional) Ringkasan diagnosis yang bisa dicetak atau disalin untuk teknisi

## Catatan Keselamatan

- Hasil FIXIT adalah **perkiraan awal**, bukan diagnosis pasti.
- Solusi berisiko (membuka casing, baterai menggembung, bau terbakar, percikan listrik) harus disertai peringatan agar pengguna berhenti dan menghubungi teknisi.
- Seluruh isi basis aturan dan solusi sebaiknya divalidasi oleh teknisi atau dosen sebelum dipublikasikan.
