<?php
// config/db.php
// Configuración típica de XAMPP: usuario root sin contraseña.
// Cambia DB_NAME por el nombre de TU base de datos.

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = "MCEJ1_BD"; // <-- CAMBIA ESTO
const DB_USER = "kevin";;
const DB_PASS = '12345';
const DB_CHARSET = 'utf8mb4';



function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo;
}