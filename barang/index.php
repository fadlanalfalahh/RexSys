<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../auth/login.php");
  exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// Tambah Barang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
  $nama = trim($_POST['nama_barang']);
  $id_kategori = intval($_POST['id_kategori']);
  $satuan = trim($_POST['satuan']);

  $cek = $db->prepare("SELECT id FROM barang WHERE nama_barang = ?");
  $cek->bind_param("s", $nama);
  $cek->execute();
  $cek->store_result();

  if ($cek->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Barang sudah ada.']);
  } else {
    $stmt = $db->prepare("INSERT INTO barang (nama_barang, id_kategori, satuan) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $nama, $id_kategori, $satuan);
    if ($stmt->execute()) {
      echo json_encode(['status' => 'success']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan barang.']);
    }
  }
  exit;
}

// Edit Barang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'edit') {
  $id = intval($_POST['edit_id']);
  $nama = trim($_POST['edit_nama_barang']);
  $id_kategori = intval($_POST['edit_id_kategori']);
  $satuan = trim($_POST['edit_satuan']);

  $cek = $db->prepare("SELECT id FROM barang WHERE nama_barang = ? AND id != ?");
  $cek->bind_param("si", $nama, $id);
  $cek->execute();
  $cek->store_result();

  if ($cek->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Nama barang sudah ada.']);
  } else {
    $stmt = $db->prepare("UPDATE barang SET nama_barang = ?, id_kategori = ?, satuan = ? WHERE id = ?");
    $stmt->bind_param("sisi", $nama, $id_kategori, $satuan, $id);
    if ($stmt->execute()) {
      echo json_encode(['status' => 'success']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Gagal mengedit barang.']);
    }
  }
  exit;
}

// Hapus Barang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {
  $id = intval($_POST['id']);

  $stmt = $db->prepare("DELETE FROM barang WHERE id = ?");
  $stmt->bind_param("i", $id);

  if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus barang.']);
  }
  exit;
}

// Ambil Data
$result = $db->query("SELECT barang.*, kategori.nama_kategori FROM barang JOIN kategori ON barang.id_kategori = kategori.id ORDER BY barang.id DESC");
$kategori_list = $db->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Data Barang</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <!-- DataTables Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
  <!-- Select2 -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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
            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/kategori/') !== false ? 'active text-white' : ''; ?>" href="../kategori/">Kategori</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'index.php' ? 'active text-white' : ''; ?>" href="index.php">Barang</a>
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
      <h3>Data Barang</h3>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">Tambah Barang</button>
    </div>

    <div class="table-responsive">
      <table id="tabel-barang" class="table table-bordered table-hover table-striped nowrap w-100">
        <thead class="table-dark">
          <tr>
            <th width="50">No</th>
            <th>Nama Barang</th>
            <th>Kategori</th>
            <th>Satuan</th>
            <th width="125">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1;
          while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $no++; ?></td>
              <td><?= htmlspecialchars($row['nama_barang']); ?></td>
              <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
              <td><?= htmlspecialchars($row['satuan']); ?></td>
              <td class="text-nowrap">
                <div class="d-flex gap-1 flex-wrap">
                  <button class="btn btn-warning btn-sm btn-edit"
                    data-id="<?= $row['id']; ?>"
                    data-nama="<?= htmlspecialchars($row['nama_barang']); ?>"
                    data-kategori="<?= $row['id_kategori']; ?>"
                    data-satuan="<?= $row['satuan']; ?>">Edit</button>
                  <button class="btn btn-danger btn-sm btn-hapus" data-id="<?= $row['id']; ?>">Hapus</button>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal Tambah -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
      <form id="formTambah" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Barang</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Nama Barang</label>
            <input type="text" class="form-control" name="nama_barang" required>
          </div>
          <div class="mb-3">
            <label>Kategori</label>
            <select class="form-select select2-barang" name="id_kategori" required>
              <option value="">Pilih Kategori</option>
              <?php while ($kat = $kategori_list->fetch_assoc()): ?>
                <option value="<?= $kat['id']; ?>"><?= htmlspecialchars($kat['nama_kategori']); ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label>Satuan</label>
            <select class="form-select select2-barang" name="satuan" required>
              <option value="">Pilih Satuan</option>
              <option value="PCS">PCS</option>
              <option value="LUSIN">LUSIN</option>
              <option value="KODI">KODI</option>
              <option value="SERIE">SERIE</option>
              <option value="SET">SET</option>
              <option value="PACK">PACK</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Edit -->
  <div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
      <form id="formEdit" class="modal-content">
        <input type="hidden" name="edit_id" id="edit_id">
        <div class="modal-header">
          <h5 class="modal-title">Edit Barang</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Nama Barang</label>
            <input type="text" class="form-control" name="edit_nama_barang" id="edit_nama_barang" required>
          </div>
          <div class="mb-3">
            <label>Kategori</label>
            <select class="form-select select2-barang" name="edit_id_kategori" id="edit_id_kategori" required>
              <option value="">Pilih Kategori</option>
              <?php $kategori_list2 = $db->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
              while ($kat = $kategori_list2->fetch_assoc()): ?>
                <option value="<?= $kat['id']; ?>"><?= htmlspecialchars($kat['nama_kategori']); ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label>Satuan</label>
            <select class="form-select select2-barang" name="edit_satuan" id="edit_satuan" required>
              <option value="">Pilih Satuan</option>
              <option value="PCS">PCS</option>
              <option value="LUSIN">LUSIN</option>
              <option value="KODI">KODI</option>
              <option value="SERIE">SERIE</option>
              <option value="SET">SET</option>
              <option value="PACK">PACK</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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
  <!-- Select2 -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script>
    $(document).ready(function() {
      // Modal Tambah
      $('#modalTambah').on('shown.bs.modal', function() {
        $(this).find('.select2-barang').select2({
          dropdownParent: $('#modalTambah'),
          width: '100%',
          placeholder: 'Pilih Opsi'
        });
      });

      $('#modalTambah').on('hidden.bs.modal', function() {
        $(this).find('input[name="nama_barang"]').val('');
        $(this).find('select[name="id_kategori"]').val('').trigger('change');
        $(this).find('select[name="satuan"]').val('').trigger('change');
      });

      // Modal Edit
      $('#modalEdit').on('shown.bs.modal', function() {
        $(this).find('.select2-barang').select2({
          dropdownParent: $('#modalEdit'),
          width: '100%',
          placeholder: 'Pilih Opsi'
        });
      });

      $('#modalEdit').on('hidden.bs.modal', function() {
        $('#edit_id').val('');
        $('#edit_nama_barang').val('');
        $('#edit_id_kategori').val('').trigger('change');
        $('#edit_satuan').val('').trigger('change');
      });
    });

    document.getElementById('formTambah').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append('aksi', 'tambah');

      fetch('index.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(res => {
          if (res.status === 'success') {
            Swal.fire('Berhasil', 'Barang ditambahkan', 'success').then(() => location.reload());
          } else {
            Swal.fire('Gagal', res.message, 'error');
          }
        });
    });

    document.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_nama_barang').value = this.dataset.nama;
        document.getElementById('edit_id_kategori').value = this.dataset.kategori;

        // Tambahan: atur satuan
        $('#edit_satuan').val(this.dataset.satuan).trigger('change');

        new bootstrap.Modal(document.getElementById('modalEdit')).show();
      });
    });

    document.getElementById('formEdit').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append('aksi', 'edit');

      fetch('index.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(res => {
          if (res.status === 'success') {
            Swal.fire('Berhasil', 'Barang diperbarui', 'success').then(() => location.reload());
          } else {
            Swal.fire('Gagal', res.message, 'error');
          }
        });
    });

    document.querySelectorAll('.btn-hapus').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.dataset.id;
        Swal.fire({
          title: 'Yakin ingin hapus?',
          text: "Data akan dihapus permanen!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, Hapus!',
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          reverseButtons: true
        }).then((result) => {
          if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('aksi', 'hapus');
            formData.append('id', id);

            fetch('index.php', {
                method: 'POST',
                body: formData
              })
              .then(res => res.json())
              .then(res => {
                if (res.status === 'success') {
                  Swal.fire('Terhapus!', 'Barang berhasil dihapus.', 'success')
                    .then(() => location.reload());
                } else {
                  Swal.fire('Gagal', res.message, 'error');
                }
              });
          }
        });
      });
    });
    $(document).ready(function() {
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