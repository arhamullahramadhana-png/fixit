<?php

declare(strict_types=1);

/**
 * Mesin diagnosis rule-based dengan penggabungan certainty factor.
 *
 * Skor tiap aturan = (gejala cocok / gejala dibutuhkan) x bobot aturan.
 * Bila beberapa aturan mengarah ke penyebab yang sama, skor digabung:
 *   CF = CF1 + CF2 * (1 - CF1)
 */
class DiagnosisEngine
{
    /** @var array<int, array> */
    private array $rules = [];

    public function __construct(private PDO $pdo, private Repository $repo)
    {
        $this->rules = $this->repo->rulesWithSymptoms(true);
    }

    /** Semua aturan aktif, dipakai chatbot untuk memilih pertanyaan. */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * Hitung kemungkinan penyebab dari daftar id gejala.
     *
     * @return array{hasil: array, semua: array, total_gejala: int}
     */
    public function diagnose(array $symptomIds): array
    {
        $dipilih = [];
        foreach ($symptomIds as $id) {
            $dipilih[(int) $id] = true;
        }

        $perPenyebab = [];

        foreach ($this->rules as $rule) {
            $butuh = $rule['symptom_ids'];
            if (!$butuh) {
                continue;
            }

            $cocok = array_values(array_filter($butuh, static fn (int $id): bool => isset($dipilih[$id])));
            if (!$cocok) {
                continue;
            }

            $kecocokan = count($cocok) / count($butuh);
            $skorAturan = $kecocokan * (float) $rule['bobot'];
            $causeId = (int) $rule['cause_id'];

            if (!isset($perPenyebab[$causeId])) {
                $perPenyebab[$causeId] = [
                    'cause_id'       => $causeId,
                    'kode'           => $rule['kode_penyebab'] ?? '',
                    'nama_penyebab'  => $rule['nama_penyebab'],
                    'deskripsi'      => $rule['deskripsi'],
                    'tingkat_risiko' => $rule['tingkat_risiko'],
                    'aturan'         => [],
                ];
            }

            $perPenyebab[$causeId]['aturan'][] = [
                'kode'       => $rule['kode'],
                'bobot'      => (float) $rule['bobot'],
                'kecocokan'  => $kecocokan,
                'skor'       => $skorAturan,
                'gejala_cocok' => $cocok,
                'gejala_butuh' => $butuh,
            ];
        }

        $hasil = [];
        foreach ($perPenyebab as $causeId => $data) {
            usort(
                $data['aturan'],
                static fn (array $a, array $b): int => $b['skor'] <=> $a['skor']
            );

            $cf = 0.0;
            foreach ($data['aturan'] as $aturan) {
                $cf = $cf + $aturan['skor'] * (1 - $cf);
            }

            $data['skor'] = round($cf, 3);
            $data['jumlah_aturan_cocok'] = count($data['aturan']);
            if ($data['skor'] >= 0.25) {
                $hasil[] = $data;
            }
        }

        usort(
            $hasil,
            static fn (array $a, array $b): int =>
                $b['skor'] <=> $a['skor'] ?: strcmp($a['nama_penyebab'], $b['nama_penyebab'])
        );

        return [
            'hasil'         => array_slice($hasil, 0, 3),
            'semua'         => $hasil,
            'total_gejala'  => count($dipilih),
        ];
    }

    /** Ambil solusi bertahap untuk satu penyebab. */
    public function solusi(int $causeId): array
    {
        return $this->repo->solutionsFor($causeId);
    }
}
