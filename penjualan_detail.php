<?php   
include 'db.php';
include 'layout/navbar.php';

// Pastikan MySQLi throw exception agar kita bisa tangkap error trigger (Stok Kurang)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id_nota = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil Header Transaksi
$h = mysqli_query($conn, "
    SELECT p.*, u.username 
    FROM penjualan p 
    JOIN user u ON p.iduser = u.iduser 
    WHERE idpenjualan = $id_nota
");
$header = mysqli_fetch_assoc($h);

if (!$header) {
    echo "<script>alert('Transaksi tidak ditemukan!'); window.location='penjualan.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Transaksi Penjualan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold text-primary mb-0">🛒 Transaksi #<?= $id_nota; ?></h4>
            <small class="text-muted">Kasir: <?= $header['username']; ?> | Tgl: <?= $header['created_at']; ?></small>
        </div>
        <div>
             <h3 class="fw-bold text-success">Total: Rp <?= number_format($header['total_nilai'], 0, ',', '.'); ?></h3>
        </div>
    </div>
    
    <hr>
    <a href="penjualan.php" class="btn btn-secondary mb-3">Kembali ke Daftar</a>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambahBarang">
        + Tambah Barang (F2)
    </button>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-primary text-center">
                    <tr>
                        <th>Barang</th>
                        <th>Harga</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $q_detail = mysqli_query($conn, "
                    SELECT dp.*, b.nama 
                    FROM detail_penjualan dp 
                    JOIN barang b ON dp.idbarang = b.idbarang 
                    WHERE penjualan_idpenjualan = $id_nota
                    ORDER BY dp.iddetail_penjualan DESC
                ");

                if (mysqli_num_rows($q_detail) > 0) {
                    while ($item = mysqli_fetch_assoc($q_detail)) {
                        echo "<tr>
                            <td>{$item['nama']}</td>
                            <td class='text-end'>Rp " . number_format($item['harga_satuan'],0,',','.') . "</td>
                            <td class='text-center'>{$item['jumlah']}</td>
                            <td class='text-end fw-bold'>Rp " . number_format($item['subtotal'],0,',','.') . "</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' class='text-center text-muted p-4'>Keranjang masih kosong.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahBarang" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Input Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Barang</label>
                            <select name="idbarang" class="form-select" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php
                                // Ambil Margin Aktif saat ini untuk ditampilkan di estimasi harga
                                $q_margin = mysqli_query($conn, "SELECT persen FROM margin_penjualan WHERE status=1 ORDER BY idmargin_penjualan DESC LIMIT 1");
                                $d_margin = mysqli_fetch_assoc($q_margin);
                                $persen_margin = $d_margin ? (float)$d_margin['persen'] : 0;

                                // Ambil barang
                                $brg = mysqli_query($conn, "
                                    SELECT idbarang, nama, harga, 
                                        fn_stok_akhir(idbarang) as stok 
                                    FROM barang 
                                    WHERE status = 1
                                    
                                ");

                                while ($b = mysqli_fetch_assoc($brg)) {
                                    // Hitung Harga Jual untuk Tampilan (Display Only)
                                    $harga_modal = $b['harga'];
                                    $harga_jual  = $harga_modal + ($harga_modal * $persen_margin / 100);
                                    
                                    // Format Rupiah
                                    $txt_harga = number_format($harga_jual, 0, ',', '.');
                                    
                                    // Logic Stok
                                    $disabled = ($b['stok'] <= 0) ? "disabled" : "";
                                    $label_stok = ($b['stok'] <= 0) ? "HABIS" : "Sisa: {$b['stok']}";
                                    $warna_stok = ($b['stok'] <= 0) ? "🔴" : "🟢";
                                    
                                    echo "<option value='{$b['idbarang']}' $disabled>
                                            {$b['nama']} (Rp {$txt_harga}) - {$warna_stok} [{$label_stok}]
                                        </option>";
                                }
                                ?>
                            </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah</label>
                        <input type="number" name="jumlah" class="form-control" min="1" value="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah_item" class="btn btn-success">Masukan Keranjang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// PROSES INPUT BARANG
if (isset($_POST['tambah_item'])) {
    $id_barang = (int)$_POST['idbarang'];
    $jumlah = (int)$_POST['jumlah'];

    try {
        // Panggil SP Detail Penjualan
        // SP ini otomatis: Insert Detail -> Insert Kartu Stok (Keluar) -> Update Header Total
        $query = "CALL sp_detail_penjualan_insert($id_nota, $id_barang, $jumlah)";
        mysqli_query($conn, $query);

        // Jika sukses, refresh
        echo "<script>window.location='penjualan_detail.php?id=$id_nota';</script>";

    } catch (mysqli_sql_exception $e) {
        // TANGKAP ERROR DARI TRIGGER (Error 45000: Stok Tidak Cukup)
        $pesan_error = $e->getMessage();
        
        // Bersihkan pesan error default MySQL biar enak dibaca user
        // Biasanya formatnya: "Uncaught mysqli_sql_exception: TRANSAKSI GAGAL: ..."
        echo "<script>alert('Gagal! " . addslashes($pesan_error) . "');</script>";
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const myModal = document.getElementById('modalTambahBarang')
    const myInput = document.querySelector('input[name="jumlah"]')
    myModal.addEventListener('shown.bs.modal', () => {
        myInput.focus()
    })
</script>
</body>
</html>