<?php include 'db.php'; ?>
<?php include 'layout/navbar.php'; ?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Penjualan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container-fluid mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="text-primary fw-bold">💰 Data Penjualan (Kasir)</h4>
      <form method="POST">
        <button type="submit" name="buat_transaksi" class="btn btn-success fw-bold">
            + Transaksi Baru
        </button>
      </form>
  </div>

  <div class="card shadow-sm border-0">
      <div class="card-body table-responsive">
          <table class="table table-bordered table-striped align-middle">
              <thead class="table-primary text-center">
                  <tr>
                      <th>ID Nota</th>
                      <th>Tanggal</th>
                      <th>Kasir</th>
                      <th>Total Belanja</th>
                      <th>Aksi</th>
                  </tr>
              </thead>
              <tbody>
              <?php
              $q = mysqli_query($conn, "
                  SELECT p.idpenjualan, p.created_at, p.total_nilai, u.username
                  FROM penjualan p
                  JOIN user u ON p.iduser = u.iduser
                  ORDER BY p.idpenjualan DESC
              ");

              if (mysqli_num_rows($q) > 0) {
                  while ($row = mysqli_fetch_assoc($q)) {
                      $tgl = date('d-m-Y H:i', strtotime($row['created_at']));
                      $total = "Rp " . number_format($row['total_nilai'], 0, ',', '.');
                      
                      echo "<tr>
                          <td class='text-center fw-bold'>#{$row['idpenjualan']}</td>
                          <td class='text-center'>{$tgl}</td>
                          <td>{$row['username']}</td>
                          <td class='text-end fw-bold text-success'>{$total}</td>
                          <td class='text-center'>
                              <a href='penjualan_detail.php?id={$row['idpenjualan']}' class='btn btn-sm btn-primary'>Detail</a>
                          </td>
                      </tr>";
                  }
              } else {
                  echo "<tr><td colspan='5' class='text-center text-muted'>Belum ada transaksi penjualan</td></tr>";
              }
              ?>
              </tbody>
          </table>
      </div>
  </div>
</div>

<?php
// PROSES MEMBUAT NOTA BARU
if (isset($_POST['buat_transaksi'])) {
    // Ambil ID User yang sedang login (Hardcode id=5 'kasir_rina' untuk simulasi)
    // Nanti ganti jadi: $_SESSION['iduser'];
    $id_kasir = 5; 

    // Panggil SP untuk buat header (Menggunakan Session Variable MySQL @id_baru)
    $query1 = "CALL sp_tambah_penjualan($id_kasir, @id_baru)";
    $query2 = "SELECT @id_baru AS id"; // Ambil ID yang baru digenerate

    if (mysqli_query($conn, $query1)) {
        $res = mysqli_query($conn, $query2);
        $row = mysqli_fetch_assoc($res);
        $id_transaksi_baru = $row['id'];

        // Redirect langsung ke halaman detail untuk input barang
        echo "<script>window.location='penjualan_detail.php?id={$id_transaksi_baru}';</script>";
    } else {
        echo "<script>alert('Gagal membuat transaksi: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>