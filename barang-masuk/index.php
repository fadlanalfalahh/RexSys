<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../auth/login.php");
  exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// Tambah Barang Masuk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
  $id_barang = intval($_POST['id_barang']);
  $qty = intval($_POST['qty']);

  // 1. Tambah ke tabel barang_masuk
  $stmt1 = $db->prepare("INSERT INTO barang_masuk (id_barang, qty) VALUES (?, ?)");
  $stmt1->bind_param("ii", $id_barang, $qty);

  if ($stmt1->execute()) {
    $stmt2 = $db->prepare("UPDATE barang SET stok = stok + ? WHERE id = ?");
    $stmt2->bind_param("ii", $qty, $id_barang);
    $stmt2->execute();

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
  } else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan barang masuk.']);
  }
  exit;
}

// Edit Barang Masuk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'edit_masuk') {
  $id = intval($_POST['edit_id']);
  $id_barang = intval($_POST['edit_id_barang']);
  $qty = intval($_POST['edit_qty']);

  $stmt = $db->prepare("UPDATE barang_masuk SET id_barang = ?, qty = ?, tanggal = NOW() WHERE id = ?");
  $stmt->bind_param("iii", $id_barang, $qty, $id);

  if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengedit barang masuk.']);
  }
  exit;
}

// Hapus Barang Masuk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'hapus_masuk') {
  $id = intval($_POST['id']);

  // Ambil data qty dan id_barang dari data yang akan dihapus
  $get = $db->prepare("SELECT id_barang, qty FROM barang_masuk WHERE id = ?");
  $get->bind_param("i", $id);
  $get->execute();
  $result = $get->get_result();

  if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $id_barang = $data['id_barang'];
    $qty = $data['qty'];

    // Kurangi stok di tabel barang
    $update = $db->prepare("UPDATE barang SET stok = stok - ? WHERE id = ?");
    $update->bind_param("ii", $qty, $id_barang);
    $update->execute();

    // Hapus data barang_masuk
    $stmt = $db->prepare("DELETE FROM barang_masuk WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
      echo json_encode(['status' => 'success']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data.']);
    }
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
  }

  exit;
}

// Ambil Data
$result = $db->query("SELECT barang.*, kategori.nama_kategori FROM barang JOIN kategori ON barang.id_kategori = kategori.id ORDER BY barang.id DESC");
$kategori_list = $db->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
// Ambil daftar barang untuk dropdown
$barang_list = $db->query("
  SELECT b.id, b.nama_barang, b.satuan, k.nama_kategori
  FROM barang b
  JOIN kategori k ON b.id_kategori = k.id
  ORDER BY k.nama_kategori ASC, b.nama_barang ASC
");
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

    /* Hilangkan panah atas bawah di input type number */
    input[type=number]::-webkit-inner-spin-button,
    input[type=number]::-webkit-outer-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }

    input[type=number] {
      -moz-appearance: textfield;
      /* Firefox */
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
            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/barang/') !== false ? 'active text-white' : ''; ?>" href="../barang/">Barang</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'index.php' ? 'active text-white' : ''; ?>" href="index.php">Barang Masuk</a>
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
      <h3>Data Barang Masuk</h3>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">Tambah Barang Masuk</button>
    </div>

    <div class="table-responsive">
      <table id="tabel-barang" class="table table-bordered table-hover table-striped nowrap w-100">
        <thead class="table-dark">
          <tr>
            <th width="50">No</th>
            <th>Kategori</th>
            <th>Nama Barang</th>
            <th>Qty Masuk</th>
            <th>Tanggal Masuk</th>
            <th width="125">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no = 1;
          $result = $db->query("
            SELECT bm.id, bm.id_barang, bm.qty, bm.tanggal, b.nama_barang, b.satuan, k.nama_kategori
            FROM barang_masuk bm
            JOIN barang b ON bm.id_barang = b.id
            JOIN kategori k ON b.id_kategori = k.id
            ORDER BY bm.tanggal DESC
          ");
          while ($row = $result->fetch_assoc()):
          ?>
            <tr>
              <td><?= $no++; ?></td>
              <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
              <td><?= htmlspecialchars($row['nama_barang']); ?></td>
              <td><?= number_format($row['qty']) . ' ' . htmlspecialchars($row['satuan']); ?></td>
              <td><?= date('d-m-Y H:i:s', strtotime($row['tanggal'])); ?></td>
              <td class="text-nowrap">
                <button class="btn btn-warning btn-sm btn-edit-masuk"
                  data-id="<?= $row['id']; ?>"
                  data-id_barang="<?= $row['id_barang']; ?>"
                  data-qty="<?= $row['qty']; ?>">Edit</button>
                <button class="btn btn-danger btn-sm btn-hapus-masuk"
                  data-id="<?= $row['id']; ?>">Hapus</button>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal Tambah Barang Masuk -->
  <div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
      <form id="formTambah" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Barang Masuk</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Barang</label>
            <select class="form-select select2-barang" name="id_barang" required>
              <option value="">Pilih Barang</option>
              <?php while ($b = $barang_list->fetch_assoc()): ?>
                <option value="<?= $b['id']; ?>" data-satuan="<?= $b['satuan']; ?>">
                  <?= htmlspecialchars($b['nama_kategori']) . ' - ' . htmlspecialchars($b['nama_barang']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label>Jumlah Masuk</label>
            <div class="input-group">
              <input type="number" class="form-control" name="qty" id="qty" min="1" required>
              <span class="input-group-text" id="label-satuan">-</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Edit Barang Masuk -->
  <div class="modal fade" id="modalEditMasuk" tabindex="-1">
    <div class="modal-dialog">
      <form id="formEditMasuk" class="modal-content">
        <input type="hidden" name="edit_id" id="edit_masuk_id">
        <div class="modal-header">
          <h5 class="modal-title">Edit Barang Masuk</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Barang</label>
            <select class="form-select select2-barang" name="edit_id_barang" id="edit_id_barang" required>
              <option value="">Pilih Barang</option>
              <?php
              $barang_list_edit = $db->query("
                SELECT b.id, b.nama_barang, b.satuan, k.nama_kategori
                FROM barang b
                JOIN kategori k ON b.id_kategori = k.id
                ORDER BY k.nama_kategori ASC, b.nama_barang ASC
              ");
              while ($b = $barang_list_edit->fetch_assoc()):
              ?>
                <option value="<?= $b['id']; ?>" data-satuan="<?= $b['satuan']; ?>">
                  <?= htmlspecialchars($b['nama_kategori']) . ' - ' . htmlspecialchars($b['nama_barang']); ?>
                </option>
              <?php endwhile; ?>
            </select>

          </div>
          <div class="mb-3">
            <label>Jumlah Masuk</label>
            <div class="input-group">
              <input type="number" class="form-control" name="edit_qty" id="edit_qty" min="1" required>
              <span class="input-group-text" id="edit_label_satuan">-</span>
            </div>
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

  <!-- Select2-->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script>
    $(document).ready(function() {
      // Reset Select2 dan field saat modal Tambah ditutup
      $('#modalTambah').on('hidden.bs.modal', function() {
        const select = $(this).find('select[name="id_barang"]');
        select.val('').trigger('change'); // Reset Select2
        $('#label-satuan').text('-'); // Reset label satuan
        $('#qty').val(''); // Kosongkan jumlah
      });

      // Reset Select2 dan field saat modal Edit ditutup
      $('#modalEditMasuk').on('hidden.bs.modal', function() {
        const select = $(this).find('select[name="edit_id_barang"]');
        select.val('').trigger('change'); // Reset Select2
        $('#edit_label_satuan').text('-'); // Reset label satuan
        $('#edit_qty').val(''); // Kosongkan jumlah
      });

      $('.select2-barang').select2({
        dropdownParent: $('#modalTambah'), // untuk modal Tambah
        theme: 'default',
        width: '100%',
        placeholder: 'Pilih Barang'
      });

      $('#modalEditMasuk').on('shown.bs.modal', function() {
        $('#edit_id_barang').select2({
          dropdownParent: $('#modalEditMasuk'),
          theme: 'default',
          width: '100%',
          placeholder: 'Pilih Barang'
        });
      });
    });

    // Untuk modal Tambah
    $('select[name="id_barang"]').on('select2:select', function(e) {
      const satuan = $(this).find(':selected').data('satuan');
      $('#label-satuan').text(satuan || '-');
    });

    // Untuk modal Edit
    $('#edit_id_barang').on('select2:select', function(e) {
      const satuan = $(this).find(':selected').data('satuan');
      $('#edit_label_satuan').text(satuan || '-');
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

    // Tampilkan data ke modal edit
    document.querySelectorAll('.btn-edit-masuk').forEach(btn => {
      btn.addEventListener('click', function() {
        document.getElementById('edit_masuk_id').value = this.dataset.id;
        document.getElementById('edit_id_barang').value = this.dataset.id_barang;
        document.getElementById('edit_qty').value = this.dataset.qty;
        const selectedOption = document.querySelector('#edit_id_barang option[value="' + this.dataset.id_barang + '"]');
        document.getElementById('edit_label_satuan').textContent = selectedOption.dataset.satuan || '-';
        new bootstrap.Modal(document.getElementById('modalEditMasuk')).show();
      });
    });

    // Submit edit
    document.getElementById('formEditMasuk').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append('aksi', 'edit_masuk');

      fetch('index.php', {
        method: 'POST',
        body: formData
      }).then(res => res.json()).then(res => {
        if (res.status === 'success') {
          Swal.fire('Berhasil', 'Data diperbarui', 'success').then(() => location.reload());
        } else {
          Swal.fire('Gagal', res.message, 'error');
        }
      });
    });

    // Hapus barang masuk
    document.querySelectorAll('.btn-hapus-masuk').forEach(btn => {
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
            formData.append('aksi', 'hapus_masuk');
            formData.append('id', id);

            fetch('index.php', {
              method: 'POST',
              body: formData
            }).then(res => res.json()).then(res => {
              if (res.status === 'success') {
                Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success')
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