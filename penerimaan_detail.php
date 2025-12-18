<?php   
include 'db.php';
include 'layout/navbar.php';

// 1. Ambil ID & Validasi Keamanan
$idp = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. Ambil Header Penerimaan & Info Pengadaan
$query_header = "
    SELECT pr.idpenerimaan, pr.created_at, pr.status,
           pg.idpengadaan, v.nama_vendor
    FROM penerimaan pr
    JOIN pengadaan pg ON pr.idpengadaan = pg.idpengadaan
    JOIN vendor v ON pg.vendor_idvendor = v.idvendor
    WHERE pr.idpenerimaan = $idp
";
$h = mysqli_query($conn, $query_header);
$header = mysqli_fetch_assoc($h);

if (!$header) {
    echo "<script>alert('Data penerimaan tidak ditemukan!'); window.location='penerimaan.php';</script>";
    exit;
}

$idpengadaan = (int)$header['idpengadaan'];

// ==========================================
// PROSES PENYIMPANAN (LOGIKA UTAMA)
// ==========================================
if (isset($_POST['tambah_detail'])) {

    $idbarang      = (int)$_POST['barang_idbarang'];
    $jumlah_terima = (int)$_POST['jumlah_terima'];

    // Validasi input
    if ($idbarang <= 0 || $jumlah_terima <= 0) {
        echo "<script>alert('Jumlah barang harus lebih dari 0!');</script>";
    } else {
        // A. Cek Sisa Barang (Validasi Partial Delivery)
        // Hitung total dipesan
        $q_order = mysqli_query($conn, "SELECT jumlah FROM detail_pengadaan WHERE idpengadaan = $idpengadaan AND idbarang = $idbarang");
        $d_order = mysqli_fetch_assoc($q_order);
        $total_pesan = $d_order ? (int)$d_order['jumlah'] : 0;

        // Hitung total yang SUDAH diterima sebelumnya (untuk item ini saja, di penerimaan ini)
        $q_received = mysqli_query($conn, "SELECT IFNULL(SUM(jumlah_terima),0) AS total FROM detail_penerimaan WHERE idpenerimaan = $idp AND barang_idbarang = $idbarang");
        $d_received = mysqli_fetch_assoc($q_received);
        $sudah_diterima = (int)$d_received['total'];

        $sisa_boleh = $total_pesan - $sudah_diterima;

        if ($jumlah_terima > $sisa_boleh) {
            echo "<script>alert('Gagal! Jumlah melebihi sisa pesanan. Sisa yang belum datang: $sisa_boleh');</script>";
        } else {
            // B. EKSEKUSI STORED PROCEDURE (Input Barang & Tambah Stok)
            $query_sp = "CALL sp_detail_penerimaan_insert($idp, $idbarang, $jumlah_terima)";
            
            if (mysqli_query($conn, $query_sp)) {
                
                // C. LOGIKA UPDATE STATUS OTOMATIS (REVISI DISINI)
                // Kita hitung Total Global PO vs Total Global Penerimaan (Semua penerimaan terkait PO ini)
                
                // 1. Total Barang di PO ini
                $cek_all_order = mysqli_fetch_assoc(mysqli_query($conn, "
                    SELECT SUM(jumlah) as total FROM detail_pengadaan WHERE idpengadaan = $idpengadaan
                "))['total'];

                // 2. Total Barang yang SUDAH Diterima (Dari semua penerimaan yang link ke PO ini)
                // Kita perlu join agar menghitung semua penerimaan milik PO tersebut
                $cek_all_recv  = mysqli_fetch_assoc(mysqli_query($conn, "
                    SELECT SUM(dp.jumlah_terima) as total 
                    FROM detail_penerimaan dp
                    JOIN penerimaan p ON dp.idpenerimaan = p.idpenerimaan
                    WHERE p.idpengadaan = $idpengadaan
                "))['total'];
                
                // Jika Total Diterima >= Total Dipesan
                if ($cek_all_recv >= $cek_all_order) {
                    // 1. Update Status Penerimaan ini jadi Selesai
                    mysqli_query($conn, "UPDATE penerimaan SET status = 'S' WHERE idpenerimaan = $idp");
                    
                    // 2. Update Status PENGADAAN (PO) jadi Selesai juga
                    mysqli_query($conn, "UPDATE pengadaan SET status = 'S' WHERE idpengadaan = $idpengadaan");
                }

                echo "<script>alert('Barang berhasil diterima & Stok bertambah!'); window.location='penerimaan_detail.php?id=$idp';</script>";
            } else {
                echo "<script>alert('Error Database: " . mysqli_error($conn) . "');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Penerimaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-primary">📥 Detail Penerimaan #<?= $header['idpenerimaan']; ?></h4>
        <a href="penerimaan.php" class="btn btn-secondary">Kembali</a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless mb-0">
                        <tr><th style="width:150px">No. Pengadaan</th><td>: #<?= $header['idpengadaan']; ?></td></tr>
                        <tr><th>Vendor</th><td>: <?= htmlspecialchars($header['nama_vendor']); ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless mb-0">
                        <tr><th style="width:150px">Tanggal Terima</th><td>: <?= $header['created_at']; ?></td></tr>
                        <tr><th>Status</th><td>: 
                            <?= ($header['status'] == 'P') 
                                ? "<span class='badge bg-warning text-dark'>Proses (Barang Belum Lengkap)</span>" 
                                : "<span class='badge bg-success'>Selesai (Lengkap)</span>"; ?>
                        </td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold text-secondary">
            📋 Daftar Barang yang Dipesan (Input Barang Datang Disini)
        </div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-primary text-center">
                    <tr>
                        <th>Nama Barang</th>
                        <th>Jml Pesan</th>
                        <th>Sudah Diterima (Total)</th>
                        <th>Sisa (Belum Datang)</th>
                        <th style="width: 250px;">Input Kedatangan</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Ambil daftar barang yang dipesan di Pengadaan ini
                $q_items = mysqli_query($conn, "
                    SELECT dp.idbarang, b.nama, dp.jumlah AS qty_pesan
                    FROM detail_pengadaan dp
                    JOIN barang b ON dp.idbarang = b.idbarang
                    WHERE dp.idpengadaan = $idpengadaan
                ");

                while ($item = mysqli_fetch_assoc($q_items)):
                    $idb = $item['idbarang'];
                    $qty_pesan = (int)$item['qty_pesan'];

                    // Hitung TOTAL diterima untuk barang ini (dari SEMUA penerimaan terkait PO ini)
                    // Supaya kalau ada penerimaan parsial (cicil), sisa-nya akurat.
                    $cek_terima = mysqli_query($conn, "
                        SELECT IFNULL(SUM(dp.jumlah_terima),0) as total 
                        FROM detail_penerimaan dp
                        JOIN penerimaan p ON dp.idpenerimaan = p.idpenerimaan
                        WHERE p.idpengadaan = $idpengadaan AND dp.barang_idbarang = $idb
                    ");
                    $qty_terima = (int)mysqli_fetch_assoc($cek_terima)['total'];
                    
                    $sisa = $qty_pesan - $qty_terima;
                ?>
                    <tr>
                        <td><?= htmlspecialchars($item['nama']); ?></td>
                        <td class="text-center fw-bold"><?= $qty_pesan; ?></td>
                        <td class="text-center"><?= $qty_terima; ?></td>
                        <td class="text-center fw-bold text-danger"><?= $sisa; ?></td>
                        <td>
                            <?php if ($sisa > 0): ?>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="barang_idbarang" value="<?= $idb; ?>">
                                    <input type="number" name="jumlah_terima" class="form-control form-control-sm" 
                                           placeholder="Jml Datang" min="1" max="<?= $sisa; ?>" required>
                                    <button type="submit" name="tambah_detail" class="btn btn-sm btn-success">
                                        Terima
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="badge bg-secondary w-100">✅ Sudah Lengkap</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold text-success">
            🚚 Riwayat Barang Masuk (Khusus Penerimaan Ini)
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-success text-center">
                    <tr>
                        <th>Nama Barang</th>
                        <th>Jumlah Masuk</th>
                        <th>Harga Beli (Satuan)</th>
                        <th>Subtotal Nilai</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $q_hist = mysqli_query($conn, "
                    SELECT b.nama, dp.jumlah_terima, dp.harga_satuan_terima, dp.sub_total_terima
                    FROM detail_penerimaan dp
                    JOIN barang b ON dp.barang_idbarang = b.idbarang
                    WHERE dp.idpenerimaan = $idp
                    ORDER BY dp.iddetail_penerimaan DESC
                ");

                if (mysqli_num_rows($q_hist) > 0) {
                    while ($hist = mysqli_fetch_assoc($q_hist)) {
                        echo "<tr>
                                <td>{$hist['nama']}</td>
                                <td class='text-center fw-bold'>{$hist['jumlah_terima']}</td>
                                <td class='text-end'>Rp " . number_format($hist['harga_satuan_terima'],0,',','.') . "</td>
                                <td class='text-end'>Rp " . number_format($hist['sub_total_terima'],0,',','.') . "</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' class='text-center text-muted p-3'>Belum ada barang yang diterima di sesi ini.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>