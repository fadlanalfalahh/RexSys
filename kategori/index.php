<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../auth/login.php");
  exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
$result = $db->query("SELECT * FROM kategori ORDER BY id DESC");

// Proses tambah kategori via POST AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_kategori'])) {
  $nama = trim($_POST['nama_kategori']);

  if ($nama !== '') {
    // Cek apakah nama kategori sudah ada
    $cek = $db->prepare("SELECT id FROM kategori WHERE nama_kategori = ?");
    $cek->bind_param("s", $nama);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows > 0) {
      echo json_encode([
        'status' => 'error',
        'message' => 'Kategori sudah ada.'
      ]);
    } else {
      // Lanjut insert
      $stmt = $db->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
      $stmt->bind_param("s", $nama);

      if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
      } else {
        echo json_encode([
          'status' => 'error',
          'message' => 'Gagal menyimpan kategori.'
        ]);
      }
    }
    exit;
  } else {
    echo json_encode([
      'status' => 'error',
      'message' => 'Nama kategori tidak boleh kosong.'
    ]);
    exit;
  }
}

// Proses edit kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'edit') {
  $id = intval($_POST['edit_id']);
  $nama = trim($_POST['edit_nama']);

  if ($nama !== '') {
    // Cek apakah nama kategori sudah ada (dan bukan dirinya sendiri)
    $cek = $db->prepare("SELECT id FROM kategori WHERE nama_kategori = ? AND id != ?");
    $cek->bind_param("si", $nama, $id);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows > 0) {
      echo json_encode(['status' => 'error', 'message' => 'Kategori sudah ada.']);
    } else {
      $stmt = $db->prepare("UPDATE kategori SET nama_kategori = ? WHERE id = ?");
      $stmt->bind_param("si", $nama, $id);
      if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal update kategori.']);
      }
    }
    exit;
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Nama kategori tidak boleh kosong.']);
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Data Kategori</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <!-- DataTables Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

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
</head>

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
            <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active text-white' : ''; ?>" href="../dashboard.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'index.php' ? 'active text-white' : ''; ?>" href="index.php">Kategori</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/barang/') !== false ? 'active text-white' : ''; ?>" href="../barang/">Barang</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/barang-masuk/') !== false ? 'active text-white' : ''; ?>" href="../barang-masuk/">Barang Masuk</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/barang-keluar/') !== false ? 'active text-white' : ''; ?>" href="../barang-keluar/">Barang Keluar</a>
          </li>
        </ul>

        <div class="d-none d-lg-flex align-items-center">
          <span class="text-white me-3">Halo, <?= htmlspecialchars($_SESSION['username']); ?></span>
          <a href="../auth/logout.php" class="btn btn-outline-light btn-sm btn-logout">Logout</a>
        </div>

        <div class="d-lg-none mt-3">
          <a href="../auth/logout.php" class="btn btn-outline-light btn-sm btn-logout">Logout</a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Konten -->
  <div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>Data Kategori</h3>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalKategori">Tambah Kategori</button>
    </div>

    <div class="table-responsive">
      <table id="tabel-kategori" class="table table-bordered table-hover table-striped nowrap w-100">
        <thead class="table-dark">
          <tr>
            <th width="50">No</th>
            <th>Nama Kategori</th>
            <th width="125">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1;
          while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $no++; ?></td>
              <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
              <td class="text-nowrap">
                <div class="d-flex gap-1 flex-wrap">
                  <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="<?= $row['id']; ?>" data-nama="<?= htmlspecialchars($row['nama_kategori']); ?>">Edit</button>
                  <button type="button" class="btn btn-danger btn-sm btn-hapus" data-id="<?= $row['id']; ?>">Hapus</button>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal Tambah Kategori -->
  <div class="modal fade" id="modalKategori" tabindex="-1">
    <div class="modal-dialog">
      <form id="formTambahKategori" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Kategori</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="nama_kategori" class="form-label">Nama Kategori</label>
            <input type="text" class="form-control" name="nama_kategori" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Edit Kategori -->
  <div class="modal fade" id="modalEditKategori" tabindex="-1">
    <div class="modal-dialog">
      <form id="formEditKategori" class="modal-content">
        <input type="hidden" name="edit_id" id="edit_id">
        <div class="modal-header">
          <h5 class="modal-title">Edit Kategori</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_nama" class="form-label">Nama Kategori</label>
            <input type="text" class="form-control" name="edit_nama" id="edit_nama" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Script -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- jQuery (dibutuhkan oleh DataTables) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- DataTables + Bootstrap 5 + Responsive -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

  <script>
    document.getElementById('formTambahKategori').addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);

      fetch('index.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(result => {
          if (result.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: 'Kategori berhasil ditambahkan',
              timer: 1500,
              showConfirmButton: false
            }).then(() => location.reload());
          } else {
            Swal.fire('Gagal', result.message, 'error');
          }
        })
        .catch(() => {
          Swal.fire('Error', 'Terjadi kesalahan', 'error');
        });
    });

    // Isi modal edit saat tombol diklik
    document.querySelectorAll('.btn-edit').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const nama = this.dataset.nama;

        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;

        const modal = new bootstrap.Modal(document.getElementById('modalEditKategori'));
        modal.show();
      });
    });

    // Submit edit kategori
    document.getElementById('formEditKategori').addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      formData.append('aksi', 'edit');

      fetch('index.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(result => {
          if (result.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: 'Kategori berhasil diperbarui',
              timer: 1500,
              showConfirmButton: false
            }).then(() => location.reload());
          } else {
            Swal.fire('Gagal', result.message, 'error');
          }
        })
        .catch(() => {
          Swal.fire('Error', 'Terjadi kesalahan', 'error');
        });
    });

    // Hapus dengan SweetAlert
    document.querySelectorAll('.btn-hapus').forEach(function(button) {
      button.addEventListener('click', function() {
        const id = this.dataset.id;

        Swal.fire({
          title: 'Yakin ingin menghapus?',
          text: "Data tidak bisa dikembalikan!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Ya, hapus!',
          cancelButtonText: 'Batal',
          reverseButtons: true
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = 'hapus.php?id=' + id;
          }
        });
      });
    });

    $(document).ready(function() {
      $('#tabel-kategori').DataTable({
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