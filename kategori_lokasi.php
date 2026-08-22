<?php
$currentPage = 'master';
$pageTitle = 'Kategori & Laboratorium';
$pageSubtitle = 'Manajemen data kategori barang dan lokasi laboratorium TKJ SMKN 1 Pleret';

require_once __DIR__ . '/includes/header.php';
?>

<div class="grid-2col">
  <!-- Categories Card -->
  <div class="glass-card">
    <div class="card-header flex-header">
      <h3><i data-lucide="folder"></i> Kategori Barang</h3>
      <button class="btn btn-sm btn-primary" onclick="openCategoryModalPHP()">+ Kategori</button>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Nama Kategori</th>
            <th>Keterangan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allCategories as $c): ?>
            <tr>
              <td><span class="sku-code"><?= htmlspecialchars($c['code']) ?></span></td>
              <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
              <td><?= htmlspecialchars($c['description'] ?: '-') ?></td>
              <td>
                <div style="display:flex; gap:0.35rem;">
                  <button type="button" class="btn btn-sm btn-outline" onclick="editCategoryPHP(<?= htmlspecialchars(json_encode($c)) ?>)" title="Edit Kategori">
                    <i data-lucide="edit-3"></i>
                  </button>
                  <a href="actions.php?action=delete_category&id=<?= $c['id'] ?>" class="btn btn-sm btn-rose" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')" title="Hapus Kategori">
                    <i data-lucide="trash-2"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Locations Card -->
  <div class="glass-card">
    <div class="card-header flex-header">
      <h3><i data-lucide="map-pin"></i> Laboratorium & Ruang TKJ</h3>
      <button class="btn btn-sm btn-primary" onclick="openLocationModalPHP()">+ Lokasi</button>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Ruang / Lab</th>
            <th>Penanggung Jawab</th>
            <th>Keterangan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allLocations as $l): ?>
            <tr>
              <td><strong><?= htmlspecialchars($l['name']) ?></strong></td>
              <td><?= htmlspecialchars($l['keeper']) ?></td>
              <td><?= htmlspecialchars($l['description'] ?: '-') ?></td>
              <td>
                <div style="display:flex; gap:0.35rem;">
                  <button type="button" class="btn btn-sm btn-outline" onclick="editLocationPHP(<?= htmlspecialchars(json_encode($l)) ?>)" title="Edit Lokasi">
                    <i data-lucide="edit-3"></i>
                  </button>
                  <a href="actions.php?action=delete_location&id=<?= $l['id'] ?>" class="btn btn-sm btn-rose" onclick="return confirm('Apakah Anda yakin ingin menghapus lokasi lab ini?')" title="Hapus Lokasi">
                    <i data-lucide="trash-2"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
