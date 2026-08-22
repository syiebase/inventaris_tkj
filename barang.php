<?php
$currentPage = 'barang';
$pageTitle = 'Data Inventaris Barang';
$pageSubtitle = 'Kelola seluruh daftar perangkat, komponen, dan stok laboratorium TKJ';

require_once __DIR__ . '/includes/header.php';

// Filter Parameters
$search = trim($_GET['search'] ?? '');
$catFilter = $_GET['category_id'] ?? '';
$locFilter = $_GET['location_id'] ?? '';
$condFilter = $_GET['condition'] ?? '';

// Build Query
$sql = "
    SELECT i.*, c.name as category_name, c.code as category_code, l.name as location_name, l.keeper as location_keeper
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.id
    LEFT JOIN locations l ON i.location_id = l.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (i.name LIKE ? OR i.code_sku LIKE ? OR i.brand_model LIKE ? OR i.specifications LIKE ?)";
    $q = "%$search%";
    $params = array_merge($params, [$q, $q, $q, $q]);
}
if (!empty($catFilter)) {
    $sql .= " AND i.category_id = ?";
    $params[] = $catFilter;
}
if (!empty($locFilter)) {
    $sql .= " AND i.location_id = ?";
    $params[] = $locFilter;
}
if (!empty($condFilter)) {
    $sql .= " AND i.`condition` = ?";
    $params[] = $condFilter;
}

$sql .= " ORDER BY i.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>

<div class="glass-card">
  <!-- Filter Form -->
  <form method="GET" action="barang.php" class="filter-bar">
    <div class="search-input-group">
      <i data-lucide="search" class="search-icon"></i>
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama barang, SKU, atau spesifikasi...">
    </div>

    <div class="filter-selects">
      <select name="category_id" onchange="this.form.submit()">
        <option value="">Semua Kategori</option>
        <?php foreach ($allCategories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $catFilter == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <select name="location_id" onchange="this.form.submit()">
        <option value="">Semua Lokasi / Lab</option>
        <?php foreach ($allLocations as $l): ?>
          <option value="<?= $l['id'] ?>" <?= $locFilter == $l['id'] ? 'selected' : '' ?>><?= htmlspecialchars($l['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <select name="condition" onchange="this.form.submit()">
        <option value="">Semua Kondisi</option>
        <option value="Baik" <?= $condFilter === 'Baik' ? 'selected' : '' ?>>Baik</option>
        <option value="Rusak Ringan" <?= $condFilter === 'Rusak Ringan' ? 'selected' : '' ?>>Rusak Ringan</option>
        <option value="Rusak Berat" <?= $condFilter === 'Rusak Berat' ? 'selected' : '' ?>>Rusak Berat</option>
        <option value="Dalam Perbaikan" <?= $condFilter === 'Dalam Perbaikan' ? 'selected' : '' ?>>Dalam Perbaikan</option>
      </select>

      <a href="barang.php" class="btn btn-outline">
        <i data-lucide="rotate-ccw"></i> Reset
      </a>
    </div>
  </form>

  <!-- Items Table -->
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>SKU Kode</th>
          <th>Nama Perangkat</th>
          <th>Kategori</th>
          <th>Lokasi Lab</th>
          <th>Kondisi</th>
          <th>Total / Ada</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr>
            <td colspan="7" style="text-align:center; color: var(--text-muted); padding: 2rem;">Data inventaris tidak ditemukan</td>
          </tr>
        <?php else: ?>
          <?php foreach ($items as $item): ?>
            <tr>
              <td><span class="sku-code"><?= htmlspecialchars($item['code_sku']) ?></span></td>
              <td>
                <strong><?= htmlspecialchars($item['name']) ?></strong>
                <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($item['brand_model'] ?: '-') ?></div>
              </td>
              <td><span class="badge badge-info"><?= htmlspecialchars($item['category_name'] ?: '-') ?></span></td>
              <td><span class="badge badge-secondary"><?= htmlspecialchars($item['location_name'] ?: '-') ?></span></td>
              <td>
                <?php if ($item['condition'] === 'Baik'): ?>
                  <span class="badge badge-success"><i data-lucide="check-circle"></i> Baik</span>
                <?php elseif ($item['condition'] === 'Rusak Ringan'): ?>
                  <span class="badge badge-warning"><i data-lucide="alert-triangle"></i> Rusak Ringan</span>
                <?php elseif ($item['condition'] === 'Rusak Berat'): ?>
                  <span class="badge badge-danger"><i data-lucide="alert-circle"></i> Rusak Berat</span>
                <?php else: ?>
                  <span class="badge badge-info"><i data-lucide="wrench"></i> Perbaikan</span>
                <?php endif; ?>
              </td>
              <td>
                <strong><?= $item['available_qty'] ?></strong> / <span style="color:var(--text-muted);"><?= $item['total_qty'] ?></span> Unit
              </td>
              <td>
                <div style="display:flex; gap:0.4rem;">
                  <button class="btn btn-sm btn-outline" onclick='editItemModal(<?= json_encode($item) ?>)' title="Edit">
                    <i data-lucide="edit-3"></i>
                  </button>
                  <a href="actions.php?action=delete_item&id=<?= $item['id'] ?>" class="btn btn-sm btn-rose" onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini?')" title="Hapus">
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
