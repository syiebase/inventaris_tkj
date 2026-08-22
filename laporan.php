<?php
$currentPage = 'laporan';
$pageTitle = 'Cetak Laporan Inventaris';
$pageSubtitle = 'Cetak dan ekspor dokumen inventaris resmi SMK Negeri 1 Pleret';

require_once __DIR__ . '/includes/header.php';

$type = $_GET['type'] ?? 'all';

// Fetch data for printable view
$itemsReport = $pdo->query("
    SELECT i.*, c.name as category_name, l.name as location_name
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.id
    LEFT JOIN locations l ON i.location_id = l.id
    ORDER BY c.name ASC, i.name ASC
")->fetchAll();

$loansReport = $pdo->query("
    SELECT l.*, i.name as item_name, i.code_sku
    FROM loans l
    JOIN items i ON l.item_id = i.id
    ORDER BY l.loan_date DESC
")->fetchAll();

$maintReport = $pdo->query("
    SELECT m.*, i.name as item_name, i.code_sku
    FROM maintenance m
    JOIN items i ON m.item_id = i.id
    ORDER BY m.start_date DESC
")->fetchAll();
// Fetch settings for report signatures
$settingsRows = $pdo->query("SELECT * FROM settings")->fetchAll();
$settings = [];
foreach ($settingsRows as $row) {
    $settings[$row['key_name']] = $row['value_text'];
}

$cityName    = $settings['city_name'] ?? 'Pleret';
$kaprogTitle = $settings['kaprog_title'] ?? 'Kepala Program Ahli TKJ';
$kaprogName  = $settings['kaprog_name'] ?? 'Budi Santoso, M.Eng.';
$kaprogNip   = $settings['kaprog_nip'] ?? 'NIP. 19780512 200501 1 003';

$kalabTitle  = $settings['kalab_title'] ?? 'Kepala Laboratorium TKJ';
$kalabName   = $settings['kalab_name'] ?? 'Agus Prasetyo, S.Kom.';
$kalabNip    = $settings['kalab_nip'] ?? 'NIP. 19820315 200802 1 007';

$kopPemda    = $settings['kop_pemda'] ?? 'PEMERINTAH DAERAH DAERAH ISTIMEWA YOGYAKARTA';
$kopDinas    = $settings['kop_dinas'] ?? 'DINAS PENDIDIKAN, PEMUDA, DAN OLAHRAGA';
$kopSchool   = $settings['kop_school'] ?? 'SMK NEGERI 1 PLERET';
$kopAddress  = $settings['kop_address'] ?? 'Jl. Dahromo, Kanggotan, Pleret, Bantul, DI Yogyakarta 55791';
$kopProgram  = $settings['kop_program'] ?? 'Kompetensi Keahlian: Teknik Komputer dan Jaringan (TKJ)';
?>

<div class="glass-card report-control-card">
  <div class="report-header">
    <div class="report-header-icon">
      <i data-lucide="printer"></i>
    </div>
    <div>
      <h3>Cetak Laporan Inventaris Resmi</h3>
      <p>Pilih jenis laporan yang ingin dicetak dengan format kop surat SMK Negeri 1 Pleret.</p>
    </div>
  </div>

  <div class="report-options">
    <a href="laporan.php?type=all" class="report-type-card <?= $type === 'all' ? 'active' : '' ?>">
      <div class="card-icon-box cyan-box">
        <i data-lucide="file-spreadsheet"></i>
      </div>
      <div class="card-content">
        <h4>Laporan Seluruh Inventaris</h4>
        <p>Mencakup data semua barang, kondisi, dan lokasi lab.</p>
      </div>
      <?php if ($type === 'all'): ?>
        <span class="active-badge"><i data-lucide="check-circle-2"></i> Terpilih</span>
      <?php endif; ?>
    </a>

    <a href="laporan.php?type=loans" class="report-type-card <?= $type === 'loans' ? 'active' : '' ?>">
      <div class="card-icon-box emerald-box">
        <i data-lucide="arrow-left-right"></i>
      </div>
      <div class="card-content">
        <h4>Laporan Peminjaman Alat</h4>
        <p>Daftar transaksi peminjaman aktif & riwayat pengembalian.</p>
      </div>
      <?php if ($type === 'loans'): ?>
        <span class="active-badge"><i data-lucide="check-circle-2"></i> Terpilih</span>
      <?php endif; ?>
    </a>

    <a href="laporan.php?type=maintenance" class="report-type-card <?= $type === 'maintenance' ? 'active' : '' ?>">
      <div class="card-icon-box rose-box">
        <i data-lucide="wrench"></i>
      </div>
      <div class="card-content">
        <h4>Laporan Perbaikan Lab</h4>
        <p>Rincian log kerusakan, teknisi, dan alokasi biaya maintenance.</p>
      </div>
      <?php if ($type === 'maintenance'): ?>
        <span class="active-badge"><i data-lucide="check-circle-2"></i> Terpilih</span>
      <?php endif; ?>
    </a>
  </div>

  <div class="report-action-bar" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
    <button class="btn btn-emerald btn-lg" onclick="window.print()">
      <i data-lucide="printer"></i> Cetak / Export PDF Laporan
    </button>
    <button class="btn btn-outline btn-lg" onclick="openModal('modal-settings-ttd')">
      <i data-lucide="edit-3"></i> Ubah Kop & Pejabat Laporan
    </button>
  </div>
</div>

<!-- Printable Area (Hidden on screen, visible on Print) -->
<div class="print-only">
  <div class="kop-surat">
    <h2><?= htmlspecialchars($kopPemda) ?></h2>
    <h2><?= htmlspecialchars($kopDinas) ?></h2>
    <h3><?= htmlspecialchars($kopSchool) ?></h3>
    <p><?= htmlspecialchars($kopAddress) ?></p>
    <p><?= htmlspecialchars($kopProgram) ?></p>
  </div>

  <div style="text-align:center; margin-bottom: 20px;">
    <h3 style="margin:0; font-size:13pt; text-transform:uppercase;">
      <?php 
        if ($type === 'loans') echo 'LAPORAN TRANSAKSI PEMINJAMAN ALAT PRAKTIK TKJ';
        elseif ($type === 'maintenance') echo 'LAPORAN PERBAIKAN & MAINTENANCE LABORATORIUM TKJ';
        else echo 'LAPORAN DAFTAR INVENTARIS TEKNIK KOMPUTER DAN JARINGAN';
      ?>
    </h3>
    <p style="margin:4px 0 0 0; font-size:10pt;">Tanggal Cetak: <?= date('d F Y') ?></p>
  </div>

  <table class="print-table">
    <?php if ($type === 'all'): ?>
      <thead>
        <tr>
          <th style="width:30px;">No</th>
          <th>Kode SKU</th>
          <th>Nama Perangkat</th>
          <th>Kategori</th>
          <th>Lokasi Lab</th>
          <th>Kondisi</th>
          <th>Total</th>
          <th>Tersedia</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($itemsReport as $idx => $item): ?>
          <tr>
            <td style="text-align:center;"><?= $idx + 1 ?></td>
            <td><strong><?= htmlspecialchars($item['code_sku']) ?></strong></td>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= htmlspecialchars($item['category_name']) ?></td>
            <td><?= htmlspecialchars($item['location_name']) ?></td>
            <td><?= htmlspecialchars($item['condition']) ?></td>
            <td style="text-align:center;"><?= $item['total_qty'] ?></td>
            <td style="text-align:center;"><?= $item['available_qty'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>

    <?php elseif ($type === 'loans'): ?>
      <thead>
        <tr>
          <th style="width:30px;">No</th>
          <th>Nama Peminjam</th>
          <th>Kelas / Peran</th>
          <th>Barang</th>
          <th>Qty</th>
          <th>Tgl Pinjam</th>
          <th>Tenggat</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($loansReport as $idx => $l): ?>
          <tr>
            <td style="text-align:center;"><?= $idx + 1 ?></td>
            <td><strong><?= htmlspecialchars($l['borrower_name']) ?></strong></td>
            <td><?= htmlspecialchars($l['borrower_class'] ?: $l['borrower_role']) ?></td>
            <td><?= htmlspecialchars($l['item_name']) ?> (<?= htmlspecialchars($l['code_sku']) ?>)</td>
            <td style="text-align:center;"><?= $l['quantity'] ?> Unit</td>
            <td><?= date('d/m/Y', strtotime($l['loan_date'])) ?></td>
            <td><?= date('d/m/Y', strtotime($l['expected_return'])) ?></td>
            <td><?= htmlspecialchars($l['status']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>

    <?php elseif ($type === 'maintenance'): ?>
      <thead>
        <tr>
          <th style="width:30px;">No</th>
          <th>Perangkat SKU</th>
          <th>Deskripsi Kerusakan</th>
          <th>Teknisi</th>
          <th>Mulai</th>
          <th>Selesai</th>
          <th>Biaya (Rp)</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($maintReport as $idx => $m): ?>
          <tr>
            <td style="text-align:center;"><?= $idx + 1 ?></td>
            <td><strong><?= htmlspecialchars($m['item_name']) ?></strong> (<?= htmlspecialchars($m['code_sku']) ?>)</td>
            <td><?= htmlspecialchars($m['issue_description']) ?></td>
            <td><?= htmlspecialchars($m['technician']) ?></td>
            <td><?= date('d/m/Y', strtotime($m['start_date'])) ?></td>
            <td><?= $m['end_date'] ? date('d/m/Y', strtotime($m['end_date'])) : '-' ?></td>
            <td style="text-align:right;">Rp <?= number_format($m['cost'] ?: 0, 0, ',', '.') ?></td>
            <td><?= htmlspecialchars($m['status']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    <?php endif; ?>
  </table>

  <div class="ttd-box">
    <div class="ttd-col">
      <p>Mengetahui,<br><?= htmlspecialchars($kaprogTitle) ?></p>
      <div class="ttd-space"></div>
      <p><strong><u><?= htmlspecialchars($kaprogName) ?></u></strong><br><?= htmlspecialchars($kaprogNip) ?></p>
    </div>

    <div class="ttd-col">
      <p><?= htmlspecialchars($cityName) ?>, <?= date('d F Y') ?><br><?= htmlspecialchars($kalabTitle) ?></p>
      <div class="ttd-space"></div>
      <p><strong><u><?= htmlspecialchars($kalabName) ?></u></strong><br><?= htmlspecialchars($kalabNip) ?></p>
    </div>
  </div>
</div>

<!-- MODAL: EDIT TANDA TANGAN & KOP LAPORAN -->
<div class="modal-overlay" id="modal-settings-ttd">
  <div class="modal-box modal-lg">
    <div class="modal-header">
      <h3>Edit Header Kop Surat & Pejabat Laporan</h3>
    </div>
    <form action="actions.php" method="POST">
      <input type="hidden" name="action" value="save_settings">

      <div class="form-grid">
        <!-- Kop Surat Header Settings -->
        <div class="form-group full-width"><strong style="color:var(--cyan-color);">Pengaturan Header Kop Surat:</strong></div>
        <div class="form-group">
          <label>Instansi Induk (Baris 1) <span class="req">*</span></label>
          <input type="text" name="settings[kop_pemda]" value="<?= htmlspecialchars($kopPemda) ?>" placeholder="Contoh: PEMERINTAH DAERAH DAERAH ISTIMEWA YOGYAKARTA" required>
        </div>
        <div class="form-group">
          <label>Dinas / OPD (Baris 2) <span class="req">*</span></label>
          <input type="text" name="settings[kop_dinas]" value="<?= htmlspecialchars($kopDinas) ?>" placeholder="Contoh: DINAS PENDIDIKAN, PEMUDA, DAN OLAHRAGA" required>
        </div>
        <div class="form-group">
          <label>Nama Sekolah (Baris 3) <span class="req">*</span></label>
          <input type="text" name="settings[kop_school]" value="<?= htmlspecialchars($kopSchool) ?>" placeholder="Contoh: SMK NEGERI 1 PLERET" required>
        </div>
        <div class="form-group">
          <label>Kompetensi Keahlian (Baris 4)</label>
          <input type="text" name="settings[kop_program]" value="<?= htmlspecialchars($kopProgram) ?>" placeholder="Contoh: KOMPETENSI KEAHLIAN: TEKNIK KOMPUTER DAN JARINGAN (TKJ)">
        </div>
        <div class="form-group full-width">
          <label>Alamat Lengkap Sekolah (Baris 5) <span class="req">*</span></label>
          <input type="text" name="settings[kop_address]" value="<?= htmlspecialchars($kopAddress) ?>" placeholder="Contoh: Alamat: Jl. Dahromo, Kanggotan, Pleret, Bantul, DI Yogyakarta 55791" required>
        </div>

        <!-- Signature Settings -->
        <div class="form-group full-width" style="margin-top:0.5rem;"><strong style="color:var(--amber-color);">Pengaturan Pejabat Tanda Tangan & Lokasi:</strong></div>
        <div class="form-group full-width">
          <label>Kota Tempat Cetak Surat <span class="req">*</span></label>
          <input type="text" name="settings[city_name]" value="<?= htmlspecialchars($cityName) ?>" placeholder="Contoh: Pleret / Bantul / Yogyakarta" required>
        </div>

        <!-- Kaprog -->
        <div class="form-group">
          <label>Jabatan Pejabat 1 (Kiri) <span class="req">*</span></label>
          <input type="text" name="settings[kaprog_title]" value="<?= htmlspecialchars($kaprogTitle) ?>" required>
        </div>
        <div class="form-group">
          <label>Nama & Gelar Pejabat 1 <span class="req">*</span></label>
          <input type="text" name="settings[kaprog_name]" value="<?= htmlspecialchars($kaprogName) ?>" required>
        </div>
        <div class="form-group full-width">
          <label>NIP / NIPTT Pejabat 1</label>
          <input type="text" name="settings[kaprog_nip]" value="<?= htmlspecialchars($kaprogNip) ?>">
        </div>

        <!-- Kalab -->
        <div class="form-group">
          <label>Jabatan Pejabat 2 (Kanan) <span class="req">*</span></label>
          <input type="text" name="settings[kalab_title]" value="<?= htmlspecialchars($kalabTitle) ?>" required>
        </div>
        <div class="form-group">
          <label>Nama & Gelar Pejabat 2 <span class="req">*</span></label>
          <input type="text" name="settings[kalab_name]" value="<?= htmlspecialchars($kalabName) ?>" required>
        </div>
        <div class="form-group full-width">
          <label>NIP / NIPTT Pejabat 2</label>
          <input type="text" name="settings[kalab_nip]" value="<?= htmlspecialchars($kalabNip) ?>">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-settings-ttd')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Kop & TTD Laporan</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
