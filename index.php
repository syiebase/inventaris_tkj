<?php
$currentPage = 'dashboard';
$pageTitle = 'Dashboard Utama';
$pageSubtitle = 'Ringkasan statistik inventaris peralatan TKJ SMKN 1 Pleret';

require_once __DIR__ . '/includes/header.php';

// Fetch Statistics from MySQL Database
$totalItems = $pdo->query("SELECT IFNULL(SUM(total_qty), 0) as total, COUNT(id) as count FROM items")->fetch();
$totalAvailable = $pdo->query("SELECT IFNULL(SUM(available_qty), 0) as available FROM items")->fetch();
$activeLoans = $pdo->query("SELECT COUNT(id) as count FROM loans WHERE status IN ('Dipinjam', 'Terlambat')")->fetch();
$activeMaint = $pdo->query("SELECT COUNT(id) as count FROM maintenance WHERE status = 'Dalam Proses'")->fetch();

// Condition Stats for Doughnut Chart
$condStats = $pdo->query("SELECT `condition`, COUNT(id) as count, SUM(total_qty) as total_qty FROM items GROUP BY `condition`")->fetchAll();

// Category Stats for Bar Chart
$catStats = $pdo->query("
    SELECT c.name as category_name, COUNT(i.id) as item_types, IFNULL(SUM(i.total_qty), 0) as total_qty
    FROM categories c
    LEFT JOIN items i ON c.id = i.category_id
    GROUP BY c.id
")->fetchAll();

// Recent Loans
$recentLoans = $pdo->query("
    SELECT l.*, i.name as item_name, i.code_sku
    FROM loans l
    JOIN items i ON l.item_id = i.id
    WHERE l.status IN ('Dipinjam', 'Terlambat')
    ORDER BY l.loan_date DESC LIMIT 5
")->fetchAll();

// Recent Activity Logs
$recentLogs = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 8")->fetchAll();
?>

<!-- Summary Cards Grid -->
<div class="stats-grid">
  <div class="stat-card card-cyan">
    <div class="stat-icon">
      <i data-lucide="boxes"></i>
    </div>
    <div class="stat-content">
      <span class="stat-label">Total Unit Perangkat</span>
      <span class="stat-value"><?= number_format($totalItems['total'], 0, ',', '.') ?></span>
      <span class="stat-sub"><?= $totalItems['count'] ?> Jenis SKU</span>
    </div>
  </div>

  <div class="stat-card card-emerald">
    <div class="stat-icon">
      <i data-lucide="check-circle-2"></i>
    </div>
    <div class="stat-content">
      <span class="stat-label">Stok Tersedia</span>
      <span class="stat-value"><?= number_format($totalAvailable['available'], 0, ',', '.') ?></span>
      <span class="stat-sub">Siap digunakan / dipinjam</span>
    </div>
  </div>

  <div class="stat-card card-amber">
    <div class="stat-icon">
      <i data-lucide="arrow-up-right"></i>
    </div>
    <div class="stat-content">
      <span class="stat-label">Peminjaman Aktif</span>
      <span class="stat-value"><?= number_format($activeLoans['count'], 0, ',', '.') ?></span>
      <span class="stat-sub">Sedang dipinjam siswa/guru</span>
    </div>
  </div>

  <div class="stat-card card-rose">
    <div class="stat-icon">
      <i data-lucide="wrench"></i>
    </div>
    <div class="stat-content">
      <span class="stat-label">Dalam Perbaikan</span>
      <span class="stat-value"><?= number_format($activeMaint['count'], 0, ',', '.') ?></span>
      <span class="stat-sub">Properti maintenance lab</span>
    </div>
  </div>
</div>

<!-- Charts Section -->
<div class="dashboard-charts-grid">
  <div class="glass-card chart-container">
    <div class="card-header">
      <h3><i data-lucide="pie-chart"></i> Kondisi Fisik Perangkat</h3>
    </div>
    <div class="chart-box">
      <canvas id="chartCondition"></canvas>
    </div>
  </div>

  <div class="glass-card chart-container">
    <div class="card-header">
      <h3><i data-lucide="bar-chart-3"></i> Distribusi Kategori Inventaris</h3>
    </div>
    <div class="chart-box">
      <canvas id="chartCategory"></canvas>
    </div>
  </div>
</div>

<!-- Recent Tables Grid -->
<div class="dashboard-tables-grid">
  <div class="glass-card">
    <div class="card-header">
      <h3><i data-lucide="clock"></i> Peminjaman Aktif Terbaru</h3>
      <a href="peminjaman.php" class="link-action">Lihat Semua</a>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Peminjam</th>
            <th>Barang</th>
            <th>Qty</th>
            <th>Tgl Pinjam</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentLoans)): ?>
            <tr>
              <td colspan="5" style="text-align:center; color: var(--text-muted); padding: 1.5rem;">Tidak ada peminjaman aktif saat ini</td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentLoans as $rl): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($rl['borrower_name']) ?></strong>
                  <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($rl['borrower_class'] ?: $rl['borrower_role']) ?></div>
                </td>
                <td><?= htmlspecialchars($rl['item_name']) ?></td>
                <td><span class="badge badge-info"><?= $rl['quantity'] ?> Unit</span></td>
                <td><?= date('d M Y', strtotime($rl['loan_date'])) ?></td>
                <td><span class="badge badge-warning"><?= htmlspecialchars($rl['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="glass-card">
    <div class="card-header">
      <h3><i data-lucide="activity"></i> Log Aktivitas Terakhir</h3>
    </div>
    <ul class="activity-feed">
      <?php foreach ($recentLogs as $log): ?>
        <li class="activity-item">
          <div class="activity-dot"></div>
          <div class="activity-text"><?= htmlspecialchars($log['details']) ?></div>
          <div class="activity-time"><?= date('d/m H:i', strtotime($log['created_at'])) ?></div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<!-- Chart Script -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Doughnut Chart Kondisi
    const condLabels = <?= json_encode(array_column($condStats, 'condition')) ?>;
    const condData = <?= json_encode(array_column($condStats, 'total_qty')) ?>;
    
    const colorMap = {
      'Baik': '#10b981',
      'Rusak Ringan': '#f59e0b',
      'Rusak Berat': '#f43f5e',
      'Dalam Perbaikan': '#6366f1'
    };
    const condColors = condLabels.map(l => colorMap[l] || '#06b6d4');

    new Chart(document.getElementById('chartCondition').getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: condLabels,
        datasets: [{ data: condData, backgroundColor: condColors, borderWidth: 0 }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { color: '#9ca3af', font: { family: 'Inter', size: 12 } } }
        },
        cutout: '70%'
      }
    });

    // Bar Chart Kategori
    const catLabels = <?= json_encode(array_column($catStats, 'category_name')) ?>;
    const catData = <?= json_encode(array_column($catStats, 'total_qty')) ?>;

    new Chart(document.getElementById('chartCategory').getContext('2d'), {
      type: 'bar',
      data: {
        labels: catLabels,
        datasets: [{
          label: 'Total Unit',
          data: catData,
          backgroundColor: 'rgba(6, 182, 212, 0.65)',
          borderColor: '#06b6d4',
          borderWidth: 1,
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { family: 'Inter', size: 11 } } },
          y: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#9ca3af', font: { family: 'Inter', size: 11 } } }
        }
      }
    });
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
