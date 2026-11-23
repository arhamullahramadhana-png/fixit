-- ============================================================
-- FIXIT - Asisten Pemecahan Masalah Perangkat
-- Skema database + data contoh
-- Impor lewat phpMyAdmin atau: mysql -u root < fixit.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS fixit
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE fixit;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS chat_logs;
DROP TABLE IF EXISTS diagnosis_feedback;
DROP TABLE IF EXISTS diagnosis_results;
DROP TABLE IF EXISTS diagnosis_details;
DROP TABLE IF EXISTS diagnoses;
DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS solutions;
DROP TABLE IF EXISTS rule_symptoms;
DROP TABLE IF EXISTS rules;
DROP TABLE IF EXISTS causes;
DROP TABLE IF EXISTS symptoms;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Pengguna
-- ------------------------------------------------------------
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Kategori kerusakan
-- ------------------------------------------------------------
CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  ikon VARCHAR(50) NOT NULL DEFAULT 'monitor',
  urutan INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Gejala
-- ------------------------------------------------------------
CREATE TABLE symptoms (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  kode VARCHAR(10) NOT NULL UNIQUE,
  pertanyaan VARCHAR(255) NOT NULL,
  kata_kunci TEXT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_symptoms_category FOREIGN KEY (category_id)
    REFERENCES categories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Penyebab
-- ------------------------------------------------------------
CREATE TABLE causes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(10) NOT NULL UNIQUE,
  nama_penyebab VARCHAR(150) NOT NULL,
  deskripsi TEXT NULL,
  tingkat_risiko ENUM('rendah', 'sedang', 'tinggi') NOT NULL DEFAULT 'sedang'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Aturan (rule) dan gejala penyusunnya
-- ------------------------------------------------------------
CREATE TABLE rules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cause_id INT UNSIGNED NOT NULL,
  kode VARCHAR(10) NOT NULL UNIQUE,
  bobot DECIMAL(3,2) NOT NULL DEFAULT 0.50,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_rules_cause FOREIGN KEY (cause_id)
    REFERENCES causes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rule_symptoms (
  rule_id INT UNSIGNED NOT NULL,
  symptom_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (rule_id, symptom_id),
  CONSTRAINT fk_rs_rule FOREIGN KEY (rule_id)
    REFERENCES rules (id) ON DELETE CASCADE,
  CONSTRAINT fk_rs_symptom FOREIGN KEY (symptom_id)
    REFERENCES symptoms (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Solusi bertahap
-- ------------------------------------------------------------
CREATE TABLE solutions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cause_id INT UNSIGNED NOT NULL,
  langkah TEXT NOT NULL,
  urutan INT NOT NULL DEFAULT 1,
  butuh_teknisi TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_solutions_cause FOREIGN KEY (cause_id)
    REFERENCES causes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Riwayat diagnosis
-- ------------------------------------------------------------
CREATE TABLE diagnoses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  hasil_teratas VARCHAR(150) NULL,
  ringkasan TEXT NULL,
  CONSTRAINT fk_diagnoses_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE diagnosis_details (
  diagnosis_id INT UNSIGNED NOT NULL,
  symptom_id INT UNSIGNED NOT NULL,
  jawaban TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (diagnosis_id, symptom_id),
  CONSTRAINT fk_dd_diagnosis FOREIGN KEY (diagnosis_id)
    REFERENCES diagnoses (id) ON DELETE CASCADE,
  CONSTRAINT fk_dd_symptom FOREIGN KEY (symptom_id)
    REFERENCES symptoms (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE diagnosis_results (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  diagnosis_id INT UNSIGNED NOT NULL,
  cause_id INT UNSIGNED NOT NULL,
  skor DECIMAL(5,3) NOT NULL,
  urutan INT NOT NULL,
  CONSTRAINT fk_dr_diagnosis FOREIGN KEY (diagnosis_id)
    REFERENCES diagnoses (id) ON DELETE CASCADE,
  CONSTRAINT fk_dr_cause FOREIGN KEY (cause_id)
    REFERENCES causes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Umpan balik diagnosis (tepat / kurang tepat)
-- ------------------------------------------------------------
CREATE TABLE diagnosis_feedback (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  diagnosis_id INT UNSIGNED NOT NULL UNIQUE,
  user_id INT UNSIGNED NULL,
  penilaian ENUM('tepat', 'tidak_tepat') NOT NULL,
  catatan VARCHAR(500) NULL,
  waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_df_diagnosis FOREIGN KEY (diagnosis_id)
    REFERENCES diagnoses (id) ON DELETE CASCADE,
  CONSTRAINT fk_df_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Artikel panduan
-- ------------------------------------------------------------
CREATE TABLE articles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NULL,
  judul VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  isi MEDIUMTEXT NOT NULL,
  gambar VARCHAR(255) NULL,
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_articles_category FOREIGN KEY (category_id)
    REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Log chatbot
-- ------------------------------------------------------------
CREATE TABLE chat_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  pesan TEXT NOT NULL,
  balasan TEXT NULL,
  dikenali TINYINT(1) NOT NULL DEFAULT 0,
  waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_chat_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA CONTOH
-- ============================================================

INSERT INTO users (nama, email, password_hash, role) VALUES
('Admin FIXIT', 'admin@fixit.test', '$2y$12$rVQxG3rDAwXcc3H0cd7XSev2ct5FI/dsp6J7rvQ1NETSx6mxk8jyi', 'admin'),
('Pengguna Contoh', 'user@fixit.test', '$2y$12$nMYsYtsbA2uvwzbIl93T0e5x4O/EtsdrbDT0iIJcJ1TwEcwfme/Kq', 'user');

INSERT INTO categories (nama_kategori, slug, ikon, urutan) VALUES
('Layar & Tampilan', 'layar', 'monitor', 1),
('Daya & Baterai', 'daya', 'battery', 2),
('Performa', 'performa', 'gauge', 3),
('Penyimpanan', 'penyimpanan', 'disk', 4),
('Jaringan & Periferal', 'jaringan', 'wifi', 5);

INSERT INTO symptoms (category_id, kode, pertanyaan, kata_kunci, aktif) VALUES
(1, 'S01', 'Layar tetap hitam padahal laptop menyala', 'layar hitam, layar gelap, blank, blank screen, hitam, tidak ada tampilan, no display, layar mati', 1),
(1, 'S02', 'Muncul garis-garis di layar', 'garis, bergaris, garis vertikal, garis horizontal, line, artefak', 1),
(1, 'S03', 'Layar sangat redup atau nyaris tidak terlihat', 'redup, samar, gelap sekali, backlight, tidak terang', 1),
(1, 'S04', 'Layar berkedip atau bergetar', 'berkedip, kedip, flicker, bergetar, goyang', 1),
(1, 'S05', 'Gambar muncul saat disambungkan ke monitor atau TV eksternal', 'monitor eksternal, hdmi, tv, proyektor, tampil di monitor, monitor tampil', 1),
(1, 'S06', 'Tidak ada gambar di monitor atau TV eksternal', 'monitor eksternal tidak, hdmi tidak, tv tidak, tidak tampil di monitor, monitor tidak tampil, monitor tidak muncul, tidak muncul di monitor', 1),
(1, 'S07', 'Warna atau gambar di layar tampak pecah', 'warna pecah, gambar pecah, glitch, warna aneh, artefak warna', 1),
(2, 'S08', 'Laptop sama sekali tidak menyala saat tombol power ditekan', 'tidak menyala, tidak bisa menyala, mati total, gak nyala, ga nyala, tidak hidup', 1),
(2, 'S09', 'Lampu indikator menyala saat tombol power ditekan', 'lampu nyala, lampu menyala, indikator nyala, indikator menyala, lampu hidup', 1),
(2, 'S10', 'Lampu indikator tidak menyala', 'lampu mati, indikator mati, tidak ada lampu, lampu tidak nyala', 1),
(2, 'S11', 'Terdengar bunyi beep berulang saat dinyalakan', 'beep, bip, bunyi beep, suara beep, beep berulang', 1),
(2, 'S12', 'Laptop mati sendiri saat sedang dipakai', 'mati sendiri, mati mendadak, mati tiba-tiba, shutdown sendiri', 1),
(2, 'S13', 'Charger tidak mengisi daya baterai', 'charger tidak mengisi, tidak mengisi, cas tidak masuk, charging tidak, adaptor tidak', 1),
(2, 'S14', 'Baterai cepat habis', 'baterai cepat habis, baterai boros, baterai drop, cepat habis, baterai habis', 1),
(2, 'S15', 'Baterai tampak menggembung atau casing menonjol', 'baterai menggembung, baterai gembung, baterai bengkak, casing menonjol, casing naik', 1),
(3, 'S16', 'Laptop terasa sangat lambat', 'lambat, lemot, lelet, slow, lambat sekali, berat', 1),
(3, 'S17', 'Sering hang atau tidak merespons', 'hang, freeze, macet, not responding, nge-hang, tidak merespons', 1),
(3, 'S18', 'Bagian bawah laptop atau area kipas terasa sangat panas', 'panas, overheat, panas berlebih, kepanasan, hangat', 1),
(3, 'S19', 'Kipas berbunyi keras atau berisik', 'kipas berisik, kipas keras, fan berisik, bising, kipas kencang', 1),
(3, 'S20', 'Laptop restart sendiri', 'restart sendiri, reboot sendiri, mati nyala sendiri, restart tiba-tiba', 1),
(3, 'S21', 'Muncul layar biru (blue screen) atau crash', 'blue screen, bsod, layar biru, crash, error biru', 1),
(4, 'S22', 'Terdengar bunyi klik dari dalam laptop', 'bunyi klik, klik, tik tik, harddisk bunyi, hdd bunyi', 1),
(4, 'S23', 'File atau folder hilang dan data rusak', 'file hilang, data hilang, file rusak, corrupt, data corrupt', 1),
(4, 'S24', 'Laptop restart berulang sebelum masuk sistem', 'bootloop, boot loop, restart terus, looping, nyala mati berulang', 1),
(4, 'S25', 'Muncul pesan no bootable device atau sistem operasi tidak ditemukan', 'no bootable, bootable device, os not found, sistem operasi tidak ditemukan, tidak ada sistem', 1),
(4, 'S26', 'Penyimpanan hampir penuh', 'penyimpanan penuh, storage penuh, disk penuh, memori penuh, ruang penuh', 1),
(5, 'S27', 'Wi-Fi tidak terdeteksi', 'wifi tidak terdeteksi, wi-fi tidak, wireless tidak, wifi hilang, sinyal wifi tidak, wifi mati', 1),
(5, 'S28', 'Wi-Fi terhubung tetapi tidak ada internet', 'terhubung tanpa internet, no internet, internet mati, dns, wifi terhubung tapi', 1),
(5, 'S29', 'Beberapa tombol keyboard tidak berfungsi', 'keyboard, tombol tidak berfungsi, papan ketik, keyboard mati, tombol mati', 1),
(5, 'S30', 'Touchpad tidak berfungsi', 'touchpad, trackpad, kursor tidak bergerak, touchpad mati', 1),
(5, 'S31', 'Perangkat USB tidak terbaca', 'usb tidak terbaca, flashdisk tidak, flash disk tidak, port usb, usb mati', 1),
(5, 'S32', 'Bluetooth tidak bisa diaktifkan', 'bluetooth, bt tidak, bluetooth mati', 1),
(2, 'S33', 'Kipas berputar saat laptop dinyalakan', 'kipas berputar, kipas nyala, fan berputar, kipas jalan, kipas hidup', 1);

INSERT INTO causes (kode, nama_penyebab, deskripsi, tingkat_risiko) VALUES
('C01', 'Kerusakan panel atau kabel fleksibel layar', 'Panel LCD atau kabel fleksibel yang menghubungkan layar ke mainboard mengalami kerusakan.', 'sedang'),
('C02', 'RAM longgar atau bermasalah', 'Modul RAM tidak terpasang sempurna atau kontaknya kotor sehingga perangkat gagal menampilkan gambar.', 'sedang'),
('C03', 'RAM atau VGA bermasalah', 'Kegagalan pada RAM atau chip grafis menyebabkan bunyi beep dan tampilan tidak keluar.', 'sedang'),
('C04', 'Masalah daya atau mainboard', 'Jalur daya pada mainboard tidak bekerja sehingga perangkat tidak mendapat tegangan yang cukup.', 'tinggi'),
('C05', 'Baterai rusak atau sudah aus', 'Kapasitas baterai menurun drastis atau sel baterai rusak dan menggembung.', 'sedang'),
('C06', 'Adaptor atau charger bermasalah', 'Adaptor tidak mengeluarkan tegangan yang benar sehingga baterai tidak terisi.', 'rendah'),
('C07', 'Overheating akibat debu atau thermal paste kering', 'Aliran udara terhambat dan perpindahan panas tidak optimal sehingga perangkat melambat dan memanas.', 'sedang'),
('C08', 'Hard disk atau SSD bermasalah', 'Media penyimpanan mengalami bad sector atau kerusakan mekanis sehingga data dan sistem terganggu.', 'tinggi'),
('C09', 'Sistem operasi atau driver bermasalah', 'Berkas sistem operasi rusak atau driver tidak kompatibel sehingga perangkat gagal boot.', 'rendah'),
('C10', 'RAM tidak cukup untuk beban kerja', 'Kapasitas memori lebih kecil dari kebutuhan aplikasi yang dijalankan.', 'rendah'),
('C11', 'Driver atau modul Wi-Fi bermasalah', 'Driver jaringan tidak berfungsi atau modul Wi-Fi tidak terdeteksi oleh sistem.', 'rendah'),
('C12', 'Kerusakan keyboard atau touchpad', 'Konektor atau komponen keyboard dan touchpad mengalami kerusakan.', 'sedang'),
('C13', 'Kerusakan port USB atau perangkat', 'Port USB tidak memberikan koneksi data atau daya yang stabil.', 'rendah'),
('C14', 'Infeksi malware atau terlalu banyak program latar belakang', 'Program berbahaya atau aplikasi latar belakang menghabiskan sumber daya.', 'rendah'),
('C15', 'Kapasitas penyimpanan penuh', 'Ruang penyimpanan yang tersisa terlalu kecil untuk operasi sistem dan aplikasi.', 'rendah');

INSERT INTO rules (cause_id, kode, bobot, aktif) VALUES
(1, 'R01', 0.80, 1),
(2, 'R02', 0.70, 1),
(3, 'R03', 0.80, 1),
(4, 'R04', 0.60, 1),
(4, 'R05', 0.65, 1),
(6, 'R06', 0.70, 1),
(5, 'R07', 0.75, 1),
(5, 'R08', 0.90, 1),
(7, 'R09', 0.80, 1),
(7, 'R10', 0.70, 1),
(2, 'R11', 0.65, 1),
(8, 'R12', 0.85, 1),
(8, 'R13', 0.80, 1),
(8, 'R14', 0.85, 1),
(9, 'R15', 0.60, 1),
(10, 'R16', 0.55, 1),
(14, 'R17', 0.45, 1),
(11, 'R18', 0.75, 1),
(11, 'R19', 0.60, 1),
(12, 'R20', 0.80, 1),
(13, 'R21', 0.70, 1),
(1, 'R22', 0.75, 1),
(1, 'R23', 0.55, 1),
(1, 'R24', 0.55, 1),
(7, 'R25', 0.60, 1),
(15, 'R26', 0.60, 1),
(9, 'R27', 0.55, 1),
(3, 'R28', 0.60, 1),
(4, 'R29', 0.55, 1),
(6, 'R30', 0.55, 1);

INSERT INTO rule_symptoms (rule_id, symptom_id) VALUES
(1, 1), (1, 33), (1, 9), (1, 5),
(2, 1), (2, 33), (2, 6),
(3, 1), (3, 11),
(4, 1), (4, 10),
(5, 8), (5, 10), (5, 13),
(6, 8), (6, 13),
(7, 14), (7, 12),
(8, 15),
(9, 18), (9, 19), (9, 17),
(10, 18), (10, 20),
(11, 17), (11, 21), (11, 20),
(12, 22), (12, 23),
(13, 24), (13, 25),
(14, 22), (14, 24),
(15, 24), (15, 21),
(16, 16), (16, 17),
(17, 16), (17, 21),
(18, 27), (18, 28),
(19, 27), (19, 32),
(20, 29), (20, 30),
(21, 31),
(22, 2), (22, 7),
(23, 4),
(24, 3),
(25, 18), (25, 16),
(26, 26), (26, 16),
(27, 25), (27, 23),
(28, 21), (28, 2),
(29, 12), (29, 10),
(30, 13), (30, 14);

INSERT INTO solutions (cause_id, langkah, urutan, butuh_teknisi) VALUES
(1, 'Simpan pekerjaan penting bila layar masih sempat menampilkan gambar.', 1, 0),
(1, 'Coba tekan Fn + tombol kecerahan dan sambungkan ke monitor eksternal untuk memastikan perangkat masih hidup.', 2, 0),
(1, 'Jangan membongkar panel sendiri karena kabel fleksibel layar sangat mudah rusak.', 3, 1),
(1, 'Bawa ke teknisi untuk pemeriksaan panel dan kabel fleksibel layar.', 4, 1),

(2, 'Matikan laptop, lepas charger, dan lepas baterai bila dapat dilepas.', 1, 0),
(2, 'Cabut dan pasang ulang modul RAM, lalu bersihkan kontaknya dengan penghapus bersih.', 2, 0),
(2, 'Coba satu modul RAM secara bergantian di setiap slot.', 3, 0),
(2, 'Bila tetap tidak menyala, bawa ke teknisi untuk pemeriksaan lebih lanjut.', 4, 1),

(3, 'Matikan laptop dan lepas charger.', 1, 0),
(3, 'Pasang ulang RAM dan coba satu modul secara bergantian.', 2, 0),
(3, 'Catat pola bunyi beep karena dapat membantu teknisi menentukan komponen yang gagal.', 3, 0),
(3, 'Bawa ke teknisi untuk pemeriksaan RAM dan VGA.', 4, 1),

(4, 'Pastikan stopkontak dan kabel charger berfungsi dengan mencoba perangkat lain.', 1, 0),
(4, 'Lepas charger, tahan tombol power selama 30 detik, lalu coba nyalakan kembali.', 2, 0),
(4, 'Jangan membongkar mainboard sendiri.', 3, 1),
(4, 'Bawa ke teknisi untuk pemeriksaan jalur daya dan mainboard.', 4, 1),

(5, 'Gunakan charger resmi dan hindari memakai laptop saat baterai menggembung.', 1, 0),
(5, 'Bila baterai menggembung, hentikan pemakaian dan jangan menekan casing.', 2, 1),
(5, 'Ganti baterai melalui teknisi resmi.', 3, 1),
(5, 'Setelah baterai baru, lakukan satu kali kalibrasi dengan mengisi penuh lalu mengosongkan.', 4, 0),

(6, 'Coba charger lain dengan spesifikasi tegangan dan arus yang sama.', 1, 0),
(6, 'Periksa kabel dan konektor charger dari keretakan atau lilitan yang rusak.', 2, 0),
(6, 'Ganti adaptor bila charger terbukti tidak mengisi.', 3, 0),
(6, 'Bila charger baru juga tidak mengisi, periksa port charger ke teknisi.', 4, 1),

(7, 'Letakkan laptop di permukaan keras dan beri ruang di sekitar ventilasi.', 1, 0),
(7, 'Bersihkan debu pada kipas dan ventilasi menggunakan udara bertekanan.', 2, 0),
(7, 'Batasi aplikasi berat dan gunakan cooling pad saat bekerja lama.', 3, 0),
(7, 'Bila suhu tetap tinggi, minta teknisi mengganti thermal paste.', 4, 1),

(8, 'Segera cadangkan data penting ke penyimpanan eksternal.', 1, 0),
(8, 'Hentikan pemakaian bila terdengar bunyi klik dari dalam perangkat.', 2, 1),
(8, 'Jangan menjatuhkan atau mengguncang laptop.', 3, 0),
(8, 'Ganti drive dan lakukan pemulihan data melalui teknisi.', 4, 1),

(9, 'Simpan data penting lalu coba masuk ke safe mode.', 1, 0),
(9, 'Jalankan pemulihan sistem atau periksa pembaruan driver.', 2, 0),
(9, 'Gunakan media instalasi untuk memperbaiki boot.', 3, 0),
(9, 'Bila gagal, instal ulang sistem operasi atau bawa ke teknisi.', 4, 1),

(10, 'Tutup aplikasi dan tab browser yang tidak dipakai.', 1, 0),
(10, 'Kurangi program yang berjalan otomatis saat startup.', 2, 0),
(10, 'Tambahkan RAM bila beban kerja memang berat.', 3, 1),

(11, 'Aktifkan mode pesawat lalu matikan kembali untuk menyegarkan koneksi.', 1, 0),
(11, 'Restart laptop dan router.', 2, 0),
(11, 'Perbarui atau pasang ulang driver Wi-Fi dari situs resmi produsen.', 3, 0),
(11, 'Bila modul Wi-Fi tidak terdeteksi, periksa ke teknisi.', 4, 1),

(12, 'Bersihkan keyboard dengan hati-hati dan periksa pengaturan aksesibilitas.', 1, 0),
(12, 'Sambungkan keyboard eksternal agar perangkat tetap bisa dipakai.', 2, 0),
(12, 'Bila touchpad juga mati, periksa konektor internal melalui teknisi.', 3, 1),
(12, 'Ganti keyboard atau touchpad bila diperlukan.', 4, 1),

(13, 'Coba perangkat USB di port lain.', 1, 0),
(13, 'Coba perangkat lain di port yang sama untuk memastikan sumber masalahnya.', 2, 0),
(13, 'Periksa driver USB melalui Device Manager.', 3, 0),
(13, 'Bila semua port bermasalah, periksa ke teknisi.', 4, 1),

(14, 'Jalankan pemindaian antivirus dan hapus program mencurigakan.', 1, 0),
(14, 'Hapus ekstensi browser dan aplikasi yang tidak dikenal.', 2, 0),
(14, 'Perbarui sistem operasi dan antivirus.', 3, 0),
(14, 'Bila tetap lambat, cadangkan data lalu instal ulang.', 4, 1),

(15, 'Kosongkan recycle bin dan folder unduhan.', 1, 0),
(15, 'Hapus aplikasi serta file besar yang tidak dipakai.', 2, 0),
(15, 'Pindahkan data ke penyimpanan eksternal atau cloud.', 3, 0),
(15, 'Sisakan minimal 15 persen ruang kosong agar sistem tetap stabil.', 4, 0);

INSERT INTO articles (category_id, judul, slug, isi, gambar) VALUES
(1, 'Panduan Aman Menangani Layar Hitam', 'panduan-aman-menangani-layar-hitam',
'<p>Layar hitam tidak selalu berarti laptop rusak total. Sebelum panik, perhatikan apakah lampu indikator dan kipas masih bekerja. Bila keduanya menyala, kemungkinan besar perangkat masih hidup tetapi gambar tidak tersampaikan ke layar.</p><h2>Langkah awal yang aman</h2><ol><li>Tekan Fn + tombol kecerahan untuk memastikan layar tidak sekadar redup.</li><li>Sambungkan laptop ke monitor atau TV melalui HDMI. Bila gambar muncul, panel atau kabel fleksibel layar perlu diperiksa.</li><li>Matikan perangkat, lepas charger, lalu tahan tombol power 30 detik.</li><li>Jangan membongkar panel layar sendiri karena kabel fleksibel sangat tipis dan mudah putus.</li></ol><p>Bila muncul bunyi beep berulang, catat polanya. Pola beep membantu teknisi menentukan apakah masalah berasal dari RAM atau chip grafis.</p>',
'assets/img/artikel-layar.svg'),
(2, 'Merawat Baterai dan Charger Laptop', 'merawat-baterai-dan-charger-laptop',
'<p>Sebagian besar baterai laptop modern memakai teknologi lithium yang tidak perlu selalu diisi sampai penuh. Kebiasaan mengisi sambil memakai perangkat dalam waktu lama tetap dapat mempercepat penurunan kapasitas.</p><h2>Kebiasaan yang membantu</h2><ul><li>Gunakan charger dengan tegangan dan arus yang sesuai spesifikasi.</li><li>Hindari membiarkan baterai benar-benar kosong dalam waktu lama.</li><li>Jauhkan laptop dari permukaan yang menutup ventilasi saat mengisi daya.</li><li>Bila casing tampak menonjol, hentikan pemakaian karena baterai kemungkinan menggembung.</li></ul><p>Baterai yang menggembung berisiko tinggi. Jangan menekan casing, jangan mencoba menusuk sel baterai, dan segera ganti melalui teknisi resmi.</p>',
'assets/img/artikel-daya.svg'),
(3, 'Mengatasi Laptop Lambat dan Panas', 'mengatasi-laptop-lambat-dan-panas',
'<p>Laptop yang lambat sering disertai suhu tinggi dan kipas yang berbunyi keras. Penyebab paling umum adalah debu pada heatsink, thermal paste yang mengering, atau terlalu banyak program berjalan di latar belakang.</p><h2>Urutan pemeriksaan</h2><ol><li>Periksa Task Manager untuk melihat aplikasi yang memakai CPU dan memori paling besar.</li><li>Kurangi program yang berjalan otomatis saat startup.</li><li>Bersihkan ventilasi dan kipas dengan udara bertekanan.</li><li>Gunakan cooling pad dan hindari permukaan lunak seperti kasur.</li><li>Minta teknisi mengganti thermal paste bila suhu tetap tinggi.</li></ol><p>Jangan membuka casing jika perangkat masih dalam masa garansi.</p>',
'assets/img/artikel-performa.svg'),
(4, 'Mengenali Tanda Hard Disk Akan Rusak', 'mengenali-tanda-hard-disk-akan-rusak',
'<p>Media penyimpanan yang mulai rusak biasanya memberi tanda sebelum benar-benar gagal. Mengenali tanda tersebut dapat menyelamatkan data penting Anda.</p><h2>Tanda yang perlu diwaspadai</h2><ul><li>Bunyi klik atau tik-tik berulang dari dalam laptop.</li><li>File tiba-tiba hilang atau tidak bisa dibuka.</li><li>Sistem sering gagal boot atau muncul pesan no bootable device.</li><li>Proses menyalin file terasa sangat lambat.</li></ul><p>Langkah paling penting adalah mencadangkan data segera. Hindari menjalankan alat perbaikan disk pada drive yang berbunyi klik karena dapat memperparah kerusakan mekanis.</p>',
'assets/img/artikel-penyimpanan.svg'),
(5, 'Memperbaiki Wi-Fi dan Periferal yang Tidak Berfungsi', 'memperbaiki-wifi-dan-periferal',
'<p>Masalah jaringan dan periferal sering berasal dari driver atau pengaturan, bukan kerusakan hardware. Mulailah dari langkah yang paling mudah dan aman.</p><h2>Wi-Fi tidak terdeteksi</h2><ol><li>Aktifkan lalu matikan mode pesawat.</li><li>Restart laptop dan router.</li><li>Periksa driver Wi-Fi di Device Manager.</li><li>Pasang ulang driver dari situs resmi produsen.</li></ol><h2>Keyboard, touchpad, dan USB</h2><ul><li>Coba keyboard atau mouse eksternal untuk memastikan sistem tetap berfungsi.</li><li>Pindahkan perangkat USB ke port lain.</li><li>Periksa pengaturan aksesibilitas yang mungkin menonaktifkan touchpad.</li></ul><p>Bila seluruh port atau seluruh tombol bermasalah, kemungkinan ada masalah pada konektor internal yang perlu diperiksa teknisi.</p>',
'assets/img/artikel-jaringan.svg');

INSERT INTO diagnoses (user_id, waktu, hasil_teratas, ringkasan) VALUES
(2, '2026-10-20 09:15:00', 'RAM longgar atau bermasalah', 'Layar hitam dengan kipas berputar dan monitor eksternal tidak menampilkan gambar.');

INSERT INTO diagnosis_details (diagnosis_id, symptom_id, jawaban) VALUES
(1, 1, 1), (1, 33, 1), (1, 6, 1);

INSERT INTO diagnosis_results (diagnosis_id, cause_id, skor, urutan) VALUES
(1, 2, 0.700, 1),
(1, 3, 0.400, 2);

INSERT INTO diagnosis_feedback (diagnosis_id, user_id, penilaian, catatan, waktu) VALUES
(1, 2, 'tepat', 'Langkah memasang ulang RAM berhasil, laptop kembali menyala.', '2026-10-20 10:05:00');

INSERT INTO chat_logs (user_id, pesan, balasan, dikenali) VALUES
(NULL, 'Laptop saya bunyi kriuk kriuk waktu diangkat', 'Maaf, saya belum mengenali gejala itu. Coba pilih kategori kerusakan atau tulis gejala dengan kata lain.', 0),
(NULL, 'Panu di layar', 'Maaf, saya belum mengenali gejala itu. Coba pilih kategori kerusakan atau tulis gejala dengan kata lain.', 0),
(NULL, 'Laptop nyala tapi tidak bisa masuk windows', 'Saya catat: bootloop. Apakah muncul pesan no bootable device?', 1);

-- ============================================================
-- Selesai
-- ============================================================
