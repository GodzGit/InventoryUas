<div class="d-flex">
  <!-- 🔹 Sidebar -->
  <nav class="d-flex flex-column flex-shrink-0 p-3 text-white bg-primary bg-gradient shadow-sm"
       style="width: 280px; min-height: 100vh;">
    
    <a href="dashboard_superadmin.php"
       class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none fw-bold">
      <span class="fs-4">📦 InventoriApp</span>
    </a>
    <hr>

    <ul class="nav nav-pills flex-column mb-auto">

      <!-- Master Data -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">⚙️ Master Data</a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="barang.php">Barang</a></li>
          <li><a class="dropdown-item" href="satuan.php">Satuan</a></li>
          <li><a class="dropdown-item" href="vendor.php">Vendor</a></li>
          <li><a class="dropdown-item" href="user.php">User</a></li>
          <li><a class="dropdown-item" href="role.php">Role</a></li>
          <li><a class="dropdown-item" href="margin_penjualan.php">Margin Penjualan</a></li>
        </ul>
      </li>

      <!-- Transaksi -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">🔁 Transaksi</a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="pengadaan.php">Pengadaan</a></li>
          <li><a class="dropdown-item" href="penerimaan.php">Penerimaan</a></li>
          <li><a class="dropdown-item" href="penjualan.php">Penjualan</a></li>
          <li><a class="dropdown-item" href="retur.php">Retur</a></li>
        </ul>
      </li>

      <!-- Laporan -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">📊 Laporan</a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="laporan_pengadaan.php">Laporan Pengadaan</a></li>
          <li><a class="dropdown-item" href="laporan_penerimaan.php">Laporan Penerimaan</a></li>
          <li><a class="dropdown-item" href="laporan_penjualan.php">Laporan Penjualan</a></li>
          <li><a class="dropdown-item" href="laporan_retur.php">Laporan Retur</a></li>
          <li><a class="dropdown-item" href="laporan_stok.php">Laporan Stok Barang</a></li>
        </ul>
      </li>

    </ul>

  </nav>