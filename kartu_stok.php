<?php include 'db.php'; ?>
<?php include 'layout/navbar.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Judul View</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<div class="container mt-4">
  <h4 class="fw-bold text-primary mb-3">📘 Judul View</h4>
  <div class="card shadow-sm border-0"><div class="card-body table-responsive">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-primary">
        <tr>
          <?php
          $result = mysqli_query($conn, "DESCRIBE nama_view");
          while($col = mysqli_fetch_assoc($result)){
            echo "<th>{$col['Field']}</th>";
          }
          ?>
        </tr>
      </thead>
      <tbody>
      <?php
      $data = mysqli_query($conn, "SELECT * FROM nama_view");
      if(mysqli_num_rows($data)>0){
        while($row=mysqli_fetch_assoc($data)){
          echo "<tr>";
          foreach($row as $v) echo "<td>".htmlspecialchars($v)."</td>";
          echo "</tr>";
        }
      } else echo "<tr><td colspan='10' class='text-center text-muted'>Tidak ada data</td></tr>";
      ?>
      </tbody>
    </table>
  </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
