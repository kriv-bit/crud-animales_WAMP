<?php
include __DIR__ . "/../config/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $sql = "UPDATE animales
            SET nombre = :nombre,
                especie = :especie,
                edad = :edad
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        "id"      => $_POST["id"],
        "nombre"  => $_POST["nombre"],
        "especie" => $_POST["especie"],
        "edad"    => $_POST["edad"],
    ]);
}

header("Location: ../index.php");
exit;
