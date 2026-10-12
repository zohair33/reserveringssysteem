<?php
$host = 'localhost';
$db   = 'reserveringssysteem';
$user = 'root';
$pass = ''; // Standaard bij XAMPP is dit leeg

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (\PDOException $e) {
    die("Databaseverbinding mislukt: " . $e->getMessage());
}
?>