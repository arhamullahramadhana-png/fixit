<?php

declare(strict_types=1);

/**
 * Mesin CRUD sederhana untuk panel admin.
 *
 * Setiap halaman admin mendefinisikan $crud lalu menyertakan file ini.
 * Kolom tabel dan nama field berasal dari konfigurasi internal, bukan input pengguna.
 */

require_once __DIR__ . '/_layout.php';

$table = $crud['table'];
$pk = $crud['primary'] ?? 'id';
$fields = $crud['fields'];
$self = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$mode = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? 0);
$error = '';
$fromPost = false;
$row = [];

if (is_post()) {
    csrf_verify();

    if (($_POST['_action'] ?? '') === 'delete') {
        $hapusId = (int) ($_POST['id'] ?? 0);
        if ($hapusId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$pk} = ?");
                $stmt->execute([$hapusId]);
                flash('success', 'Data berhasil dihapus.');
            } catch (PDOException $e) {
                flash('error', 'Data tidak dapat dihapus karena masih dipakai data lain.');
            }
        }
        redirect('/admin/' . $self);
    }

    $fromPost = true;
    $id = (int) ($_POST['id'] ?? 0);
    $mode = $id > 0 ? 'edit' : 'new';
    $data = [];

    foreach ($fields as $field) {
        $nama = $field['name'];
        $tipe = $field['type'] ?? 'text';

        if ($tipe === 'file') {
            $hasilUpload = crud_upload($field);
            if ($hasilUpload['error'] !== '') {
                $error = $hasilUpload['error'];
            }
            if ($hasilUpload['path'] !== null) {
                $data[$nama] = $hasilUpload['path'];
            }
            continue;
        }

        if ($tipe === 'checkbox') {
            $data[$nama] = isset($_POST[$nama]) ? 1 : 0;
            continue;
        }

        $nilai = isset($_POST[$nama]) && is_string($_POST[$nama]) ? trim($_POST[$nama]) : '';

        if (!empty($field['required']) && $nilai === '') {
            $error = 'Kolom ' . $field['label'] . ' wajib diisi.';
            break;
        }

        if ($nilai === '' && !empty($field['nullable'])) {
            $data[$nama] = null;
            continue;
        }

        if ($tipe === 'number') {
            if ($nilai === '') {
                $nilai = (string) ($field['default'] ?? 0);
            }
            if (!is_numeric($nilai)) {
                $error = 'Kolom ' . $field['label'] . ' harus berupa angka.';
                break;
            }
            $nilai = (string) (0 + $nilai);
            if ($nilai < ($field['min'] ?? -INF) || $nilai > ($field['max'] ?? INF)) {
                $error = 'Nilai ' . $field['label'] . ' di luar rentang yang diizinkan.';
                break;
            }
        }

        $data[$nama] = $nilai;
    }

    if ($error === '') {
        $kolom = array_keys($data);

        try {
            if ($id > 0) {
                $set = implode(', ', array_map(static fn (string $kolom): string => "{$kolom} = ?", $kolom));
                $sql = "UPDATE {$table} SET {$set} WHERE {$pk} = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([...array_values($data), $id]);
            } else {
                $daftarKolom = implode(', ', $kolom);
                $placeholder = implode(', ', array_fill(0, count($kolom), '?'));
                $stmt = $pdo->prepare("INSERT INTO {$table} ({$daftarKolom}) VALUES ({$placeholder})");
                $stmt->execute(array_values($data));
            }

            flash('success', $id > 0 ? 'Perubahan berhasil disimpan.' : 'Data baru berhasil ditambahkan.');
            redirect('/admin/' . $self);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Data tidak dapat disimpan. Kemungkinan kode atau slug sudah dipakai, atau relasi yang dipilih tidak valid.';
            } else {
                error_log('CRUD error: ' . $e->getMessage());
                $error = 'Terjadi kesalahan saat menyimpan data.';
            }
        }
    }
}

if ($mode === 'edit' && $id > 0 && !$row) {
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$pk} = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch() ?: [];
    if (!$row) {
        flash('error', 'Data yang ingin diedit tidak ditemukan.');
        redirect('/admin/' . $self);
    }
}

/**
 * Tangani upload gambar artikel. Mengembalikan path relatif atau null.
 *
 * @return array{path: ?string, error: string}
 */
function crud_upload(array $field): array
{
    $nama = $field['name'];
    $berkas = $_FILES[$nama] ?? null;

    if (!$berkas || ($berkas['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => ''];
    }

    if ($berkas['error'] !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'Berkas gagal diunggah. Coba lagi.'];
    }

    if ($berkas['size'] > 2 * 1024 * 1024) {
        return ['path' => null, 'error' => 'Ukuran gambar maksimal 2 MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($berkas['tmp_name']) ?: '';
    $jenis = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (!isset($jenis[$mime])) {
        return ['path' => null, 'error' => 'Format gambar harus JPG, PNG, WebP, atau GIF.'];
    }

    $folder = APP_ROOT . '/assets/img';
    if (!is_dir($folder)) {
        mkdir($folder, 0775, true);
    }

    $berkasBaru = 'unggahan-' . uniqid('', true) . '.' . $jenis[$mime];
    if (!move_uploaded_file($berkas['tmp_name'], $folder . '/' . $berkasBaru)) {
        return ['path' => null, 'error' => 'Gambar gagal disimpan ke folder aset.'];
    }

    return ['path' => 'assets/img/' . $berkasBaru, 'error' => ''];
}

function crud_value(array $field, array $row, bool $fromPost): string
{
    $nama = $field['name'];

    if ($fromPost) {
        if (($field['type'] ?? 'text') === 'checkbox') {
            return isset($_POST[$nama]) ? '1' : '0';
        }
        return isset($_POST[$nama]) && is_string($_POST[$nama]) ? $_POST[$nama] : '';
    }

    if (array_key_exists($nama, $row)) {
        return (string) $row[$nama];
    }

    if (isset($_GET[$nama])) {
        return (string) $_GET[$nama];
    }

    return (string) ($field['default'] ?? '');
}

function crud_render_field(array $field, array $row, bool $fromPost): void
{
    $nama = $field['name'];
    $label = $field['label'];
    $tipe = $field['type'] ?? 'text';
    $nilai = crud_value($field, $row, $fromPost);
    $wajib = !empty($field['required']) ? ' <span style="color:var(--risk-tinggi)">*</span>' : '';
    $idField = 'field-' . $nama;
    ?>
    <div class="field">
        <?php if ($tipe === 'checkbox'): ?>
            <label class="field-check" for="<?= e($idField) ?>">
                <input type="checkbox" id="<?= e($idField) ?>" name="<?= e($nama) ?>" value="1"
                    <?= $nilai === '1' ? 'checked' : '' ?>>
                <span><?= e($label) ?><?= $wajib ?></span>
            </label>
            <?php if (!empty($field['hint'])): ?>
                <span class="hint"><?= e($field['hint']) ?></span>
            <?php endif; ?>
        <?php else: ?>
            <label for="<?= e($idField) ?>"><?= e($label) ?><?= $wajib ?></label>

            <?php if ($tipe === 'textarea'): ?>
                <textarea id="<?= e($idField) ?>" name="<?= e($nama) ?>"
                          rows="<?= (int) ($field['rows'] ?? 4) ?>"
                          placeholder="<?= e($field['placeholder'] ?? '') ?>"><?= e($nilai) ?></textarea>
            <?php elseif ($tipe === 'select'): ?>
                <?php $opsi = is_callable($field['options'] ?? null) ? ($field['options'])() : ($field['options'] ?? []); ?>
                <select id="<?= e($idField) ?>" name="<?= e($nama) ?>">
                    <?php if (!empty($field['empty'])): ?>
                        <option value=""><?= e($field['empty']) ?></option>
                    <?php endif; ?>
                    <?php foreach ($opsi as $value => $teks): ?>
                        <option value="<?= e((string) $value) ?>" <?= (string) $value === $nilai ? 'selected' : '' ?>>
                            <?= e((string) $teks) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($tipe === 'file'): ?>
                <input type="file" id="<?= e($idField) ?>" name="<?= e($nama) ?>"
                       data-file-preview="preview-<?= e($nama) ?>"
                       accept="image/jpeg,image/png,image/webp,image/gif">
                <?php if (!empty($row[$nama])): ?>
                    <span class="hint">Gambar saat ini:</span>
                    <img src="<?= e(article_image_url($row[$nama])) ?>" alt=""
                         style="max-height:100px;border-radius:8px;border:1px solid var(--border);margin-top:.4rem">
                <?php endif; ?>
                <img id="preview-<?= e($nama) ?>" alt="" hidden
                     style="max-height:120px;border-radius:8px;border:1px solid var(--border);margin-top:.4rem">
            <?php else: ?>
                <input type="<?= e($tipe) ?>"
                       id="<?= e($idField) ?>"
                       name="<?= e($nama) ?>"
                       value="<?= e($nilai) ?>"
                       <?= isset($field['min']) ? 'min="' . e((string) $field['min']) . '"' : '' ?>
                       <?= isset($field['max']) ? 'max="' . e((string) $field['max']) . '"' : '' ?>
                       <?= isset($field['step']) ? 'step="' . e((string) $field['step']) . '"' : '' ?>
                       <?= isset($field['slug_source']) ? 'data-slug-source="field-' . e($field['slug_source']) . '"' : '' ?>
                       placeholder="<?= e($field['placeholder'] ?? '') ?>">
            <?php endif; ?>

            <?php if (!empty($field['hint'])): ?>
                <span class="hint"><?= e($field['hint']) ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

$judul = $mode === 'list'
    ? $crud['judul']
    : ($mode === 'edit' ? 'Edit ' . $crud['judul'] : 'Tambah ' . $crud['judul']);

$aksi = $mode === 'list'
    ? '<a class="btn btn-primary btn-sm" href="?action=new">' . admin_icon('tambah') . ' Tambah</a>'
    : '<a class="btn btn-ghost btn-sm" href="' . url('/admin/' . $self) . '">Kembali</a>';

$konfirmasiHapus = (string) ($crud['delete_message'] ?? 'Hapus data ini? Tindakan tidak dapat dibatalkan.');

admin_header($judul, $self, $crud['subjudul'] ?? '', $aksi);
?>

<?php if ($error !== ''): ?>
    <div class="flash flash-error" style="margin-bottom:1.2rem"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($mode === 'list'): ?>
    <?php
    $sql = $crud['list_sql'] ?? "SELECT * FROM {$table} ORDER BY " . ($crud['order'] ?? "{$pk} DESC");
    $baris = $pdo->query($sql)->fetchAll();
    ?>
    <div class="table-wrap">
        <?php if (!$baris): ?>
            <div class="table-empty">Belum ada data. Klik tombol Tambah untuk membuat entri pertama.</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <?php foreach ($crud['list_columns'] as $kolom): ?>
                            <th><?= e($kolom['label']) ?></th>
                        <?php endforeach; ?>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($baris as $item): ?>
                        <tr>
                            <?php foreach ($crud['list_columns'] as $kolom): ?>
                                <td><?= e((string) ($kolom['value'])($item)) ?></td>
                            <?php endforeach; ?>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-outline btn-sm" href="?action=edit&amp;id=<?= (int) $item['id'] ?>">Edit</a>
                                    <form method="post" data-confirm="<?= e($konfirmasiHapus) ?>" style="display:inline">
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
    <form method="post" class="form-card" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id > 0 ? $id : '' ?>">

        <?php foreach ($fields as $field): ?>
            <?php crud_render_field($field, $row, $fromPost); ?>
        <?php endforeach; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a class="btn btn-ghost" href="<?= url('/admin/' . $self) ?>">Batal</a>
        </div>
    </form>
<?php endif; ?>

<?php admin_footer(); ?>
