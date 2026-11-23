-- ============================================================
-- Migrasi FIXIT: tabel umpan balik diagnosis
-- Jalankan sekali pada database yang sudah terpasang:
--   mysql -u root fixit < database/migrasi_umpan_balik.sql
-- ============================================================

USE fixit;

CREATE TABLE IF NOT EXISTS diagnosis_feedback (
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
