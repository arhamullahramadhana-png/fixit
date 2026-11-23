<?php

declare(strict_types=1);

/**
 * Chatbot konsultasi berbasis kata kunci.
 *
 * Chatbot mencocokkan pesan pengguna dengan kata kunci gejala, lalu memilih
 * pertanyaan lanjutan yang paling membedakan kandidat penyebab.
 */
class Chatbot
{
    private const MAKS_PERTANYAAN = 5;
    private const AMBANG_HASIL   = 0.75;

    /** @var array<int, array> */
    private array $symptoms = [];

    private array $state;

    public function __construct(
        private Repository $repo,
        private DiagnosisEngine $engine,
        private ?int $userId
    ) {
        foreach ($this->repo->allSymptoms(true) as $symptom) {
            $this->symptoms[(int) $symptom['id']] = $symptom;
        }

        $this->state = array_merge($this->blankState(), $_SESSION['chat'] ?? []);
        $this->state['symptoms'] = array_values(array_unique(array_map('intval', $this->state['symptoms'])));
        $this->state['negatives'] = array_values(array_unique(array_map('intval', $this->state['negatives'])));
        $this->state['asked'] = array_values(array_unique(array_map('intval', $this->state['asked'])));
    }

    private function blankState(): array
    {
        return [
            'symptoms' => [],
            'negatives' => [],
            'asked' => [],
            'pending' => null,
            'done' => false,
            'result' => null,
            'saved' => false,
            'messages' => [],
        ];
    }

    public function current(): array
    {
        if (!$this->state['messages']) {
            $this->addBot(
                'Halo! Saya asisten FIXIT. Ceritakan gejala yang Anda alami, misalnya '
                . '"laptop menyala tapi layar hitam" atau "kipas berisik dan panas".'
            );
            $this->save();
        }
        return $this->payload();
    }

    public function reset(): array
    {
        $this->state = $this->blankState();
        $this->save();
        return $this->current();
    }

    public function handleText(string $pesan): array
    {
        $pesan = trim($pesan);
        if ($pesan === '') {
            return $this->current();
        }

        $this->addUser($pesan);

        $teks = $this->normalize($pesan);

        if ($this->state['done']) {
            if (str_contains($teks, 'mulai ulang') || str_contains($teks, 'ulang')) {
                return $this->reset();
            }
            $this->addBot('Konsultasi sebelumnya sudah selesai. Tekan "Mulai ulang" untuk konsultasi baru.', [
                ['label' => 'Mulai ulang', 'text' => 'mulai ulang'],
            ]);
            $this->save();
            return $this->payload();
        }

        $dikenali = false;

        // Jawaban untuk pertanyaan terakhir.
        if ($this->state['pending'] !== null) {
            $pendingId = (int) $this->state['pending'];
            $sebutan = $this->sebutanPending($teks, $pendingId);

            if ($sebutan === 'yes') {
                $this->addSymptom($pendingId);
                $this->markAsked($pendingId);
                $this->state['pending'] = null;
                $dikenali = true;
            } elseif ($sebutan === 'no') {
                $this->addNegative($pendingId);
                $this->markAsked($pendingId);
                $this->state['pending'] = null;
                $dikenali = true;
            } elseif (!$this->menyebutGejala($teks)) {
                if ($this->isTidakTahu($teks)) {
                    $this->markAsked($pendingId);
                    $this->state['pending'] = null;
                    $dikenali = true;
                } elseif ($this->isYes($teks)) {
                    $this->addSymptom($pendingId);
                    $this->markAsked($pendingId);
                    $this->state['pending'] = null;
                    $dikenali = true;
                } elseif ($this->isNo($teks)) {
                    $this->addNegative($pendingId);
                    $this->markAsked($pendingId);
                    $this->state['pending'] = null;
                    $dikenali = true;
                }
            }
        }

        // Tetap coba ekstraksi bebas bila pengguna menulis lebih dari sekadar ya/tidak.
        $ditemukan = $this->extractSymptoms($teks);
        foreach ($ditemukan as $symptomId) {
            if (in_array($symptomId, $this->state['negatives'], true)) {
                continue;
            }
            $this->addSymptom($symptomId);
            $this->markAsked($symptomId);
            $dikenali = true;
        }

        $this->state['pending'] = null;

        if (!$dikenali) {
            $balasan = 'Maaf, saya belum mengenali gejala itu. Pilih kategori di bawah atau tulis dengan kata lain.';
            $this->addBot($balasan, $this->categoryQuickReplies());
            $this->repo->logChat($this->userId, $pesan, $balasan, false);
            $this->save();
            return $this->payload();
        }

        return $this->lanjut($pesan);
    }

    /** Pengguna memilih langsung satu gejala dari tombol pilihan. */
    public function handleSymptom(int $symptomId): array
    {
        $symptom = $this->symptoms[$symptomId] ?? null;
        if (!$symptom) {
            return $this->current();
        }
        $this->addUser($symptom['pertanyaan']);
        $this->addSymptom($symptomId);
        $this->markAsked($symptomId);
        $this->state['pending'] = null;
        return $this->lanjut($symptom['pertanyaan']);
    }

    /** Tampilkan pilihan gejala untuk satu kategori. */
    public function handleCategory(int $categoryId): array
    {
        $kategori = $this->repo->category($categoryId);
        if (!$kategori) {
            return $this->current();
        }
        $gejala = $this->repo->symptomsByCategory($categoryId, true);
        $quick = [];
        foreach (array_slice($gejala, 0, 8) as $item) {
            $quick[] = ['label' => $item['pertanyaan'], 'symptom_id' => (int) $item['id']];
        }
        $this->addBot(
            'Baik, ini gejala yang umum pada kategori ' . $kategori['nama_kategori']
            . '. Pilih yang Anda alami, atau tulis sendiri.',
            $quick
        );
        $this->save();
        return $this->payload();
    }

    // ---------------------------------------------------------
    // Inti percakapan
    // ---------------------------------------------------------

    private function lanjut(string $pesanTerakhir): array
    {
        $hasil = $this->engine->diagnose($this->state['symptoms']);
        $sudahTanya = count($this->state['asked']);
        $teratas = $hasil['hasil'][0]['skor'] ?? 0.0;
        $pertanyaan = $this->pilihPertanyaan($hasil);

        $cukup = $teratas >= self::AMBANG_HASIL
            || $sudahTanya >= self::MAKS_PERTANYAAN
            || $pertanyaan === null;

        if ($cukup && $hasil['hasil']) {
            return $this->selesaikan($hasil, $pesanTerakhir);
        }

        if ($pertanyaan !== null) {
            $this->state['pending'] = (int) $pertanyaan['id'];
            $balasan = 'Saya catat. ' . $pertanyaan['pertanyaan'] . '?';
            $this->addBot($balasan, [
                ['label' => 'Ya', 'text' => 'ya'],
                ['label' => 'Tidak', 'text' => 'tidak'],
                ['label' => 'Tidak tahu', 'text' => 'tidak tahu'],
            ]);
            $this->repo->logChat($this->userId, $pesanTerakhir, $balasan, true);
            $this->save();
            return $this->payload();
        }

        $balasan = 'Gejala yang tercatat belum cukup untuk menentukan penyebab. '
            . 'Coba tambahkan gejala lain, atau pilih kategori di bawah.';
        $this->addBot($balasan, $this->categoryQuickReplies());
        $this->repo->logChat($this->userId, $pesanTerakhir, $balasan, false);
        $this->save();
        return $this->payload();
    }

    private function selesaikan(array $hasil, string $pesanTerakhir): array
    {
        $items = [];
        foreach ($hasil['hasil'] as $item) {
            $item['solusi'] = $this->engine->solusi((int) $item['cause_id']);
            $items[] = $item;
        }

        $namaGejala = [];
        foreach ($this->state['symptoms'] as $id) {
            if (isset($this->symptoms[$id])) {
                $namaGejala[] = $this->symptoms[$id]['pertanyaan'];
            }
        }

        $ringkas = [];
        foreach ($items as $item) {
            $ringkas[] = $item['nama_penyebab'] . ' (' . score_persen($item['skor']) . '%)';
        }

        $this->state['result'] = [
            'items' => $items,
            'gejala' => $namaGejala,
            'diagnosis_id' => null,
        ];

        if ($this->userId !== null && !$this->state['saved']) {
            try {
                $this->state['result']['diagnosis_id'] = $this->repo->saveDiagnosis(
                    $this->userId,
                    $this->state['symptoms'],
                    $items,
                    'Konsultasi chatbot dengan ' . count($namaGejala) . ' gejala.'
                );
                $this->state['saved'] = true;
            } catch (Throwable $e) {
                // Riwayat bersifat tambahan; jangan gagalkan konsultasi.
            }
        }

        $this->state['done'] = true;

        $balasan = 'Kemungkinan penyebab: ' . implode(', ', $ringkas) . '. '
            . 'Solusi awal sudah saya tampilkan di bawah. Ingat, ini perkiraan awal, bukan diagnosis pasti.';
        $this->addBot($balasan, [['label' => 'Mulai ulang', 'text' => 'mulai ulang']]);
        $this->repo->logChat($this->userId, $pesanTerakhir, $balasan, true);
        $this->save();
        return $this->payload();
    }

    /**
     * Pilih gejala yang paling membedakan antar kandidat penyebab.
     */
    private function pilihPertanyaan(array $hasil): ?array
    {
        $kandidat = array_slice($hasil['semua'], 0, 3);
        if (!$kandidat) {
            return null;
        }

        $bobot = [];
        foreach ($kandidat as $peringkat => $cause) {
            $nilai = 3 - $peringkat;
            foreach ($this->engine->rules() as $rule) {
                if ((int) $rule['cause_id'] !== (int) $cause['cause_id']) {
                    continue;
                }
                foreach ($rule['symptom_ids'] as $symptomId) {
                    if ($this->sudahDibahas($symptomId)) {
                        continue;
                    }
                    $bobot[$symptomId] = ($bobot[$symptomId] ?? 0) + $nilai;
                }
            }
        }

        if (!$bobot) {
            return null;
        }

        arsort($bobot);
        $id = (int) array_key_first($bobot);
        return $this->symptoms[$id] ?? null;
    }

    private function sudahDibahas(int $symptomId): bool
    {
        return in_array($symptomId, $this->state['symptoms'], true)
            || in_array($symptomId, $this->state['negatives'], true)
            || in_array($symptomId, $this->state['asked'], true);
    }

    // ---------------------------------------------------------
    // Ekstraksi gejala
    // ---------------------------------------------------------

    private function extractSymptoms(string $teks): array
    {
        $ditemukan = [];
        foreach ($this->symptoms as $id => $symptom) {
            $kataKunci = array_filter(array_map('trim', explode(',', (string) $symptom['kata_kunci'])));
            foreach ($kataKunci as $kata) {
                $kata = mb_strtolower($kata);
                if ($kata === '') {
                    continue;
                }
                $posisi = mb_strpos($teks, $kata);
                if ($posisi === false) {
                    continue;
                }
                if ($this->dinegasikan($teks, $posisi)) {
                    continue;
                }
                $ditemukan[] = $id;
                break;
            }
        }
        return array_values(array_unique($ditemukan));
    }

    /**
     * Tentukan apakah pengguna menyebut gejala yang sedang ditanyakan.
     *
     * @return 'yes'|'no'|null
     */
    private function sebutanPending(string $teks, int $symptomId): ?string
    {
        $symptom = $this->symptoms[$symptomId] ?? null;
        if (!$symptom) {
            return null;
        }

        $disebut = false;
        $dinegasikan = false;
        foreach (array_filter(array_map('trim', explode(',', (string) $symptom['kata_kunci']))) as $kata) {
            $kata = mb_strtolower($kata);
            $posisi = mb_strpos($teks, $kata);
            if ($posisi === false) {
                continue;
            }
            $disebut = true;
            if ($this->dinegasikan($teks, $posisi)) {
                $dinegasikan = true;
            } else {
                return 'yes';
            }
        }

        return ($disebut && $dinegasikan) ? 'no' : null;
    }

    /** Apakah pesan menyebut gejala apa pun, termasuk yang dinegasikan. */
    private function menyebutGejala(string $teks): bool
    {
        foreach ($this->symptoms as $symptom) {
            foreach (array_filter(array_map('trim', explode(',', (string) $symptom['kata_kunci']))) as $kata) {
                $kata = mb_strtolower($kata);
                if ($kata !== '' && mb_strpos($teks, $kata) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    private function dinegasikan(string $teks, int $posisi): bool
    {
        $sebelum = mb_substr($teks, max(0, $posisi - 24), min(24, $posisi));
        return (bool) preg_match('/\b(tidak|tdk|nggak|gak|bukan|belum|tanpa|no)\b/', $sebelum);
    }

    private function normalize(string $teks): string
    {
        $teks = mb_strtolower($teks);
        $teks = preg_replace('/[^\p{L}\p{N}\s-]/u', ' ', $teks) ?? '';
        return trim(preg_replace('/\s+/', ' ', $teks) ?? '');
    }

    private function isYes(string $teks): bool
    {
        return (bool) preg_match('/\b(ya|iya|yup|betul|benar|ada|sudah|yes)\b/', $teks);
    }

    private function isNo(string $teks): bool
    {
        return (bool) preg_match('/\b(tidak|tdk|nggak|gak|bukan|belum|no)\b/', $teks);
    }

    private function isTidakTahu(string $teks): bool
    {
        return str_contains($teks, 'tidak tahu')
            || str_contains($teks, 'tidak yakin')
            || str_contains($teks, 'kurang tahu');
    }

    // ---------------------------------------------------------
    // Pembantu state dan pesan
    // ---------------------------------------------------------

    private function addSymptom(int $symptomId): void
    {
        if (!isset($this->symptoms[$symptomId])) {
            return;
        }
        if (!in_array($symptomId, $this->state['symptoms'], true)) {
            $this->state['symptoms'][] = $symptomId;
        }
        $this->state['negatives'] = array_values(array_diff($this->state['negatives'], [$symptomId]));
    }

    private function addNegative(int $symptomId): void
    {
        if (!in_array($symptomId, $this->state['negatives'], true)) {
            $this->state['negatives'][] = $symptomId;
        }
        $this->state['symptoms'] = array_values(array_diff($this->state['symptoms'], [$symptomId]));
    }

    private function markAsked(int $symptomId): void
    {
        if (!in_array($symptomId, $this->state['asked'], true)) {
            $this->state['asked'][] = $symptomId;
        }
    }

    private function addUser(string $teks): void
    {
        $this->state['messages'][] = ['from' => 'user', 'text' => $teks, 'quick' => []];
    }

    private function addBot(string $teks, array $quick = []): void
    {
        $this->state['messages'][] = ['from' => 'bot', 'text' => $teks, 'quick' => $quick];
    }

    private function categoryQuickReplies(): array
    {
        $quick = [];
        foreach ($this->repo->categories() as $kategori) {
            $quick[] = ['label' => $kategori['nama_kategori'], 'category_id' => (int) $kategori['id']];
        }
        return $quick;
    }

    private function payload(): array
    {
        return [
            'messages' => $this->state['messages'],
            'result' => $this->state['result'],
            'done' => $this->state['done'],
            'progress' => count($this->state['asked']) . '/' . self::MAKS_PERTANYAAN,
        ];
    }

    private function save(): void
    {
        $_SESSION['chat'] = $this->state;
    }
}
