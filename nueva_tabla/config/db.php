<?php
// -------------------------- SE USA EN WAMP ASI
//const DB_HOST = '127.0.0.1'; 

const DB_HOST = 'db';
const DB_NAME = "MCEJ1_BD"; //
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

    // Opciones de configuración para la conexión PDO
    $options = [

        // Hace que PDO lance una excepción (error tipo PDOException)
        // cuando ocurre un error en una consulta SQL.
        // Es mejor que solo devolver "false" porque podemos capturar
        // el error con try/catch y manejarlo correctamente.
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

        // Define el modo de obtención (fetch) por defecto.
        // FETCH_ASSOC hace que los resultados se devuelvan
        // como un array asociativo:
        // ['id' => 1, 'nombre' => 'Max']
        // En vez de incluir también índices numéricos duplicados.
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // Desactiva la emulación de prepared statements.
        // Esto obliga a usar consultas preparadas reales del servidor MySQL,
        // lo que mejora la seguridad contra inyección SQL
        // y asegura mejor manejo de tipos de datos.
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo;
}