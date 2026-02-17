<?php
include __DIR__ . "/../config/db.php";

if (isset($_GET["id"])) {
    $stmt = $pdo->prepare("DELETE FROM animales WHERE id = :id");
    $stmt->execute(["id" => $_GET["id"]]);
}

header("Location: ../index.php");
exit;
