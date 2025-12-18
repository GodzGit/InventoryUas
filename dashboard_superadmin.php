<?php include 'db.php'; ?>

    <?php include 'layout/navbar.php'; ?> 
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Superadmin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">


<!-- 🔹 Isi Dashboard -->
<div class="container mt-5">
  <div class="card shadow-sm border-0">
    <div class="card-body text-center py-5">
      <h2 class="fw-bold text-primary mb-3">Selamat Datang, Superadmin!</h2>
      <p class="text-muted fs-5">Gunakan menu di samping untuk mengelola data master dan transaksi inventori.</p>
      <hr class="my-4">
      <div class="row justify-content-center">
        <div class="col-md-3">
          <a href="laporan_stok.php" class="btn btn-outline-primary w-100">📦 Lihat Stok Barang</a>
        </div>
        <div class="col-md-3 mt-3 mt-md-0">
          <a href="laporan_penjualan.php" class="btn btn-outline-success w-100">💰 Lihat Penjualan</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 🔹 Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


<!-- catatan nama view File	View	Judul
v_master_barang.php	v_master_barang	Master Barang
v_master_satuan.php	v_master_satuan	Master Satuan
v_master_vendor.php	v_master_vendor	Master Vendor
v_master_role.php	v_master_role	Master Role
v_master_user.php	v_master_user	Master User
v_master_margin_penjualan.php	v_master_margin_penjualan	Master Margin Penjualan
v_barang_aktif.php	v_barang_aktif	Barang Aktif
v_satuan_aktif.php	v_satuan_aktif	Satuan Aktif
v_vendor_aktif.php	v_vendor_aktif	Vendor Aktif
v_margin_penjualan_aktif.php	v_margin_penjualan_aktif	Margin Penjualan Aktif -->