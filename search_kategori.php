<?php
include 'config/koneksi.php';

$q = isset($_GET['q']) ? $db->real_escape_string($_GET['q']) : '';

$sql = "SELECT id, nama_kategori FROM kategori 
        WHERE nama_kategori LIKE '%$q%' 
        ORDER BY nama_kategori ASC 
        LIMIT 20";

$result = $db->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
  $data[] = [
    'id' => $row['id'],
    'text' => $row['nama_kategori']
  ];
}

header('Content-Type: application/json');
echo json_encode($data);
