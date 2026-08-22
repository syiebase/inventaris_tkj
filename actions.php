<?php
// ====================================================
// ACTION HANDLER FOR INVENTARIS TKJ SMKN 1 PLERET
// ====================================================

session_start();
require_once __DIR__ . '/config/database.php';

function setFlash($msg, $type = 'success') {
    $_SESSION['flash_msg'] = $msg;
    $_SESSION['flash_type'] = $type;
}

function redirectBack($default = 'index.php') {
    $referer = $_SERVER['HTTP_REFERER'] ?? $default;
    header("Location: " . $referer);
    exit;
}

function logActivity($pdo, $action, $entity, $details) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (action, entity, details) VALUES (?, ?, ?)");
        $stmt->execute([$action, $entity, $details]);
    } catch (Exception $e) {
        // ignore logging error
    }
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ------------------------------------------------
    // 1. SIMPAN / EDIT BARANG INVENTARIS
    // ------------------------------------------------
    case 'save_item':
        $id = $_POST['id'] ?? '';
        $code_sku = trim($_POST['code_sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $category_id = $_POST['category_id'] ?? '';
        $location_id = $_POST['location_id'] ?? '';
        $brand_model = trim($_POST['brand_model'] ?? '');
        $condition = $_POST['condition'] ?? 'Baik';
        $total_qty = (int)($_POST['total_qty'] ?? 1);
        $specifications = trim($_POST['specifications'] ?? '');

        if (empty($code_sku) || empty($name) || empty($category_id) || empty($location_id)) {
            setFlash('SKU, Nama Barang, Kategori, dan Lokasi wajib diisi.', 'error');
            redirectBack('barang.php');
        }

        try {
            if (!empty($id)) {
                // Edit Item
                $stmtExisting = $pdo->prepare("SELECT * FROM items WHERE id = ?");
                $stmtExisting->execute([$id]);
                $existing = $stmtExisting->fetch();

                if (!$existing) {
                    setFlash('Barang tidak ditemukan.', 'error');
                    redirectBack('barang.php');
                }

                $diff = $total_qty - $existing['total_qty'];
                $newAvailable = max(0, $existing['available_qty'] + $diff);

                $stmt = $pdo->prepare("
                    UPDATE items SET
                        code_sku = ?,
                        name = ?,
                        category_id = ?,
                        location_id = ?,
                        brand_model = ?,
                        `condition` = ?,
                        total_qty = ?,
                        available_qty = ?,
                        specifications = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$code_sku, $name, $category_id, $location_id, $brand_model, $condition, $total_qty, $newAvailable, $specifications, $id]);

                logActivity($pdo, 'UPDATE_ITEM', 'ITEMS', "Memperbarui data barang: $name ($code_sku)");
                setFlash('Data barang berhasil diperbarui!');
            } else {
                // New Item
                $stmt = $pdo->prepare("
                    INSERT INTO items (code_sku, name, category_id, location_id, brand_model, `condition`, total_qty, available_qty, specifications)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$code_sku, $name, $category_id, $location_id, $brand_model, $condition, $total_qty, $total_qty, $specifications]);

                logActivity($pdo, 'CREATE_ITEM', 'ITEMS', "Menambahkan barang baru: $name ($code_sku), Qty: $total_qty");
                setFlash('Barang baru berhasil ditambahkan ke inventaris!');
            }
        } catch (PDOException $e) {
            setFlash('Error: ' . $e->getMessage(), 'error');
        }

        redirectBack('barang.php');
        break;

    // ------------------------------------------------
    // 2. HAPUS BARANG
    // ------------------------------------------------
    case 'delete_item':
        $id = $_GET['id'] ?? '';
        if ($id) {
            try {
                $stmtItem = $pdo->prepare("SELECT name, code_sku FROM items WHERE id = ?");
                $stmtItem->execute([$id]);
                $item = $stmtItem->fetch();

                if ($item) {
                    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
                    $stmt->execute([$id]);
                    logActivity($pdo, 'DELETE_ITEM', 'ITEMS', "Menghapus barang: {$item['name']} ({$item['code_sku']})");
                    setFlash('Barang berhasil dihapus dari inventaris.');
                }
            } catch (PDOException $e) {
                setFlash('Gagal menghapus barang: ' . $e->getMessage(), 'error');
            }
        }
        redirectBack('barang.php');
        break;

    // ------------------------------------------------
    // 3. TRANSAKSI PEMINJAMAN ALAT
    // ------------------------------------------------
    case 'save_loan':
        $item_id = $_POST['item_id'] ?? '';
        $borrower_name = trim($_POST['borrower_name'] ?? '');
        $borrower_role = $_POST['borrower_role'] ?? 'Siswa';
        $borrower_class = trim($_POST['borrower_class'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 1);
        $loan_date = $_POST['loan_date'] ?? date('Y-m-d');
        $expected_return = $_POST['expected_return'] ?? date('Y-m-d', strtotime('+7 days'));
        $letter_number = trim($_POST['letter_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($item_id) || empty($borrower_name) || empty($expected_return)) {
            setFlash('Pilih Barang, Nama Peminjam, dan Tanggal Pengembalian.', 'error');
            redirectBack('peminjaman.php');
        }

        if (empty($letter_number)) {
            $letter_number = 'SURAT-TKJ/' . date('Ym') . '/' . rand(1000, 9999);
        }

        try {
            // Check availability
            $stmtItem = $pdo->prepare("SELECT name, available_qty FROM items WHERE id = ?");
            $stmtItem->execute([$item_id]);
            $item = $stmtItem->fetch();

            if (!$item) {
                setFlash('Barang tidak ditemukan.', 'error');
                redirectBack('peminjaman.php');
            }

            if ($item['available_qty'] < $quantity) {
                setFlash("Stok tidak mencukupi. Sisa tersedia: {$item['available_qty']} unit.", 'error');
                redirectBack('peminjaman.php');
            }

            // Insert loan
            $stmt = $pdo->prepare("
                INSERT INTO loans (letter_number, item_id, borrower_name, borrower_role, borrower_class, quantity, loan_date, expected_return, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Dipinjam', ?)
            ");
            $stmt->execute([$letter_number, $item_id, $borrower_name, $borrower_role, $borrower_class, $quantity, $loan_date, $expected_return, $notes]);

            // Decrement item available_qty
            $newAvailable = $item['available_qty'] - $quantity;
            $stmtDec = $pdo->prepare("UPDATE items SET available_qty = ? WHERE id = ?");
            $stmtDec->execute([$newAvailable, $item_id]);

            logActivity($pdo, 'CREATE_LOAN', 'LOANS', "Peminjaman $quantity unit {$item['name']} oleh $borrower_name (Surat: $letter_number)");
            setFlash("Peminjaman $quantity unit {$item['name']} oleh $borrower_name berhasil dicatat!");
        } catch (PDOException $e) {
            setFlash('Error: ' . $e->getMessage(), 'error');
        }

        redirectBack('peminjaman.php');
        break;

    // ------------------------------------------------
    // 4. KEMBALIKAN PEMINJAMAN
    // ------------------------------------------------
    case 'return_loan':
        $id = $_GET['id'] ?? '';
        if ($id) {
            try {
                $stmtLoan = $pdo->prepare("SELECT * FROM loans WHERE id = ?");
                $stmtLoan->execute([$id]);
                $loan = $stmtLoan->fetch();

                if ($loan && $loan['status'] !== 'Dikembalikan') {
                    $todayStr = date('Y-m-d');
                    $stmtUpdate = $pdo->prepare("UPDATE loans SET status = 'Dikembalikan', actual_return = ? WHERE id = ?");
                    $stmtUpdate->execute([$todayStr, $id]);

                    // Restore available_qty
                    $stmtItem = $pdo->prepare("SELECT name, available_qty FROM items WHERE id = ?");
                    $stmtItem->execute([$loan['item_id']]);
                    $item = $stmtItem->fetch();

                    if ($item) {
                        $restoredAvailable = $item['available_qty'] + $loan['quantity'];
                        $stmtRestore = $pdo->prepare("UPDATE items SET available_qty = ? WHERE id = ?");
                        $stmtRestore->execute([$restoredAvailable, $loan['item_id']]);
                    }

                    logActivity($pdo, 'RETURN_LOAN', 'LOANS', "Pengembalian {$loan['quantity']} unit barang dari {$loan['borrower_name']}");
                    setFlash("Barang berhasil dikembalikan ke stok inventaris!");
                }
            } catch (PDOException $e) {
                setFlash('Gagal memproses pengembalian: ' . $e->getMessage(), 'error');
            }
        }
        redirectBack('peminjaman.php');
        break;

    // ------------------------------------------------
    // 5. SIMPAN / EDIT LOG PERBAIKAN (MAINTENANCE)
    // ------------------------------------------------
    case 'save_maintenance':
        $id = $_POST['id'] ?? '';
        $item_id = $_POST['item_id'] ?? '';
        $technician = trim($_POST['technician'] ?? '');
        $cost = (float)($_POST['cost'] ?? 0);
        $issue_description = trim($_POST['issue_description'] ?? '');
        $start_date = date('Y-m-d');

        if (empty($item_id) || empty($technician) || empty($issue_description)) {
            setFlash('Pilih Perangkat, Nama Teknisi, dan Deskripsi Kerusakan.', 'error');
            redirectBack('perbaikan.php');
        }

        try {
            if ($id) {
                // Update
                $stmt = $pdo->prepare("
                    UPDATE maintenance SET item_id = ?, issue_description = ?, technician = ?, cost = ?
                    WHERE id = ?
                ");
                $stmt->execute([$item_id, $issue_description, $technician, $cost, $id]);
                logActivity($pdo, 'UPDATE_MAINTENANCE', 'MAINTENANCE', "Memperbarui log perbaikan ID #$id");
                setFlash('Log perbaikan berhasil diperbarui!');
            } else {
                // Create
                $stmt = $pdo->prepare("
                    INSERT INTO maintenance (item_id, issue_description, technician, start_date, status, cost)
                    VALUES (?, ?, ?, ?, 'Dalam Proses', ?)
                ");
                $stmt->execute([$item_id, $issue_description, $technician, $start_date, $cost]);

                // Set item condition to Dalam Perbaikan
                $stmtCond = $pdo->prepare("UPDATE items SET `condition` = 'Dalam Perbaikan' WHERE id = ?");
                $stmtCond->execute([$item_id]);

                logActivity($pdo, 'CREATE_MAINTENANCE', 'MAINTENANCE', "Catatan perbaikan baru oleh teknisi $technician");
                setFlash('Log perbaikan berhasil ditambahkan!');
            }
        } catch (PDOException $e) {
            setFlash('Error: ' . $e->getMessage(), 'error');
        }

        redirectBack('perbaikan.php');
        break;

    // ------------------------------------------------
    // 6. SELESAI MAINTENANCE
    // ------------------------------------------------
    case 'finish_maintenance':
        $id = $_GET['id'] ?? '';
        if ($id) {
            try {
                $stmtMaint = $pdo->prepare("SELECT * FROM maintenance WHERE id = ?");
                $stmtMaint->execute([$id]);
                $mainLog = $stmtMaint->fetch();

                if ($mainLog) {
                    $todayStr = date('Y-m-d');
                    $stmtUpdate = $pdo->prepare("UPDATE maintenance SET status = 'Selesai', end_date = ? WHERE id = ?");
                    $stmtUpdate->execute([$todayStr, $id]);

                    // Restore item condition to Baik
                    $stmtItem = $pdo->prepare("UPDATE items SET `condition` = 'Baik' WHERE id = ?");
                    $stmtItem->execute([$mainLog['item_id']]);

                    logActivity($pdo, 'FINISH_MAINTENANCE', 'MAINTENANCE', "Perbaikan alat diselesaikan. Kondisi dikembalikan ke Baik.");
                    setFlash("Status servis diselesaikan! Kondisi barang kembali Baik.");
                }
            } catch (PDOException $e) {
                setFlash('Gagal menyelesaikan perbaikan: ' . $e->getMessage(), 'error');
            }
        }
        redirectBack('perbaikan.php');
        break;

    case 'delete_maintenance':
        $id = $_GET['id'] ?? '';
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM maintenance WHERE id = ?");
                $stmt->execute([$id]);
                logActivity($pdo, 'DELETE_MAINTENANCE', 'MAINTENANCE', "Menghapus log perbaikan ID #$id");
                setFlash("Log perbaikan berhasil dihapus.");
            } catch (PDOException $e) {
                setFlash('Gagal menghapus log perbaikan: ' . $e->getMessage(), 'error');
            }
        }
        redirectBack('perbaikan.php');
        break;

    // ------------------------------------------------
    // 7. SIMPAN / EDIT KATEGORI
    // ------------------------------------------------
    case 'save_category':
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');

        if (empty($name) || empty($code)) {
            setFlash('Nama dan Kode Kategori wajib diisi.', 'error');
            redirectBack('kategori_lokasi.php');
        }

        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, code = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $code, $description, $id]);
                logActivity($pdo, 'UPDATE_CATEGORY', 'CATEGORIES', "Update kategori: $name ($code)");
                setFlash("Kategori $name berhasil diperbarui!");
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (name, code, description) VALUES (?, ?, ?)");
                $stmt->execute([$name, $code, $description]);
                logActivity($pdo, 'CREATE_CATEGORY', 'CATEGORIES', "Tambah kategori: $name ($code)");
                setFlash("Kategori $name berhasil ditambahkan!");
            }
        } catch (PDOException $e) {
            setFlash('Gagal menyimpan kategori: ' . $e->getMessage(), 'error');
        }

        redirectBack('kategori_lokasi.php');
        break;

    case 'delete_category':
        $id = $_GET['id'] ?? '';
        if ($id) {
            try {
                $stmtCat = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                $stmtCat->execute([$id]);
                $cat = $stmtCat->fetch();
                if ($cat) {
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    $stmt->execute([$id]);
                    logActivity($pdo, 'DELETE_CATEGORY', 'CATEGORIES', "Menghapus kategori: {$cat['name']}");
                    setFlash("Kategori {$cat['name']} berhasil dihapus.");
                }
            } catch (PDOException $e) {
                setFlash('Gagal menghapus kategori: ' . $e->getMessage(), 'error');
            }
        }
        redirectBack('kategori_lokasi.php');
        break;

    // ------------------------------------------------
    // 8. SIMPAN / EDIT LOKASI / LAB
    // ------------------------------------------------
    case 'save_location':
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $keeper = trim($_POST['keeper'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name) || empty($keeper)) {
            setFlash('Nama Lokasi dan Penanggung Jawab wajib diisi.', 'error');
            redirectBack('kategori_lokasi.php');
        }

        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE locations SET name = ?, keeper = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $keeper, $description, $id]);
                logActivity($pdo, 'UPDATE_LOCATION', 'LOCATIONS', "Update lokasi: $name (PJ: $keeper)");
                setFlash("Lokasi $name berhasil diperbarui!");
            } else {
                $stmt = $pdo->prepare("INSERT INTO locations (name, keeper, description) VALUES (?, ?, ?)");
                $stmt->execute([$name, $keeper, $description]);
                logActivity($pdo, 'CREATE_LOCATION', 'LOCATIONS', "Tambah lokasi: $name (PJ: $keeper)");
                setFlash("Lokasi $name berhasil ditambahkan!");
            }
        } catch (PDOException $e) {
            setFlash('Gagal menyimpan lokasi: ' . $e->getMessage(), 'error');
        }

        redirectBack('kategori_lokasi.php');
        break;

    case 'delete_location':
        $id = $_GET['id'] ?? '';
        if ($id) {
            try {
                $stmtLoc = $pdo->prepare("SELECT name FROM locations WHERE id = ?");
                $stmtLoc->execute([$id]);
                $loc = $stmtLoc->fetch();
                if ($loc) {
                    $stmt = $pdo->prepare("DELETE FROM locations WHERE id = ?");
                    $stmt->execute([$id]);
                    logActivity($pdo, 'DELETE_LOCATION', 'LOCATIONS', "Menghapus lokasi: {$loc['name']}");
                    setFlash("Lokasi {$loc['name']} berhasil dihapus.");
                }
            } catch (PDOException $e) {
                setFlash('Gagal menghapus lokasi: ' . $e->getMessage(), 'error');
            }
        }
        redirectBack('kategori_lokasi.php');
        break;

    // ------------------------------------------------
    // 9. SIMPAN PENGATURAN TANDA TANGAN LAPORAN
    // ------------------------------------------------
    case 'save_settings':
        $settingsData = $_POST['settings'] ?? [];
        try {
            $stmt = $pdo->prepare("INSERT INTO settings (key_name, value_text) VALUES (?, ?) ON DUPLICATE KEY UPDATE value_text = VALUES(value_text)");
            foreach ($settingsData as $key => $val) {
                $stmt->execute([$key, trim($val)]);
            }
            logActivity($pdo, 'UPDATE_SETTINGS', 'SETTINGS', 'Memperbarui data pejabat tanda tangan laporan');
            setFlash('Pengaturan tanda tangan laporan berhasil diperbarui!');
        } catch (PDOException $e) {
            setFlash('Gagal menyimpan pengaturan: ' . $e->getMessage(), 'error');
        }
        redirectBack('laporan.php');
        break;

    default:
        redirectBack();
        break;
}
