<?php

declare(strict_types=1);

/**
 * Kumpulan query database yang dipakai halaman publik, admin, dan mesin diagnosis.
 */
class Repository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    // ---------------------------------------------------------
    // Kategori dan gejala
    // ---------------------------------------------------------

    public function categories(): array
    {
        return $this->pdo->query('SELECT * FROM categories ORDER BY urutan, id')->fetchAll();
    }

    public function category(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function symptomsByCategory(?int $categoryId = null, bool $aktifSaja = true): array
    {
        $sql = 'SELECT s.*, c.nama_kategori, c.slug AS kategori_slug
                FROM symptoms s
                JOIN categories c ON c.id = s.category_id
                WHERE 1 = 1';
        $params = [];
        if ($categoryId !== null) {
            $sql .= ' AND s.category_id = ?';
            $params[] = $categoryId;
        }
        if ($aktifSaja) {
            $sql .= ' AND s.aktif = 1';
        }
        $sql .= ' ORDER BY c.urutan, s.kode';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Kategori beserta daftar gejalanya, siap dipakai halaman diagnosis. */
    public function symptomsGrouped(bool $aktifSaja = true): array
    {
        $categories = $this->categories();
        $symptoms = $this->symptomsByCategory(null, $aktifSaja);
        $grouped = [];
        foreach ($categories as $category) {
            $category['symptoms'] = [];
            $grouped[$category['id']] = $category;
        }
        foreach ($symptoms as $symptom) {
            $grouped[(int) $symptom['category_id']]['symptoms'][] = $symptom;
        }
        return array_values($grouped);
    }

    public function symptomsByIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM symptoms WHERE id IN ($in) ORDER BY kode");
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    public function allSymptoms(bool $aktifSaja = true): array
    {
        return $this->symptomsByCategory(null, $aktifSaja);
    }

    // ---------------------------------------------------------
    // Penyebab, aturan, solusi
    // ---------------------------------------------------------

    public function causes(): array
    {
        return $this->pdo->query(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM rules r WHERE r.cause_id = c.id) AS jumlah_aturan,
                    (SELECT COUNT(*) FROM solutions s WHERE s.cause_id = c.id) AS jumlah_solusi
             FROM causes c ORDER BY c.kode'
        )->fetchAll();
    }

    public function cause(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM causes WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Aturan aktif beserta daftar id gejala penyusunnya. */
    public function rulesWithSymptoms(bool $aktifSaja = true): array
    {
        $sql = 'SELECT r.*, c.nama_penyebab, c.kode AS kode_penyebab, c.tingkat_risiko, c.deskripsi
                FROM rules r
                JOIN causes c ON c.id = r.cause_id';
        if ($aktifSaja) {
            $sql .= ' WHERE r.aktif = 1';
        }
        $sql .= ' ORDER BY r.kode';
        $rules = $this->pdo->query($sql)->fetchAll();

        $map = [];
        foreach ($this->pdo->query('SELECT rule_id, symptom_id FROM rule_symptoms')->fetchAll() as $row) {
            $map[(int) $row['rule_id']][] = (int) $row['symptom_id'];
        }
        foreach ($rules as &$rule) {
            $rule['symptom_ids'] = $map[(int) $rule['id']] ?? [];
        }
        unset($rule);
        return $rules;
    }

    public function solutionsFor(int $causeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM solutions WHERE cause_id = ? ORDER BY urutan, id'
        );
        $stmt->execute([$causeId]);
        return $stmt->fetchAll();
    }

    // ---------------------------------------------------------
    // Riwayat diagnosis
    // ---------------------------------------------------------

    public function saveDiagnosis(?int $userId, array $symptomIds, array $hasil, ?string $ringkasan = null): int
    {
        $this->pdo->beginTransaction();
        try {
            $teratas = $hasil[0]['nama_penyebab'] ?? null;
            $stmt = $this->pdo->prepare(
                'INSERT INTO diagnoses (user_id, hasil_teratas, ringkasan) VALUES (?, ?, ?)'
            );
            $stmt->execute([$userId, $teratas, $ringkasan]);
            $diagnosisId = (int) $this->pdo->lastInsertId();

            $detail = $this->pdo->prepare(
                'INSERT IGNORE INTO diagnosis_details (diagnosis_id, symptom_id, jawaban) VALUES (?, ?, 1)'
            );
            foreach ($symptomIds as $symptomId) {
                $detail->execute([$diagnosisId, (int) $symptomId]);
            }

            $result = $this->pdo->prepare(
                'INSERT INTO diagnosis_results (diagnosis_id, cause_id, skor, urutan) VALUES (?, ?, ?, ?)'
            );
            foreach (array_slice($hasil, 0, 3) as $index => $item) {
                $result->execute([$diagnosisId, (int) $item['cause_id'], (float) $item['skor'], $index + 1]);
            }

            $this->pdo->commit();
            return $diagnosisId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function diagnosesForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.*,
                    (SELECT skor FROM diagnosis_results r WHERE r.diagnosis_id = d.id ORDER BY urutan LIMIT 1) AS skor_teratas,
                    (SELECT COUNT(*) FROM diagnosis_details dd WHERE dd.diagnosis_id = d.id) AS jumlah_gejala
             FROM diagnoses d
             WHERE d.user_id = ?
             ORDER BY d.waktu DESC, d.id DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function diagnosis(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM diagnoses WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function diagnosisResults(int $diagnosisId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT dr.*, c.nama_penyebab, c.deskripsi, c.tingkat_risiko
             FROM diagnosis_results dr
             JOIN causes c ON c.id = dr.cause_id
             WHERE dr.diagnosis_id = ?
             ORDER BY dr.urutan, dr.id'
        );
        $stmt->execute([$diagnosisId]);
        return $stmt->fetchAll();
    }

    public function diagnosisSymptoms(int $diagnosisId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, dd.jawaban
             FROM diagnosis_details dd
             JOIN symptoms s ON s.id = dd.symptom_id
             WHERE dd.diagnosis_id = ?
             ORDER BY s.kode'
        );
        $stmt->execute([$diagnosisId]);
        return $stmt->fetchAll();
    }

    // ---------------------------------------------------------
    // Umpan balik diagnosis
    // ---------------------------------------------------------

    /** Simpan atau perbarui umpan balik untuk satu diagnosis. */
    public function saveFeedback(int $diagnosisId, ?int $userId, string $penilaian, ?string $catatan): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO diagnosis_feedback (diagnosis_id, user_id, penilaian, catatan)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                penilaian = VALUES(penilaian),
                catatan = VALUES(catatan),
                waktu = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$diagnosisId, $userId, $penilaian, $catatan]);
    }

    public function feedbackForDiagnosis(int $diagnosisId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM diagnosis_feedback WHERE diagnosis_id = ?'
        );
        $stmt->execute([$diagnosisId]);
        return $stmt->fetch() ?: null;
    }

    public function feedbackList(int $limit = 200): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.*, d.hasil_teratas, d.waktu AS waktu_diagnosis,
                    u.nama, c.nama_penyebab
             FROM diagnosis_feedback f
             JOIN diagnoses d ON d.id = f.diagnosis_id
             LEFT JOIN users u ON u.id = COALESCE(f.user_id, d.user_id)
             LEFT JOIN diagnosis_results dr
                ON dr.diagnosis_id = d.id AND dr.urutan = 1
             LEFT JOIN causes c ON c.id = dr.cause_id
             ORDER BY f.waktu DESC, f.id DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function feedbackStats(): array
    {
        $row = $this->pdo->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(penilaian = 'tepat'), 0) AS tepat,
                    COALESCE(SUM(penilaian = 'tidak_tepat'), 0) AS tidak_tepat
             FROM diagnosis_feedback"
        )->fetch() ?: [];

        $total = (int) ($row['total'] ?? 0);
        $tepat = (int) ($row['tepat'] ?? 0);

        return [
            'total'       => $total,
            'tepat'       => $tepat,
            'tidak_tepat' => (int) ($row['tidak_tepat'] ?? 0),
            'persen_tepat' => $total > 0 ? (int) round($tepat / $total * 100) : 0,
        ];
    }

    /** Penyebab yang paling sering dinilai kurang tepat oleh pengguna. */
    public function causesWithNegativeFeedback(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.nama_penyebab, COUNT(*) AS jumlah
             FROM diagnosis_feedback f
             JOIN diagnosis_results dr
                ON dr.diagnosis_id = f.diagnosis_id AND dr.urutan = 1
             JOIN causes c ON c.id = dr.cause_id
             WHERE f.penilaian = 'tidak_tepat'
             GROUP BY c.id, c.nama_penyebab
             ORDER BY jumlah DESC, c.nama_penyebab
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ---------------------------------------------------------
    // Artikel
    // ---------------------------------------------------------

    public function articles(?int $categoryId = null): array
    {
        $sql = 'SELECT a.*, c.nama_kategori, c.slug AS kategori_slug
                FROM articles a
                LEFT JOIN categories c ON c.id = a.category_id';
        $params = [];
        if ($categoryId !== null) {
            $sql .= ' WHERE a.category_id = ?';
            $params[] = $categoryId;
        }
        $sql .= ' ORDER BY a.dibuat_pada DESC, a.id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function articleBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, c.nama_kategori, c.slug AS kategori_slug
             FROM articles a LEFT JOIN categories c ON c.id = a.category_id
             WHERE a.slug = ?'
        );
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function article(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM articles WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ---------------------------------------------------------
    // Chatbot dan statistik
    // ---------------------------------------------------------

    public function logChat(?int $userId, string $pesan, string $balasan, bool $dikenali): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO chat_logs (user_id, pesan, balasan, dikenali) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $pesan, $balasan, $dikenali ? 1 : 0]);
    }

    public function stats(): array
    {
        return [
            'gejala'     => (int) $this->pdo->query('SELECT COUNT(*) FROM symptoms')->fetchColumn(),
            'penyebab'   => (int) $this->pdo->query('SELECT COUNT(*) FROM causes')->fetchColumn(),
            'aturan'     => (int) $this->pdo->query('SELECT COUNT(*) FROM rules')->fetchColumn(),
            'artikel'    => (int) $this->pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn(),
            'diagnosis'  => (int) $this->pdo->query('SELECT COUNT(*) FROM diagnoses')->fetchColumn(),
            'umpan_balik' => (int) $this->pdo->query('SELECT COUNT(*) FROM diagnosis_feedback')->fetchColumn(),
            'pengguna'   => (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'log_tidak_dikenali' => (int) $this->pdo->query('SELECT COUNT(*) FROM chat_logs WHERE dikenali = 0')->fetchColumn(),
        ];
    }

    public function topCauses(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.nama_penyebab, COUNT(*) AS jumlah
             FROM diagnosis_results dr
             JOIN causes c ON c.id = dr.cause_id
             WHERE dr.urutan = 1
             GROUP BY dr.cause_id, c.nama_penyebab
             ORDER BY jumlah DESC, c.nama_penyebab
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function topSymptoms(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.kode, s.pertanyaan, COUNT(*) AS jumlah
             FROM diagnosis_details dd
             JOIN symptoms s ON s.id = dd.symptom_id
             GROUP BY dd.symptom_id, s.kode, s.pertanyaan
             ORDER BY jumlah DESC, s.kode
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function unrecognizedLogs(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.*, u.nama
             FROM chat_logs l LEFT JOIN users u ON u.id = l.user_id
             WHERE l.dikenali = 0
             ORDER BY l.waktu DESC, l.id DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
