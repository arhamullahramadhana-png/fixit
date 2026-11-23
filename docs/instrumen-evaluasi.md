# Instrumen Evaluasi FIXIT

Dokumen ini berisi instrumen yang dapat dipakai untuk validasi ahli, pengujian akurasi diagnosis, dan pengukuran kegunaan aplikasi.

---

## 1. Lembar Validasi Ahli/Teknisi

**Nama validator:** ................................
**Instansi/Peran:** ................................
**Tanggal:** ................................

Berikan nilai 1–5 pada setiap aspek.

| No | Aspek | 1 | 2 | 3 | 4 | 5 | Catatan |
|---:|---|---|---|---|---|---|---|
| 1 | Kesesuaian gejala dengan kategori kerusakan | | | | | | |
| 2 | Kelengkapan gejala untuk setiap kategori | | | | | | |
| 3 | Ketepatan pasangan gejala dan penyebab pada aturan | | | | | | |
| 4 | Kewajaran bobot certainty factor | | | | | | |
| 5 | Kejelasan dan keamanan langkah solusi | | | | | | |
| 6 | Penandaan langkah yang membutuhkan teknisi | | | | | | |
| 7 | Kejelasan bahasa pada antarmuka pengguna | | | | | | |
| 8 | Kesesuaian hasil diagnosis dengan penilaian ahli | | | | | | |

**Rata-rata nilai:** ............ / 5

**Kriteria penilaian:**

| Rentang | Kategori |
|---|---|
| 4,21–5,00 | Sangat layak |
| 3,41–4,20 | Layak |
| 2,61–3,40 | Cukup layak |
| 1,81–2,60 | Kurang layak |
| 1,00–1,80 | Tidak layak |

**Masukan validator:**

................................................................................................................

................................................................................................................

---

## 2. Lembar Pengujian Akurasi Diagnosis

Isi satu baris untuk setiap kasus uji. Kolom "Hasil pakar" diisi sebelum menjalankan sistem agar tidak bias.

| No | Kasus/Gejala | Hasil diagnosis pakar | Hasil diagnosis FIXIT | Skor | Sesuai? |
|---:|---|---|---|---|---|
| 1 | | | | | |
| 2 | | | | | |
| 3 | | | | | |
| 4 | | | | | |
| 5 | | | | | |
| 6 | | | | | |
| 7 | | | | | |
| 8 | | | | | |
| 9 | | | | | |
| 10 | | | | | |

**Rumus akurasi:**

```text
Akurasi = (jumlah kasus sesuai / total kasus uji) x 100%
```

**Hasil:** ............ %

Target minimal yang disarankan: **80%** untuk penyebab teratas.

---

## 3. Kuesioner System Usability Scale (SUS)

Berikan nilai 1–5: **1 = sangat tidak setuju**, **5 = sangat setuju**.

| No | Pernyataan | 1 | 2 | 3 | 4 | 5 |
|---:|---|---|---|---|---|---|
| 1 | Saya ingin sering menggunakan aplikasi ini. | | | | | |
| 2 | Saya merasa aplikasi ini terlalu rumit. | | | | | |
| 3 | Saya merasa aplikasi ini mudah digunakan. | | | | | |
| 4 | Saya membutuhkan bantuan teknis untuk menggunakan aplikasi ini. | | | | | |
| 5 | Saya merasa bagian-bagian aplikasi ini berjalan dengan baik. | | | | | |
| 6 | Saya merasa terlalu banyak hal yang tidak konsisten pada aplikasi ini. | | | | | |
| 7 | Saya yakin orang lain akan cepat memahami aplikasi ini. | | | | | |
| 8 | Saya merasa aplikasi ini membingungkan saat digunakan. | | | | | |
| 9 | Saya merasa percaya diri saat menggunakan aplikasi ini. | | | | | |
| 10 | Saya perlu belajar banyak hal sebelum bisa memakai aplikasi ini. | | | | | |

### Cara Menghitung SUS

1. Untuk pertanyaan **ganjil** (1, 3, 5, 7, 9): nilai kontribusi = skor − 1.
2. Untuk pertanyaan **genap** (2, 4, 6, 8, 10): nilai kontribusi = 5 − skor.
3. Jumlahkan semua nilai kontribusi, lalu kalikan **2,5**.

```text
Skor SUS = total kontribusi x 2,5
```

**Rentang skor:** 0–100.

| Skor | Interpretasi |
|---|---|
| > 80 | Sangat baik |
| 68–80 | Baik |
| 51–67 | Cukup |
| < 51 | Perlu perbaikan |

---

## 4. Lembar Catatan Pengujian

| Tanggal | Penguji | Skenario | Hasil | Tindak lanjut |
|---|---|---|---|---|
| | | | | |
| | | | | |
| | | | | |
| | | | | |

---

## 5. Rekap Umpan Balik Aplikasi

Data dapat diambil dari menu **Admin > Umpan balik**. Kolom berikut membantu merangkum temuan.

| Penyebab | Jumlah dinilai tepat | Jumlah dinilai kurang tepat | Persentase tepat | Tindakan |
|---|---:|---:|---:|---|
| | | | | |
| | | | | |
| | | | | |

Tindakan yang dapat dilakukan bila suatu penyebab sering dinilai kurang tepat:

1. Periksa apakah aturan memuat gejala yang terlalu umum.
2. Tambahkan gejala pembeda yang lebih spesifik.
3. Turunkan bobot aturan yang terlalu luas.
4. Perbaiki solusi bila langkahnya kurang membantu.
5. Konsultasikan perubahan dengan teknisi.
