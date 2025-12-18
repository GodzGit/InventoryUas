<?php
include 'db.php';
?>
<?php include 'layout/navbar.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Pengadaan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .card { border-radius: 10px; }
    .table th, .table td { vertical-align: middle; text-align: center; }
    .badge-status { padding: .45em .6em; border-radius: .5rem; font-size: .85rem; }
    /* apabila layout sidebar fixed dengan margin-left:290px digunakan, sudah ada di file */
  </style>
</head>
<body class="bg-light">

<div class="container-fluid mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="text-primary fw-bold">📦 Data Pengadaan</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah Pengadaan</button>
  </div>

  <!-- Table Data -->
  <div class="card shadow-sm border-0">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-primary text-center">
          <tr>
            <th>ID</th>
            <th>Tanggal</th>
            <th>User</th>
            <th>Vendor</th>
            <th>Subtotal</th>
            <th>PPN</th>
            <th>Total</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // ambil data pengadaan dan join nama user + vendor
          $result = mysqli_query($conn, "
            SELECT 
              p.idpengadaan,
              p.timestamp,
              u.username AS nama_user,
              v.nama_vendor,
              p.subtotal_nilai,
              p.ppn,
              p.total_nilai,
              p.status AS status_pengadaan  
          FROM pengadaan p
          LEFT JOIN user u ON p.user_iduser = u.iduser
          LEFT JOIN vendor v ON p.vendor_idvendor = v.idvendor
          ORDER BY p.idpengadaan DESC
          ");

          if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
              // format rupiah
              $subtotal = "Rp " . number_format($row['subtotal_nilai'], 0, ',', '.');
              $ppn = "Rp " . number_format($row['ppn'], 0, ',', '.');
              $total = "Rp " . number_format($row['total_nilai'], 0, ',', '.');

              // status badge
              // Ganti $row['status'] jadi $row['status_pengadaan']
                $sts = $row['status_pengadaan']; 

                if ($sts == 'P') {
                    $statusBadge = "<span class='badge bg-warning text-dark'>Proses</span>";
                } elseif ($sts == 'S') {
                    $statusBadge = "<span class='badge bg-success'>Selesai</span>";
                } else {
                    // Kalau masih muncul A, berarti data di database memang salah input
                    $statusBadge = "<span class='badge bg-danger'>Error: $sts</span>";
                }

              echo "<tr>
                      <td>" . htmlspecialchars($row['idpengadaan']) . "</td>
                      <td>" . htmlspecialchars($row['timestamp']) . "</td>
                      <td>" . htmlspecialchars($row['nama_user']) . "</td>
                      <td>" . htmlspecialchars($row['nama_vendor']) . "</td>
                      <td>$subtotal</td>
                      <td>$ppn</td>
                      <td>$total</td>
                      <td>$statusBadge</td>
                      <td>
                        <a href='pengadaan_detail.php?id=" . urlencode($row['idpengadaan']) . "' class='btn btn-sm btn-primary'>Detail</a>
                      </td>
                    </tr>";
            }
          } else {
            echo "<tr><td colspan='9' class='text-center text-muted'>Belum ada data pengadaan</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Tambah Pengadaan -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Tambah Pengadaan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Vendor</label>
            <select name="vendor_id" class="form-select" required>
              <option value="">-- Pilih Vendor --</option>
              <?php
              // pakai vendor aktif untuk dropdown
              $vendors = mysqli_query($conn, "SELECT idvendor, nama_vendor FROM vendor WHERE status='A' OR status=1");
              while ($v = mysqli_fetch_assoc($vendors)) {
                $idv = (int)$v['idvendor'];
                $namav = htmlspecialchars($v['nama_vendor']);
                echo "<option value='{$idv}'>{$namav}</option>";
              }
              ?>
            </select>
          </div>

          <!-- menampilkan info bahwa subtotal dihitung dari detail -->
          <div class="mb-3">
            <label class="form-label">Catatan</label>
            <div class="form-control bg-light">Subtotal & total akan dihitung otomatis setelah menambahkan detail pengadaan.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="tambah_sp" class="btn btn-success">Simpan & Lanjut</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Proses SP -->
<?php
if (isset($_POST['tambah_sp'])) {
  // ambil vendor id dari form
  $vendor_id = (int)$_POST['vendor_id'];

  // user id sementara (ganti ke session login kalau sudah tersedia)
  $user_id = 1;

  // sesuai SP di DB — panggil SP. (kamu sebelumnya memanggil 3 param; pastikan SP di DB sesuai)
  // di sini saya gunakan 3 param seperti contohmu: user_id, vendor_id, subtotal(0)
  $call = "CALL sp_tambah_pengadaan($user_id, $vendor_id, 0)";

  if (mysqli_query($conn, $call)) {
    // ambil last inserted pengadaan id - beberapa MySQL config memerlukan SELECT LAST_INSERT_ID()
    // karena kita memanggil SP, mysqli_insert_id mungkin tidak berfungsi; ambil id terbaru secara manual:
    $lastRow = mysqli_query($conn, "SELECT idpengadaan FROM pengadaan ORDER BY idpengadaan DESC LIMIT 1");
    if ($lastRow && mysqli_num_rows($lastRow) > 0) {
      $last = mysqli_fetch_assoc($lastRow);
      $last_id = $last['idpengadaan'];
      echo "<script>alert('Pengadaan berhasil ditambahkan!');window.location='pengadaan_detail.php?id={$last_id}';</script>";
      exit;
    } else {
      echo "<script>alert('Pengadaan ditambahkan, tapi gagal ambil ID. Kembali ke daftar.');window.location='pengadaan.php';</script>";
      exit;
    }
  } else {
    echo "<div class='container mt-3'><div class='alert alert-danger'>Gagal menambah pengadaan: " . htmlspecialchars(mysqli_error($conn)) . "</div></div>";
  }
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
