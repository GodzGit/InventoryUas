<?php include 'db.php'; ?>
<?php include 'layout/navbar.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Penjualan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
  <h4 class="fw-bold text-success mb-3">💰 Laporan Penjualan</h4>
  <div class="card shadow-sm border-0">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-success text-center">
          <tr>
            <th>ID</th>
            <th>Tanggal</th>
            <th>Kasir</th>
            <th>Barang</th>
            <th>Jumlah</th>
            <th>Harga Satuan</th>
            <th>Subtotal</th>
            <th>Margin (10%)</th> <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $data = mysqli_query($conn, "SELECT * FROM v_laporan_penjualan ORDER BY tanggal_penjualan DESC");
          
          if (mysqli_num_rows($data) > 0) {
            while ($d = mysqli_fetch_assoc($data)) {
              // Format Tanggal
              $tgl = date('d/m/Y H:i', strtotime($d['tanggal_penjualan']));
              
              // Format Uang
              $harga    = "Rp " . number_format($d['harga_satuan'], 0, ',', '.');
              $jumlah   = number_format($d['jumlah'], 0, ',', '.');
              $subtotal = "Rp " . number_format($d['subtotal'], 0, ',', '.');
              
              // AMBIL DARI KOLOM BARU (nilai_margin)
              $margin   = "Rp " . number_format($d['nilai_margin'], 0, ',', '.');
              $total    = "Rp " . number_format($d['total_per_item'], 0, ',', '.');

              echo "<tr>
                <td class='text-center'>{$d['idpenjualan']}</td>
                <td class='text-center'>{$tgl}</td>
                <td>{$d['kasir']}</td>
                <td>{$d['nama_barang']}</td>
                <td class='text-center'>{$jumlah}</td>
                <td class='text-end'>{$harga}</td>
                <td class='text-end'>{$subtotal}</td>
                <td class='text-end fw-bold text-success'>{$margin}</td> <td class='text-end fw-bold'>{$total}</td>
              </tr>";
            }
          } else {
            echo "<tr><td colspan='9' class='text-center text-muted'>Tidak ada data penjualan</td></tr>";
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