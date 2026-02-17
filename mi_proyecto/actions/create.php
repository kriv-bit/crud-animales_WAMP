<?php
include __DIR__ . "/../config/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $sql = "INSERT INTO animales (nombre, especie, edad)
            VALUES (:nombre, :especie, :edad)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        "nombre"  => $_POST["nombre"],
        "especie" => $_POST["especie"],
        "edad"    => $_POST["edad"],
    ]);
}

header("Location: ../index.php");
exit;
