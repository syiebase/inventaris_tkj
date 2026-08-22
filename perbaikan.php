<?php
$currentPage = 'perbaikan';
$pageTitle = 'Log Perbaikan Lab';
$pageSubtitle = 'Catatan perawatan dan status servis teknisi peralatan TKJ SMKN 1 Pleret';

require_once __DIR__ . '/includes/header.php';

$sql = "
    SELECT m.*, i.name as item_name, i.code_sku, i.brand_model, l.name as location_name
    FROM maintenance m
    JOIN items i ON m.item_id = i.id
    LEFT JOIN locations l ON i.location_id = l.id
    ORDER BY m.start_date DESC, m.created_at DESC
";
$stmt = $pdo->query($sql);
$maintenances = $stmt->fetchAll();
?>

<div class="glass-card">
  <div class="card-header flex-header">
    <h3><i data-lucide="wrench"></i> Log & Status Perbaikan Laboratorium</h3>
    <button class="btn btn-rose" onclick="openModal('modal-maint')">
      <i data-lucide="plus"></i> Catat Perbaikan Baru
    </button>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Perangkat / SKU</th>
          <th>Lokasi Lab</th>
          <th>Deskripsi Kerusakan</th>
          <th>Teknisi</th>
          <th>Tgl Mulai</th>
          <th>Biaya (Rp)</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($maintenances)): ?>
          <tr>
            <td colspan="8" style="text-align:center; color: var(--text-muted); padding: 2rem;">Belum ada catatan perbaikan barang</td>
          </tr>
        <?php else: ?>
          <?php foreach ($maintenances as $m): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($m['item_name']) ?></strong>
                <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($m['code_sku']) ?></div>
              </td>
              <td><span class="badge badge-secondary"><?= htmlspecialchars($m['location_name'] ?: '-') ?></span></td>
              <td style="max-width:250px;"><?= htmlspecialchars($m['issue_description']) ?></td>
              <td><?= htmlspecialchars($m['technician']) ?></td>
              <td><?= date('d M Y', strtotime($m['start_date'])) ?></td>
              <td>Rp <?= number_format($m['cost'] ?: 0, 0, ',', '.') ?></td>
              <td>
                <span class="badge <?= $m['status'] === 'Selesai' ? 'badge-success' : 'badge-warning' ?>">
                  <?= htmlspecialchars($m['status']) ?>
                </span>
              </td>
              <td>
                <div style="display:flex; gap:0.35rem; align-items:center;">
                  <button type="button" class="btn btn-sm btn-outline" onclick="editMaintPHP(<?= htmlspecialchars(json_encode($m)) ?>)" title="Edit Log Perbaikan">
                    <i data-lucide="edit-3"></i>
                  </button>
                  <?php if ($m['status'] === 'Dalam Proses'): ?>
                    <a href="actions.php?action=finish_maintenance&id=<?= $m['id'] ?>" class="btn btn-sm btn-emerald" onclick="return confirm('Apakah perbaikan alat ini sudah selesai diservis? Kondisi barang akan dikembalikan ke Baik.')" title="Selesai Servis">
                      <i data-lucide="check"></i> Selesai
                    </a>
                  <?php endif; ?>
                  <a href="actions.php?action=delete_maintenance&id=<?= $m['id'] ?>" class="btn btn-sm btn-rose" onclick="return confirm('Apakah Anda yakin ingin menghapus catatan perbaikan ini?')" title="Hapus Log Perbaikan">
                    <i data-lucide="trash-2"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
