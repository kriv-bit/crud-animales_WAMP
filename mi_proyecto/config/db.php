<?php
$host = "127.0.0.1";
$db   = "MCEJ1_BD";
$user = "kevin";
$pass = "12345";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Error conexión BD: " . $e->getMessage());
}
