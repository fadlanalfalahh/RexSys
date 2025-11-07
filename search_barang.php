<?php
include 'config/koneksi.php';

$term = isset($_GET['q']) ? $db->real_escape_string($_GET['q']) : '';

$sql = "SELECT DISTINCT nama_barang FROM barang 
        WHERE nama_barang LIKE '%$term%' 
        ORDER BY nama_barang ASC 
        LIMIT 20";

$result = $db->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
  $data[] = ['nama_barang' => $row['nama_barang']];
}

header('Content-Type: application/json');
echo json_encode($data);
