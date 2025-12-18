<?php include 'db.php'; ?>
<?php include 'layout/navbar.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Pengadaan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid mt-4">
  <?php $idpengadaan = $_GET['id']; ?>
  <h4 class="fw-bold text-primary mb-3">📋 Detail Pengadaan #<?= $idpengadaan ?></h4>

  <a href="pengadaan.php" class="btn btn-primary mb-3"><button class="btn btn-primary">Kembali</button></a>
  <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#modalTambahDetail">+ Tambah Barang</button>

  <div class="card shadow-sm border-0">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-primary text-center">
          <tr>
            <th>ID</th>
            <th>Barang</th>
            <th>Harga Satuan</th>
            <th>Jumlah</th>
            <th>Sub Total</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $q = mysqli_query($conn, "
            SELECT dp.iddetail_pengadaan, b.nama AS nama_barang, dp.harga_satuan, dp.jumlah, dp.sub_total
            FROM detail_pengadaan dp
            JOIN barang b ON dp.idbarang = b.idbarang
            WHERE dp.idpengadaan = $idpengadaan
          ");
          if (mysqli_num_rows($q) > 0) {
            while ($r = mysqli_fetch_assoc($q)) {
              echo "<tr>
                      <td>{$r['iddetail_pengadaan']}</td>
                      <td>{$r['nama_barang']}</td>
                      <td>{$r['harga_satuan']}</td>
                      <td>{$r['jumlah']}</td>
                      <td>{$r['sub_total']}</td>
                    </tr>";
            }
          } else {
            echo "<tr><td colspan='5' class='text-center text-muted'>Belum ada detail pengadaan</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Tambah Detail -->
<div class="modal fade" id="modalTambahDetail" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Tambah Barang ke Pengadaan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label>Barang</label>
            <select name="idbarang" id="idbarang" class="form-select" required>
              <option value="">-- Pilih Barang --</option>
              <?php
              $barang = mysqli_query($conn, "SELECT idbarang, nama, harga FROM barang WHERE status=1");
              $data_barang = [];
              while ($b = mysqli_fetch_assoc($barang)) {
                $data_barang[$b['idbarang']] = $b['harga'];
                echo "<option value='{$b['idbarang']}'>{$b['nama']}</option>";
              }
              ?>
            </select>
          </div>
          <div class="mb-2">
            <label>Harga Satuan</label>
            <input type="number" name="harga" id="harga" class="form-control" readonly>
          </div>
          <div class="mb-2">
            <label>Jumlah</label>
            <input type="number" name="jumlah" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="tambah_detail" class="btn btn-success">Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
// Menyimpan data detail pengadaan
if (isset($_POST['tambah_detail'])) {
  $idbarang = $_POST['idbarang'];
  $harga = $_POST['harga'];
  $jumlah = $_POST['jumlah'];
  $subtotal = $harga * $jumlah;

  mysqli_query($conn, "INSERT INTO detail_pengadaan (idbarang, idpengadaan, harga_satuan, jumlah, sub_total)
                       VALUES ($idbarang, $idpengadaan, $harga, $jumlah, $subtotal)");

  echo "<script>alert('Barang berhasil ditambahkan!');window.location='pengadaan_detail.php?id=$idpengadaan';</script>";
}
?>

<!-- JS untuk ambil harga langsung dari array PHP -->
<script>
  const hargaBarang = <?php echo json_encode($data_barang); ?>;
  document.querySelector('#idbarang').addEventListener('change', function() {
    const id = this.value;
    document.getElementById('harga').value = id ? hargaBarang[id] || 0 : '';
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
