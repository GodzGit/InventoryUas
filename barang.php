<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Barang</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include 'layout/navbar.php'; ?>

<div class="container mt-4">
  <h4 class="fw-bold text-primary mb-3">📦 Data Barang</h4>

  <div class="mb-3 d-flex gap-2">
    <a href="barang.php" class="btn btn-primary btn-sm">Reset Filter</a>
    <a href="barang.php?filter=aktif" class="btn btn-success btn-sm">Hanya Aktif</a>
    <a href="barang.php?filter=nonaktif" class="btn btn-secondary btn-sm">Hanya Tidak Aktif</a>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-primary">
          <tr>
            <th>Jenis</th>
            <th>Nama</th>
            <th>Satuan</th>
            <th>Harga</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php
        // 1. Ambil Parameter
        $filter = $_GET['filter'] ?? '';
        $view   = $_GET['view'] ?? ''; // Parameter baru untuk cek limit

        // 2. Dasar Query
        $sql = "SELECT * FROM v_master_barang";

        // 3. Logic Filter Status
        if($filter == 'aktif'){
          $sql .= " WHERE status = '✅ Aktif'";
        } elseif($filter == 'nonaktif'){
          $sql .= " WHERE status = '❎ Tidak Aktif'";
        }

        // 4. Urutkan
        $sql .= " ORDER BY status ASC";

        // 5. LOGIC BATAS 3 DATA (LIMIT)
        // Jika view BUKAN 'all', maka batasi 3
        if($view != 'all') {
            $sql .= " LIMIT 3";
        }

        // Jalankan query
        $data = mysqli_query($conn, $sql);
        
        if(mysqli_num_rows($data) > 0){
          while($row = mysqli_fetch_assoc($data)){
            echo "<tr>";
            echo "<td>{$row['jenis']}</td>";
            echo "<td>{$row['nama']}</td>";
            echo "<td>{$row['nama_satuan']}</td>";
            echo "<td>Rp ".number_format($row['harga'],0,',','.')."</td>";
            echo "<td>{$row['status']}</td>";
            echo "</tr>";
          }
        } else {
          echo "<tr><td colspan='5' class='text-center text-muted'>Tidak ada data</td></tr>";
        }
        ?>
        </tbody>
      </table>

      <?php if($view != 'all' && mysqli_num_rows($data) >= 3): ?>
          <div class="text-center mt-3">
              <a href="barang.php?view=all&filter=<?= htmlspecialchars($filter) ?>" class="btn btn-outline-primary w-100">
                  ⬇️ Lihat Seluruh Data
              </a>
          </div>
      <?php endif; ?>

      <?php if($view == 'all'): ?>
          <div class="text-center mt-3">
              <a href="barang.php?filter=<?= htmlspecialchars($filter) ?>" class="btn btn-outline-secondary w-100">
                  ⬆️ Tampilkan Sedikit Saja (3 Teratas)
              </a>
          </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>