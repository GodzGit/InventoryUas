<?php include 'db.php'; ?>
<?php include 'layout/navbar.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Laporan Pengadaan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<div class="container mt-4">
  <h4 class="fw-bold text-primary mb-3">📦 Laporan Pengadaan</h4>
  <div class="card shadow-sm border-0"><div class="card-body table-responsive">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-primary"><tr>
        <th>ID</th><th>Tanggal</th><th>Vendor</th><th>Petugas</th><th>Barang</th>
        <th>Jumlah</th><th>Harga</th><th>Subtotal</th><th>Total</th>
      </tr></thead>
      <tbody>
      <?php
      $r = mysqli_query($conn, "SELECT * FROM v_laporan_pengadaan");
      if (mysqli_num_rows($r)>0){
        while($d=mysqli_fetch_assoc($r)){
          echo "<tr>";
          foreach($d as $v) echo "<td>".htmlspecialchars($v)."</td>";
          echo "</tr>";
        }
      } else echo "<tr><td colspan='9' class='text-center text-muted'>Belum ada data</td></tr>";
      ?>
      </tbody>
    </table>
  </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
