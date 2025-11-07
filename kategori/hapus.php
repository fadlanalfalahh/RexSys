<?php
session_start();
include '../config/koneksi.php';

$id = $_GET['id'];
$stmt = $db->prepare("DELETE FROM kategori WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: index.php");
exit;
