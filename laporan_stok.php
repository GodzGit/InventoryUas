<?php include 'db.php'; ?>
<?php include 'layout/navbar.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Stok Barang</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container-fluid mt-4">

    <h4 class="fw-bold text-secondary mb-3">📊 Laporan Stok Barang</h4>

    <div class="card shadow-sm border-0">
        <div class="card-body table-responsive">

            <table class="table table-bordered table-striped align-middle">
                <thead class="table-secondary text-center">
                    <tr>
                        <th>ID Barang</th>
                        <th>Nama Barang</th>
                        <th>Satuan</th>
                        <th>Stok Akhir</th>
                        <th>Terakhir Update</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $r = mysqli_query($conn, "SELECT * FROM v_laporan_stok ORDER BY idbarang ASC");

                    if (mysqli_num_rows($r) > 0) {
                        while ($d = mysqli_fetch_assoc($r)) {
                            $stok = $d['stok_akhir'] == '' ? 0 : $d['stok_akhir']; // Paksa jadi 0 jika kosong
                            $update = $d['terakhir_update'] ?? '-';

                            echo "<tr>
                                <td>{$d['idbarang']}</td>
                                <td>{$d['nama_barang']}</td>
                                <td>{$d['nama_satuan']}</td>
                                <td class='text-center fw-bold'>{$stok}</td>
                                <td>{$update}</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center text-muted'>Tidak ada data stok</td></tr>";
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
