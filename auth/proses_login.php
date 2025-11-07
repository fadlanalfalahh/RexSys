<?php
session_start();
include '../config/koneksi.php';

$username = $_POST['username'];
$password = $_POST['password'];

// Ambil data user dari database
$query = $db->prepare("SELECT * FROM users WHERE username = ?");
$query->bind_param("s", $username);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
  // Login berhasil
  $_SESSION['user_id'] = $user['id'];
  $_SESSION['username'] = $user['username'];
  header("Location: ../dashboard.php");
  exit;
} else {
  $_SESSION['error'] = "Username atau password salah.";
  header("Location: login.php");
  exit;
}