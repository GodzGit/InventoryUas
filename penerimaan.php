<?php include 'db.php'; ?>
<?php include 'layout/navbar.php'; ?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Penerimaan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container-fluid mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="text-primary fw-bold">📥 Data Penerimaan</h4>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
          + Tambah Penerimaan
      </button>
  </div>

  <div class="card shadow-sm border-0">
      <div class="card-body table-responsive">

          <table class="table table-bordered table-striped align-middle">
              <thead class="table-primary text-center">
                  <tr>
                      <th>ID Terima</th>
                      <th>Tanggal Terima</th>
                      <th>Info Pengadaan (Vendor)</th>
                      <th>Dipesan Oleh (User PO)</th> <th>Status</th>
                      <th>Aksi</th>
                  </tr>
              </thead>
              <tbody>

              <?php
              // PERBAIKAN QUERY:
              // 1. Join user diarahkan ke pg.user_iduser (Pembuat Pengadaan)
              // 2. Ditambah join ke vendor
              $q = mysqli_query($conn, "
                  SELECT p.idpenerimaan, p.created_at, 
                         pg.idpengadaan,
                         u.username AS pembuat_po,  -- Ambil username pembuat PO
                         v.nama_vendor,             -- Ambil nama vendor
                         p.status
                  FROM penerimaan p
                  JOIN pengadaan pg ON p.idpengadaan = pg.idpengadaan
                  JOIN user u ON pg.user_iduser = u.iduser  -- JOIN KE PENGADAAN, BUKAN PENERIMAAN
                  JOIN vendor v ON pg.vendor_idvendor = v.idvendor
                  ORDER BY p.idpenerimaan DESC
              ");

              if (mysqli_num_rows($q) > 0) {
                  while ($row = mysqli_fetch_assoc($q)) {

                      $status = ($row['status'] == 'P')
                              ? "<span class='badge bg-warning text-dark'>Proses</span>"
                              : "<span class='badge bg-success'>Selesai</span>";
                      
                      // Format tanggal agar lebih rapi (opsional)
                      $tgl = date('d-m-Y H:i', strtotime($row['created_at']));

                      echo "
                      <tr>
                          <td class='text-center'>{$row['idpenerimaan']}</td>
                          <td class='text-center'>{$tgl}</td>
                          <td>
                            <strong>#{$row['idpengadaan']}</strong> <br>
                            <small class='text-muted'>{$row['nama_vendor']}</small>
                          </td>
                          <td>{$row['pembuat_po']}</td>
                          <td class='text-center'>{$status}</td>
                          <td class='text-center'>
                              <a href='penerimaan_detail.php?id={$row['idpenerimaan']}' 
                                 class='btn btn-sm btn-primary'>Detail Barang</a>
                          </td>
                      </tr>";
                  }
              } else {
                  echo "<tr><td colspan='6' class='text-center text-muted'>Belum ada data penerimaan</td></tr>";
              }
              ?>

              </tbody>
          </table>

      </div>
  </div>
</div>


<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
      <div class="modal-content">
          <form method="POST">

              <div class="modal-header bg-primary text-white">
                  <h5 class="modal-title">Tambah Penerimaan Baru</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>

              <div class="modal-body">

                  <div class="mb-3">
                      <label class="form-label fw-bold">Pilih Pengadaan (PO)</label>
                      <div class="form-text mb-2">Hanya menampilkan pengadaan yang statusnya masih 'Proses'</div>
                      
                      <select name="idpengadaan" class="form-select" required>
                          <option value="">-- Pilih Pengadaan --</option>

                          <?php
                          // Menampilkan opsi pengadaan beserta nama pembuatnya
                          $pengadaan = mysqli_query($conn, "
                              SELECT p.idpengadaan, v.nama_vendor, u.username
                              FROM pengadaan p
                              JOIN vendor v ON p.vendor_idvendor = v.idvendor
                              JOIN user u ON p.user_iduser = u.iduser
                              WHERE p.status = 'P'
                          ");

                          while ($p = mysqli_fetch_assoc($pengadaan)) {
                              echo "<option value='{$p['idpengadaan']}'>
                                      PO #{$p['idpengadaan']} - {$p['nama_vendor']} (Oleh: {$p['username']})
                                    </option>";
                          }
                          ?>
                      </select>
                  </div>

              </div>

              <div class="modal-footer">
                  <button type="submit" name="tambah_penerimaan" class="btn btn-success">Buat Penerimaan</button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
              </div>

          </form>
      </div>
  </div>
</div>

<?php
if (isset($_POST['tambah_penerimaan'])) {
    $idpengadaan = $_POST['idpengadaan'];
    
    // ID User Penerima (Orang Gudang). 
    // Idealnya diambil dari session login, misal: $_SESSION['iduser']
    // Untuk sementara kita hardcode user ID 4 (staf_anton/Gudang) atau 1
    $iduser_penerima = 1; 

    // Query Call SP
    $query = "CALL sp_tambah_penerimaan($idpengadaan, $iduser_penerima)";

    if (mysqli_query($conn, $query)) {
        // Karena insert pakai SP, kita ambil ID terakhir manual dari tabel
        $get = mysqli_query($conn, "SELECT idpenerimaan FROM penerimaan ORDER BY idpenerimaan DESC LIMIT 1");
        $baru = mysqli_fetch_assoc($get)['idpenerimaan'];

        echo "<script>
            alert('Penerimaan berhasil dibuat! Silakan cek fisik barang.');
            window.location='penerimaan_detail.php?id={$baru}';
        </script>";
    } else {
        echo "<script>alert('Gagal menambah penerimaan: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>