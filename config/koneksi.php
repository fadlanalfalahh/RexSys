<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$pass = "EST.1106";
$database = "rexsy";

$db = new mysqli($host, $user, $pass, $database);

if ($db->connect_error) {
  die("Koneksi gagal: " . $db->connect_error);
}