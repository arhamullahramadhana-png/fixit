<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
require_once __DIR__ . '/_layout.php';

if (is_post()) {
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    $aksi = $_POST['_action'] ?? '';

    if ($id > 0 && $aksi === 'tandai') {
        $stmt = $pdo->prepare('UPDATE chat_logs SET dikenali = 1 WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Log ditandai sudah ditinjau.');
    } elseif ($id > 0 && $aksi === 'hapus') {
        $stmt = $pdo->prepare('DELETE FROM chat_logs WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Log dihapus.');
    }

    redirect('/admin/log_chat.php');
}

$logs = $repo->unrecognizedLogs(200);

admin_header(
    'Log chatbot',
    'log_chat.php',
    'Pesan yang belum dikenali chatbot. Tinjau untuk memperkaya kata kunci gejala.'
);
?>

<div class="table-wrap">
    <?php if (!$logs): ?>
        <div class="table-empty">Tidak ada pesan yang belum dikenali. Bagus!</div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Pesan</th>
                    <th>Balasan bot</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="nowrap small"><?= e(format_tanggal($log['waktu'], true)) ?></td>
                        <td class="small"><?= e($log['nama'] ?? 'Tamu') ?></td>
                        <td style="max-width:280px"><?= e($log['pesan']) ?></td>
                        <td style="max-width:280px" class="small muted"><?= e(excerpt((string) $log['balasan'], 90)) ?></td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-outline btn-sm"
                                   href="<?= url('/admin/gejala.php?action=new&pertanyaan=' . urlencode((string) $log['pesan']) . '&kata_kunci=' . urlencode((string) $log['pesan'])) ?>">
                                    Buat gejala
                                </a>
                                <form method="post" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_action" value="tandai">
                                    <input type="hidden" name="id" value="<?= (int) $log['id'] ?>">
                                    <button type="submit" class="btn btn-ghost btn-sm">Tandai ditinjau</button>
                                </form>
                                <form method="post" data-confirm="Hapus log ini?" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_action" value="hapus">
                                    <input type="hidden" name="id" value="<?= (int) $log['id'] ?>">
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

<?php admin_footer(); ?>
