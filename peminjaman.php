<?php
$currentPage = 'peminjaman';
$pageTitle = 'Peminjaman Alat Praktik';
$pageSubtitle = 'Sistem pencatatan dan monitoring transaksi peminjaman siswa & guru TKJ';

require_once __DIR__ . '/includes/header.php';

$filterStatus = $_GET['status'] ?? 'Dipinjam';

$sql = "
    SELECT l.*, i.name as item_name, i.code_sku, i.brand_model, c.name as category_name
    FROM loans l
    JOIN items i ON l.item_id = i.id
    LEFT JOIN categories c ON i.category_id = c.id
    WHERE 1=1
";
$params = [];

if (!empty($filterStatus)) {
    $sql .= " AND l.status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY l.loan_date DESC, l.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$loans = $stmt->fetchAll();

$todayStr = date('Y-m-d');
?>

<div class="glass-card">
  <div class="card-header flex-header" style="flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between;">
    <!-- Single Segmented Button Pills Filter -->
    <div class="tab-pills">
      <a href="peminjaman.php?status=Dipinjam" class="pill-btn <?= $filterStatus === 'Dipinjam' ? 'active' : '' ?>">
        <i data-lucide="clock" style="width:15px; height:15px;"></i> Sedang Dipinjam
      </a>
      <a href="peminjaman.php?status=Dikembalikan" class="pill-btn <?= $filterStatus === 'Dikembalikan' ? 'active' : '' ?>">
        <i data-lucide="check-circle-2" style="width:15px; height:15px;"></i> Riwayat Pengembalian
      </a>
      <a href="peminjaman.php?status=" class="pill-btn <?= $filterStatus === '' ? 'active' : '' ?>">
        <i data-lucide="list" style="width:15px; height:15px;"></i> Semua Transaksi
      </a>
    </div>

    <button class="btn btn-emerald" onclick="openModal('modal-loan')">
      <i data-lucide="plus-circle"></i> Transaksi Pinjam Baru
    </button>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>No. Surat</th>
          <th>Peminjam</th>
          <th>Peran / Kelas</th>
          <th>Nama Barang (Alat)</th>
          <th>Jumlah</th>
          <th>Tgl Pinjam</th>
          <th>Tenggat Kembali</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($loans)): ?>
          <tr>
            <td colspan="9" style="text-align:center; color: var(--text-muted); padding: 2rem;">Tidak ada transaksi peminjaman ditemukan</td>
          </tr>
        <?php else: ?>
          <?php foreach ($loans as $loan): ?>
            <?php 
              $isLate = ($loan['status'] === 'Dipinjam' && $loan['expected_return'] < $todayStr);
              $letterNo = !empty($loan['letter_number']) ? $loan['letter_number'] : ('SURAT-TKJ/' . date('Ym', strtotime($loan['loan_date'])) . '/' . str_pad($loan['id'], 3, '0', STR_PAD_LEFT));
            ?>
            <tr>
              <td><span class="sku-code"><?= htmlspecialchars($letterNo) ?></span></td>
              <td><strong><?= htmlspecialchars($loan['borrower_name']) ?></strong></td>
              <td><?= htmlspecialchars(!empty($loan['borrower_class']) ? $loan['borrower_class'] : ($loan['borrower_role'] ?? '-')) ?></td>
              <td>
                <strong><?= htmlspecialchars($loan['item_name']) ?></strong>
                <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($loan['code_sku']) ?></div>
              </td>
              <td><span class="badge badge-info"><?= $loan['quantity'] ?> Unit</span></td>
              <td><?= date('d M Y', strtotime($loan['loan_date'])) ?></td>
              <td><?= date('d M Y', strtotime($loan['expected_return'])) ?></td>
              <td>
                <?php if ($loan['status'] === 'Dikembalikan'): ?>
                  <span class="badge badge-success"><i data-lucide="check-circle-2"></i> Dikembalikan</span>
                <?php elseif ($isLate): ?>
                  <span class="badge badge-danger"><i data-lucide="alert-circle"></i> Terlambat</span>
                <?php else: ?>
                  <span class="badge badge-warning"><i data-lucide="clock"></i> Dipinjam</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display:flex; gap:0.35rem; align-items:center;">
                  <button type="button" class="btn btn-sm btn-outline" onclick='openLoanLetterPHP(<?= json_encode($loan) ?>)' title="Cetak Surat Peminjaman">
                    <i data-lucide="file-text"></i> Surat
                  </button>
                  <?php if ($loan['status'] !== 'Dikembalikan'): ?>
                    <a href="actions.php?action=return_loan&id=<?= $loan['id'] ?>" class="btn btn-sm btn-emerald" onclick="return confirm('Apakah Anda yakin ingin memproses pengembalian barang ini? Stok akan otomatis bertambah.')">
                      <i data-lucide="arrow-down-left"></i> Kembali
                    </a>
                  <?php endif; ?>
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
