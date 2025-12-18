<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Barang Aktif</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include 'layout/navbar.php'; ?>

<div class="container mt-4">
  <h4 class="fw-bold text-primary mb-3">📦 Barang Aktif</h4>
  <div class="card shadow-sm border-0">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-primary text-center">
          <tr>
            <th>ID Barang</th>
            <th>Jenis</th>
            <th>Nama Barang</th>
            <th>ID Satuan</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $data = mysqli_query($conn, "SELECT * FROM v_barang_aktif");
        if(mysqli_num_rows($data)>0){
          while($row=mysqli_fetch_assoc($data)){
            echo "<tr>
                    <td>{$row['idbarang']}</td>
                    <td>{$row['jenis']}</td>
                    <td>{$row['nama']}</td>
                    <td>{$row['idsatuan']}</td>
                    <td><span class='badge bg-success'>Aktif ✅</span></td>
                  </tr>";
          }
        } else {
          echo "<tr><td colspan='5' class='text-center text-muted'>Tidak ada barang aktif</td></tr>";
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
