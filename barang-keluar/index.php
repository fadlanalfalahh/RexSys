<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../auth/login.php");
  exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// Tambah Barang Keluar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'keluar') {
  $id_barang = intval($_POST['id_barang']);
  $qty = intval($_POST['qty']);

  $result = $db->query("SELECT stok FROM barang WHERE id = $id_barang");
  $barang = $result->fetch_assoc();

  if (!$barang) {
    echo json_encode(['status' => 'error', 'message' => 'Barang tidak ditemukan.']);
  } elseif ($barang['stok'] < $qty) {
    echo json_encode(['status' => 'error', 'message' => 'Stok tidak mencukupi.']);
  } else {
    // Insert ke barang_keluar (tanggal otomatis)
    $stmt1 = $db->prepare("INSERT INTO barang_keluar (id_barang, qty) VALUES (?, ?)");
    $stmt1->bind_param("ii", $id_barang, $qty);

    // Kurangi stok
    $stmt2 = $db->prepare("UPDATE barang SET stok = stok - ? WHERE id = ?");
    $stmt2->bind_param("ii", $qty, $id_barang);

    if ($stmt1->execute() && $stmt2->execute()) {
      echo json_encode(['status' => 'success']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data.']);
    }
  }
  exit;
}

// Edit Barang Keluar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'edit_keluar') {
  $id = intval($_POST['edit_id']);
  $id_barang = intval($_POST['edit_id_barang']);
  $qty = intval($_POST['edit_qty']);

  $stmt = $db->prepare("UPDATE barang_keluar SET id_barang = ?, qty = ? WHERE id = ?");
  $stmt->bind_param("iii", $id_barang, $qty, $id);

  if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengedit data.']);
  }
  exit;
}

// Hapus Barang Keluar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'hapus_keluar') {
  $id = intval($_POST['id']);

  // Ambil qty dan id_barang dulu
  $result = $db->query("SELECT id_barang, qty FROM barang_keluar WHERE id = $id");
  $data = $result->fetch_assoc();

  if ($data) {
    $id_barang = $data['id_barang'];
    $qty = $data['qty'];

    // Tambah kembali stok
    $stmt1 = $db->prepare("UPDATE barang SET stok = stok + ? WHERE id = ?");
    $stmt1->bind_param("ii", $qty, $id_barang);

    // Lalu hapus data
    $stmt2 = $db->prepare("DELETE FROM barang_keluar WHERE id = ?");
    $stmt2->bind_param("i", $id);

    if ($stmt1->execute() && $stmt2->execute()) {
      echo json_encode(['status' => 'success']);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Gagal hapus atau update stok.']);
    }
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
  }
  exit;
}

// Ambil Data Barang
$barang_list = $db->query("
  SELECT b.id, b.nama_barang, b.satuan, k.nama_kategori
  FROM barang b
  JOIN kategori k ON b.id_kategori = k.id
  ORDER BY k.nama_kategori ASC, b.nama_barang ASC
");
$riwayat = $db->query("
  SELECT bk.*, b.nama_barang, b.satuan, k.nama_kategori 
  FROM barang_keluar bk 
  JOIN barang b ON bk.id_barang = b.id 
  JOIN kategori k ON b.id_kategori = k.id 
  ORDER BY bk.tanggal DESC
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
            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/barang-masuk/') !== false ? 'active text-white' : ''; ?>" href="../barang-masuk/">Barang Masuk</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $current_page === 'index.php' ? 'active text-white' : ''; ?>" href="index.php">Barang Keluar</a>
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
      <h3>Data Barang Keluar</h3>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalKeluar">Tambah Barang Keluar</button>
    </div>

    <table id="tabel-barang-keluar" class="table table-bordered table-hover table-striped nowrap w-100">
      <thead class="table-dark">
        <tr>
          <th width="50">No</th>
          <th>Kategori</th>
          <th>Nama Barang</th>
          <th>Qty Keluar</th>
          <th>Tanggal Keluar</th>
          <th width="125">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1;
        while ($row = $riwayat->fetch_assoc()): ?>
          <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
            <td><?= htmlspecialchars($row['nama_barang']); ?></td>
            <td><?= number_format($row['qty']) . ' ' . htmlspecialchars($row['satuan']); ?></td>
            <td><?= date('d-m-Y H:i:s', strtotime($row['tanggal'])); ?></td>
            <td class="text-nowrap">
              <button class="btn btn-warning btn-sm btn-edit-keluar"
                data-id="<?= $row['id']; ?>"
                data-id_barang="<?= $row['id_barang']; ?>"
                data-qty="<?= $row['qty']; ?>">Edit</button>
              <button class="btn btn-danger btn-sm btn-hapus-keluar"
                data-id="<?= $row['id']; ?>">Hapus</button>
            </td>

          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Modal Tambah Barang Keluar -->
  <div class="modal fade" id="modalKeluar" tabindex="-1">
    <div class="modal-dialog">
      <form id="formKeluar" class="modal-content">
        <input type="hidden" name="aksi" value="keluar">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Barang Keluar</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Pilih Barang</label>
            <select class="form-select select2-keluar" name="id_barang" id="keluar_id_barang" required>
              <option value="">Pilih Barang</option>
              <?php $barang_list->data_seek(0);
              while ($b = $barang_list->fetch_assoc()): ?>
                <option value="<?= $b['id']; ?>" data-satuan="<?= $b['satuan']; ?>">
                  <?= htmlspecialchars($b['nama_kategori']) . ' - ' . htmlspecialchars($b['nama_barang']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label>Jumlah Keluar</label>
            <div class="input-group">
              <input type="number" class="form-control" name="qty" id="keluar_qty" required min="1">
              <span class="input-group-text" id="keluar_label_satuan">-</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Edit Barang Keluar -->
  <div class="modal fade" id="modalEditKeluar" tabindex="-1">
    <div class="modal-dialog">
      <form id="formEditKeluar" class="modal-content">
        <input type="hidden" name="edit_id" id="edit_keluar_id">
        <div class="modal-header">
          <h5 class="modal-title">Edit Barang Keluar</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Pilih Barang</label>
            <select class="form-select select2-keluar" name="edit_id_barang" id="edit_keluar_id_barang" required>
              <option value="">Pilih Barang</option>
              <?php
              $barang_list->data_seek(0);
              while ($b = $barang_list->fetch_assoc()):
              ?>
                <option value="<?= $b['id']; ?>" data-satuan="<?= $b['satuan']; ?>">
                  <?= htmlspecialchars($b['nama_kategori']) . ' - ' . htmlspecialchars($b['nama_barang']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label>Jumlah Keluar</label>
            <div class="input-group">
              <input type="number" class="form-control" name="edit_qty" id="edit_keluar_qty" required min="1">
              <span class="input-group-text" id="edit_keluar_label_satuan">-</span>
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
  <!-- Select2 -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script>
    $(document).ready(function() {
      // Inisialisasi Select2 di modal Tambah
      $('#modalKeluar').on('shown.bs.modal', function() {
        $('#keluar_id_barang').select2({
          dropdownParent: $('#modalKeluar'),
          width: '100%',
          placeholder: 'Pilih Barang'
        });
      });

      // Reset saat modal Tambah ditutup
      $('#modalKeluar').on('hidden.bs.modal', function() {
        $('#keluar_id_barang').val('').trigger('change');
        $('#keluar_label_satuan').text('-');
        $('#keluar_qty').val('');
      });

      // Inisialisasi Select2 di modal Edit
      $('#modalEditKeluar').on('shown.bs.modal', function() {
        $('#edit_keluar_id_barang').select2({
          dropdownParent: $('#modalEditKeluar'),
          width: '100%',
          placeholder: 'Pilih Barang'
        });
      });

      // Reset saat modal Edit ditutup
      $('#modalEditKeluar').on('hidden.bs.modal', function() {
        $('#edit_keluar_id_barang').val('').trigger('change');
        $('#edit_keluar_label_satuan').text('-');
        $('#edit_keluar_qty').val('');
      });

      // Ganti satuan otomatis setelah pilih barang
      $('#keluar_id_barang').on('select2:select', function() {
        const satuan = $(this).find(':selected').data('satuan');
        $('#keluar_label_satuan').text(satuan || '-');
      });

      $('#edit_keluar_id_barang').on('select2:select', function() {
        const satuan = $(this).find(':selected').data('satuan');
        $('#edit_keluar_label_satuan').text(satuan || '-');
      });
    });

    document.getElementById('formKeluar').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append('aksi', 'keluar');

      fetch('index.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(res => {
          if (res.status === 'success') {
            Swal.fire('Berhasil', 'Barang keluar disimpan', 'success').then(() => location.reload());
          } else {
            Swal.fire('Gagal', res.message, 'error');
          }
        });
    });

    // Buka Modal Edit
    document.querySelectorAll('.btn-edit-keluar').forEach(btn => {
      btn.addEventListener('click', function() {
        document.getElementById('edit_keluar_id').value = this.dataset.id;
        document.getElementById('edit_keluar_id_barang').value = this.dataset.id_barang;
        document.getElementById('edit_keluar_qty').value = this.dataset.qty;

        const selectedOption = document.querySelector(`#edit_keluar_id_barang option[value="${this.dataset.id_barang}"]`);
        document.getElementById('edit_keluar_label_satuan').textContent = selectedOption?.dataset.satuan || '-';

        new bootstrap.Modal(document.getElementById('modalEditKeluar')).show();
      });
    });

    // Ganti satuan otomatis saat pilih barang (edit)
    document.getElementById('edit_keluar_id_barang').addEventListener('change', function() {
      const satuan = this.options[this.selectedIndex].dataset.satuan;
      document.getElementById('edit_keluar_label_satuan').textContent = satuan || '-';
    });

    // Submit Edit
    document.getElementById('formEditKeluar').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append('aksi', 'edit_keluar');

      fetch('index.php', {
        method: 'POST',
        body: formData
      }).then(res => res.json()).then(res => {
        if (res.status === 'success') {
          Swal.fire('Berhasil', 'Data berhasil diperbarui', 'success').then(() => location.reload());
        } else {
          Swal.fire('Gagal', res.message, 'error');
        }
      });
    });

    // Hapus Barang Keluar
    document.querySelectorAll('.btn-hapus-keluar').forEach(btn => {
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
            formData.append('aksi', 'hapus_keluar');
            formData.append('id', id);

            fetch('index.php', {
              method: 'POST',
              body: formData
            }).then(res => res.json()).then(res => {
              if (res.status === 'success') {
                Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success').then(() => location.reload());
              } else {
                Swal.fire('Gagal', res.message, 'error');
              }
            });
          }
        });
      });
    });

    $(document).ready(function() {
      $('#tabel-barang-keluar').DataTable({
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