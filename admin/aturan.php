<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
require_once __DIR__ . '/_layout.php';

$mode = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? 0);
$error = '';
$fromPost = false;

$kategori = $repo->symptomsGrouped(false);
$penyebab = $repo->causes();

if (is_post()) {
    csrf_verify();

    if (($_POST['_action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM rules WHERE id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        flash('success', 'Aturan berhasil dihapus.');
        redirect('/admin/aturan.php');
    }

    $fromPost = true;
    $id = (int) ($_POST['id'] ?? 0);
    $mode = $id > 0 ? 'edit' : 'new';

    $causeId = (int) ($_POST['cause_id'] ?? 0);
    $kode = post_str('kode');
    $bobot = (float) ($_POST['bobot'] ?? 0);
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    $gejala = post_ids('symptoms');

    if ($causeId <= 0) {
        $error = 'Pilih penyebab terlebih dahulu.';
    } elseif ($kode === '') {
        $error = 'Kode aturan wajib diisi.';
    } elseif ($bobot < 0 || $bobot > 1) {
        $error = 'Bobot harus berada di antara 0 dan 1.';
    } elseif (!$gejala) {
        $error = 'Pilih minimal satu gejala untuk aturan ini.';
    }

    if ($error === '') {
        try {
            $pdo->beginTransaction();

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE rules SET cause_id = ?, kode = ?, bobot = ?, aktif = ? WHERE id = ?'
                );
                $stmt->execute([$causeId, $kode, $bobot, $aktif, $id]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO rules (cause_id, kode, bobot, aktif) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$causeId, $kode, $bobot, $aktif]);
                $id = (int) $pdo->lastInsertId();
            }

            $hapus = $pdo->prepare('DELETE FROM rule_symptoms WHERE rule_id = ?');
            $hapus->execute([$id]);

            $tambah = $pdo->prepare('INSERT INTO rule_symptoms (rule_id, symptom_id) VALUES (?, ?)');
            foreach ($gejala as $symptomId) {
                $tambah->execute([$id, $symptomId]);
            }

            $pdo->commit();
            flash('success', 'Aturan berhasil disimpan.');
            redirect('/admin/aturan.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = $e->getCode() === '23000'
                ? 'Kode aturan sudah dipakai. Gunakan kode lain.'
                : 'Terjadi kesalahan saat menyimpan aturan.';
        }
    }
}

$baris = [];
if ($mode === 'edit' && $id > 0 && !$fromPost) {
    $stmt = $pdo->prepare('SELECT * FROM rules WHERE id = ?');
    $stmt->execute([$id]);
    $baris = $stmt->fetch() ?: [];
    if (!$baris) {
        flash('error', 'Aturan tidak ditemukan.');
        redirect('/admin/aturan.php');
    }
}

$terpilih = [];
if ($fromPost) {
    $terpilih = post_ids('symptoms');
} elseif ($baris) {
    $stmt = $pdo->prepare('SELECT symptom_id FROM rule_symptoms WHERE rule_id = ?');
    $stmt->execute([$id]);
    $terpilih = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

$judul = $mode === 'list' ? 'Aturan diagnosis' : ($mode === 'edit' ? 'Edit aturan' : 'Tambah aturan');
$subjudul = 'Aturan menghubungkan kombinasi gejala dengan penyebab dan bobot kepastian.';
$aksi = $mode === 'list'
    ? '<a class="btn btn-primary btn-sm" href="?action=new">' . admin_icon('tambah') . ' Tambah</a>'
    : '<a class="btn btn-ghost btn-sm" href="' . url('/admin/aturan.php') . '">Kembali</a>';

admin_header($judul, 'aturan.php', $subjudul, $aksi);
?>

<?php if ($error !== ''): ?>
    <div class="flash flash-error" style="margin-bottom:1.2rem"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($mode === 'list'): ?>
    <?php
    $daftar = $pdo->query(
        'SELECT r.*, c.kode AS kode_penyebab, c.nama_penyebab,
                (SELECT COUNT(*) FROM rule_symptoms rs WHERE rs.rule_id = r.id) AS jumlah_gejala
         FROM rules r JOIN causes c ON c.id = r.cause_id
         ORDER BY r.kode'
    )->fetchAll();

    $peta = [];
    $relasi = $pdo->query(
        'SELECT rs.rule_id, s.kode FROM rule_symptoms rs JOIN symptoms s ON s.id = rs.symptom_id ORDER BY s.kode'
    )->fetchAll();
    foreach ($relasi as $rel) {
        $peta[(int) $rel['rule_id']][] = $rel['kode'];
    }
    ?>
    <div class="table-wrap">
        <?php if (!$daftar): ?>
            <div class="table-empty">Belum ada aturan.</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Penyebab</th>
                        <th>Gejala wajib</th>
                        <th>Bobot</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftar as $item): ?>
                        <tr>
                            <td class="mono"><?= e($item['kode']) ?></td>
                            <td><?= e($item['kode_penyebab'] . ' - ' . $item['nama_penyebab']) ?></td>
                            <td style="max-width:320px">
                                <span class="small"><?= e(implode(', ', $peta[(int) $item['id']] ?? [])) ?></span>
                                <span class="muted small">(<?= (int) $item['jumlah_gejala'] ?>)</span>
                            </td>
                            <td><?= e(number_format((float) $item['bobot'], 2, ',', '.')) ?></td>
                            <td><?= (int) $item['aktif'] === 1 ? 'Aktif' : 'Nonaktif' ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-outline btn-sm" href="?action=edit&amp;id=<?= (int) $item['id'] ?>">Edit</a>
                                    <form method="post" data-confirm="Hapus aturan ini?" style="display:inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="_action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php
    $nilaiCause = $fromPost ? (int) ($_POST['cause_id'] ?? 0) : (int) ($baris['cause_id'] ?? 0);
    $nilaiKode = $fromPost ? post_str('kode') : (string) ($baris['kode'] ?? '');
    $nilaiBobot = $fromPost ? (string) ($_POST['bobot'] ?? '0.50') : (string) ($baris['bobot'] ?? '0.50');
    $nilaiAktif = $fromPost ? isset($_POST['aktif']) : (int) ($baris['aktif'] ?? 1) === 1;
    ?>

    <form method="post" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id > 0 ? $id : '' ?>">

        <div class="field-row">
            <div class="field">
                <label for="field-kode">Kode aturan <span style="color:var(--risk-tinggi)">*</span></label>
                <input type="text" id="field-kode" name="kode" value="<?= e($nilaiKode) ?>" placeholder="R31">
            </div>

            <div class="field">
                <label for="field-bobot">Bobot kepastian <span style="color:var(--risk-tinggi)">*</span></label>
                <input type="number" id="field-bobot" name="bobot" value="<?= e($nilaiBobot) ?>"
                       min="0" max="1" step="0.01">
                <span class="hint">Antara 0 dan 1, misalnya 0,80 untuk aturan yang kuat.</span>
            </div>
        </div>

        <div class="field">
            <label for="field-cause">Penyebab <span style="color:var(--risk-tinggi)">*</span></label>
            <select id="field-cause" name="cause_id">
                <option value="">Pilih penyebab</option>
                <?php foreach ($penyebab as $item): ?>
                    <option value="<?= (int) $item['id'] ?>" <?= $nilaiCause === (int) $item['id'] ? 'selected' : '' ?>>
                        <?= e($item['kode'] . ' - ' . $item['nama_penyebab']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Gejala penyusun <span style="color:var(--risk-tinggi)">*</span></label>
            <?php foreach ($kategori as $kelompok): ?>
                <?php if (!$kelompok['symptoms']) { continue; } ?>
                <div class="symptom-picker-group">
                    <h3><?= e($kelompok['nama_kategori']) ?></h3>
                    <div class="checkbox-grid">
                        <?php foreach ($kelompok['symptoms'] as $gejala): ?>
                            <label class="field-check" for="gejala-<?= (int) $gejala['id'] ?>">
                                <input type="checkbox" id="gejala-<?= (int) $gejala['id'] ?>"
                                       name="symptoms[]" value="<?= (int) $gejala['id'] ?>"
                                       <?= in_array((int) $gejala['id'], $terpilih, true) ? 'checked' : '' ?>>
                                <span><span class="mono"><?= e($gejala['kode']) ?></span> <?= e($gejala['pertanyaan']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <span class="hint">Aturan dianggap cocok sebagian bila hanya sebagian gejala ini terpenuhi.</span>
        </div>

        <div class="field">
            <label class="field-check" for="field-aktif">
                <input type="checkbox" id="field-aktif" name="aktif" value="1" <?= $nilaiAktif ? 'checked' : '' ?>>
                <span>Aturan aktif dipakai dalam diagnosis</span>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a class="btn btn-ghost" href="<?= url('/admin/aturan.php') ?>">Batal</a>
        </div>
    </form>
<?php endif; ?>

<?php admin_footer(); ?>
