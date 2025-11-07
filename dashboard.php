<?php
session_start();
include 'config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: auth/login.php");
  exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// Ambil kategori untuk filter
$kategori = $db->query("SELECT * FROM kategori");

// Filter
$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$filter_nama = isset($_GET['nama_barang']) ? $_GET['nama_barang'] : '';

// Query utama
$sql = "
  SELECT 
    b.id,
    b.nama_barang,
    b.satuan,
    b.stok,
    k.nama_kategori,
    COALESCE((SELECT SUM(qty) FROM barang_masuk WHERE id_barang = b.id), 0) AS total_masuk,
    COALESCE((SELECT SUM(qty) FROM barang_keluar WHERE id_barang = b.id), 0) AS total_keluar
  FROM barang b
  JOIN kategori k ON b.id_kategori = k.id
  WHERE 1=1
";

if ($filter_kategori != '') {
  $sql .= " AND b.id_kategori = " . intval($filter_kategori);
}

if ($filter_nama != '') {
  $sql .= " AND b.nama_barang LIKE '%" . $db->real_escape_string($filter_nama) . "%'";
}

$sql .= " ORDER BY k.nama_kategori, b.nama_barang";
$data = $db->query($sql);
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Dashboard - Rexsy</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Select2 -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <!-- DataTables Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
</head>

<style>
  .btn-logout {
    transition: 0.2s;
  }

  .btn-logout:hover,
  .btn-logout:focus {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
    color: white !important;
  }

  .navbar .nav-link.active {
    color: white !important;
    font-weight: bold;
  }
</style>

<body class="bg-light">

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <span class="navbar-text text-white ms-auto d-lg-none d-block me-2">
        Halo, <?= htmlspecialchars($_SESSION['username']); ?>
      </span>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto mt-2 mt-lg-0">
          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active text-white' : ''; ?>" href="dashboard.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'index.php' && strpos($_SERVER['REQUEST_URI'], 'kategori') !== false ? 'active text-white' : ''; ?>" href="kategori/">Kategori</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'index.php' && strpos($_SERVER['REQUEST_URI'], 'barang') !== false ? 'active text-white' : ''; ?>" href="barang/">Barang</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'index.php' && strpos($_SERVER['REQUEST_URI'], 'barang-masuk') !== false ? 'active text-white' : ''; ?>" href="barang-masuk/">Barang Masuk</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'index.php' && strpos($_SERVER['REQUEST_URI'], 'barang-keluar') !== false ? 'active text-white' : ''; ?>" href="barang-keluar/">Barang Keluar</a>
          </li>
        </ul>

        <div class="d-none d-lg-flex align-items-center">
          <span class="text-white me-3">Halo, <?= htmlspecialchars($_SESSION['username']); ?></span>
          <a href="auth/logout.php" class="btn btn-outline-light btn-sm btn-logout">Logout</a>
        </div>

        <div class="d-lg-none mt-3">
          <a href="auth/logout.php" class="btn btn-outline-light btn-sm btn-logout">Logout</a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Konten Utama -->
  <div class="container py-5">
    <div class="text-center mb-5">
      <h3>Selamat Datang di Rexsy Collection</h3>
    </div>

    <form method="GET" class="row g-3 mb-4">
      <div class="col-md-4">
        <label for="kategori" class="form-label">Kategori</label>
        <select name="kategori" id="select-kategori" class="form-select">
          <?php if ($filter_kategori != ''):
            $kat = $db->query("SELECT * FROM kategori WHERE id = " . intval($filter_kategori))->fetch_assoc(); ?>
            <option value="<?= $kat['id']; ?>" selected><?= htmlspecialchars($kat['nama_kategori']); ?></option>
          <?php endif; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label for="nama_barang" class="form-label">Nama Barang</label>
        <select name="nama_barang" id="select-nama-barang" class="form-select">
          <?php if ($filter_nama != ''): ?>
            <option value="<?= htmlspecialchars($filter_nama); ?>" selected><?= htmlspecialchars($filter_nama); ?></option>
          <?php endif; ?>
        </select>
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <button type="submit" class="btn btn-primary btn-sm me-2">Tampilkan</button>
        <a href="dashboard.php" class="btn btn-secondary btn-sm">Reset</a>
      </div>
    </form>

    <table id="tabel-barang" class="table table-bordered table-striped nowrap w-100">
      <thead class="table-dark">
        <tr>
          <th>No</th>
          <th>Kategori</th>
          <th>Nama Barang</th>
          <th>Barang Masuk</th>
          <th>Barang Keluar</th>
          <th>Stok Akhir</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1;
        while ($row = $data->fetch_assoc()): ?>
          <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
            <td><?= htmlspecialchars($row['nama_barang']); ?></td>
            <td><?= number_format($row['total_masuk']) . ' ' . strtoupper($row['satuan']); ?></td>
            <td><?= number_format($row['total_keluar']) . ' ' . strtoupper($row['satuan']); ?></td>
            <td><?= number_format($row['stok']) . ' ' . strtoupper($row['satuan']); ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- jQuery & Bootstrap Bundle -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Select2 -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <!-- DataTables + Bootstrap 5 + Responsive -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

  <script>
    $(document).ready(function() {
      // Inisialisasi Select2 AJAX
      $('#select-kategori').select2({
        placeholder: "Pilih Kategori",
        ajax: {
          url: 'search_kategori.php',
          dataType: 'json',
          delay: 250,
          data: function(params) {
            return {
              q: params.term
            };
          },
          processResults: function(data) {
            return {
              results: data.map(item => ({
                id: item.id,
                text: item.text
              }))
            };
          },
          cache: true
        },
        minimumInputLength: 1,
        allowClear: true
      });

      $('#select-nama-barang').select2({
        placeholder: "Cari Nama Barang",
        ajax: {
          url: 'search_barang.php',
          dataType: 'json',
          delay: 250,
          data: function(params) {
            return {
              q: params.term
            };
          },
          processResults: function(data) {
            return {
              results: data.map(item => ({
                id: item.nama_barang,
                text: item.nama_barang
              }))
            };
          },
          cache: true
        },
        minimumInputLength: 1,
        allowClear: true
      });

      // Inisialisasi DataTables dengan Bootstrap 5 + Responsive
      $('#tabel-barang').DataTable({
        responsive: true,
        scrollX: true,
        paging: true,
        ordering: true,
        searching: false,
        language: {
          paginate: {
            previous: "Sebelumnya",
            next: "Berikutnya"
          },
          info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
          lengthMenu: "Tampilkan _MENU_ data"
        }
      });
    });
  </script>

</body>

</html>