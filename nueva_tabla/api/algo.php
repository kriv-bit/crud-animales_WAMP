<?php
// api/algo.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

function json_response(bool $ok, string $message = '', $data = null, int $code = 200): void
{
    http_response_code($code);
    echo json_encode([
        'ok' => $ok,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function read_json_body(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '[]', true);
        return is_array($decoded) ? $decoded : [];
    }
    return [];
}

function validate_date_ymd(string $date): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dt) return false;
    if ($dt->format('Y-m-d') !== $date) return false;

    $today = new DateTime('today');
    return $dt <= $today; // no futura
}

function require_id($idRaw): int
{
    $idStr = (string)$idRaw;
    if ($idStr === '' || !ctype_digit($idStr) || (int)$idStr <= 0) {
        json_response(false, 'ID inválido', null, 400);
    }
    return (int)$idStr;
}

function require_especie_id(PDO $pdo, $raw): int
{
    $id = require_id($raw);

    // valida que exista en especies
    $chk = $pdo->prepare('SELECT id FROM especies WHERE id = ? LIMIT 1');
    $chk->execute([$id]);
    if (!$chk->fetch()) {
        json_response(false, 'Especie no encontrada', null, 400);
    }

    return $id;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo = db();

    // -------------------- (EXTRA) LISTAR ESPECIES --------------------
    // GET: api/algo.php?action=listar_especies
    if ($action === 'listar_especies') {
        $stmt = $pdo->query('SELECT id, nombre, descripcion FROM especies ORDER BY nombre ASC');
        json_response(true, 'Listado especies ok', $stmt->fetchAll());
    }

    // -------------------- LISTAR ANIMALES --------------------
    // GET: api/algo.php?action=listar
    if ($action === 'listar') {
        $sql = '
            SELECT 
                a.id,
                a.nombre,
                a.fechanacimiento,
                a.especie_id,
                e.nombre AS especie
            FROM animales a
            INNER JOIN especies e ON e.id = a.especie_id
            ORDER BY a.id DESC
        ';
        $stmt = $pdo->query($sql);
        json_response(true, 'Listado ok', $stmt->fetchAll());
    }

    // -------------------- OBTENER 1 ANIMAL --------------------
    // GET: api/algo.php?action=obtener&id=5
    if ($action === 'obtener') {
        $id = require_id($_GET['id'] ?? $_POST['id'] ?? null);

        $sql = '
            SELECT 
                a.id,
                a.nombre,
                a.fechanacimiento,
                a.especie_id,
                e.nombre AS especie
            FROM animales a
            INNER JOIN especies e ON e.id = a.especie_id
            WHERE a.id = ?
            LIMIT 1
        ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $animal = $stmt->fetch();

        if (!$animal) {
            json_response(false, 'Animal no encontrado', null, 404);
        }

        json_response(true, 'Obtener ok', $animal);
    }

    // -------------------- INSERTAR ANIMAL --------------------
    // POST JSON: api/algo.php?action=insertar
    // body: {"nombre":"Max","especie_id":1,"fechanacimiento":"2019-05-14"}
    if ($action === 'insertar') {
        $body = read_json_body();

        $nombre = trim((string)($body['nombre'] ?? ($_POST['nombre'] ?? '')));
        $fn     = trim((string)($body['fechanacimiento'] ?? ($_POST['fechanacimiento'] ?? '')));

        // OJO: ahora es especie_id (no "especie" texto)
        $especieIdRaw = $body['especie_id'] ?? ($_POST['especie_id'] ?? null);

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            json_response(false, 'Nombre requerido (máx 100)', null, 400);
        }
        if ($fn !== '' && !validate_date_ymd($fn)) {
            json_response(false, 'Fecha inválida (YYYY-MM-DD y no futura)', null, 400);
        }

        $especie_id = require_especie_id($pdo, $especieIdRaw);

        $stmt = $pdo->prepare('INSERT INTO animales (nombre, especie_id, fechanacimiento) VALUES (?, ?, ?)');
        $stmt->execute([$nombre, $especie_id, $fn !== '' ? $fn : null]);

        $newId = (int)$pdo->lastInsertId();

        // devuelve ya con nombre de especie (JOIN)
        $stmt2 = $pdo->prepare('
            SELECT a.id, a.nombre, a.fechanacimiento, a.especie_id, e.nombre AS especie
            FROM animales a
            INNER JOIN especies e ON e.id = a.especie_id
            WHERE a.id = ?
            LIMIT 1
        ');
        $stmt2->execute([$newId]);
        $inserted = $stmt2->fetch();

        json_response(true, 'Insertado ok', $inserted);
    }

    // -------------------- EDITAR ANIMAL --------------------
    // POST JSON: api/algo.php?action=editar
    // body: {"id":5,"nombre":"Nuevo","especie_id":2,"fechanacimiento":"2020-01-01"}
    if ($action === 'editar') {
        $body = read_json_body();

        $id = require_id($body['id'] ?? ($_POST['id'] ?? null));

        $nombre = trim((string)($body['nombre'] ?? ($_POST['nombre'] ?? '')));
        $fn     = trim((string)($body['fechanacimiento'] ?? ($_POST['fechanacimiento'] ?? '')));
        $especieIdRaw = $body['especie_id'] ?? ($_POST['especie_id'] ?? null);

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            json_response(false, 'Nombre requerido (máx 100)', null, 400);
        }
        if ($fn !== '' && !validate_date_ymd($fn)) {
            json_response(false, 'Fecha inválida (YYYY-MM-DD y no futura)', null, 400);
        }

        $especie_id = require_especie_id($pdo, $especieIdRaw);

        // verifica existe el animal
        $chk = $pdo->prepare('SELECT id FROM animales WHERE id = ? LIMIT 1');
        $chk->execute([$id]);
        if (!$chk->fetch()) {
            json_response(false, 'Animal no encontrado', null, 404);
        }

        $stmt = $pdo->prepare('UPDATE animales SET nombre = ?, especie_id = ?, fechanacimiento = ? WHERE id = ?');
        $stmt->execute([$nombre, $especie_id, $fn !== '' ? $fn : null, $id]);

        // devuelve con JOIN
        $stmt2 = $pdo->prepare('
            SELECT a.id, a.nombre, a.fechanacimiento, a.especie_id, e.nombre AS especie
            FROM animales a
            INNER JOIN especies e ON e.id = a.especie_id
            WHERE a.id = ?
            LIMIT 1
        ');
        $stmt2->execute([$id]);
        $updated = $stmt2->fetch();

        json_response(true, 'Editado ok', $updated);
    }

    // -------------------- ELIMINAR ANIMAL --------------------
    // POST JSON: api/algo.php?action=eliminar
    // body: {"id":5}
    if ($action === 'eliminar') {
        $body = read_json_body();
        $id = require_id($body['id'] ?? ($_POST['id'] ?? ($_GET['id'] ?? null)));

        $chk = $pdo->prepare('SELECT id FROM animales WHERE id = ? LIMIT 1');
        $chk->execute([$id]);
        if (!$chk->fetch()) {
            json_response(false, 'Animal no encontrado', null, 404);
        }

        $stmt = $pdo->prepare('DELETE FROM animales WHERE id = ?');
        $stmt->execute([$id]);

        json_response(true, 'Eliminado ok', ['id' => $id]);
    }

        // -------------------- ESPECIES: LISTAR --------------------
    // GET: api/algo.php?action=listar_especies
    if ($action === 'listar_especies') {
        $stmt = $pdo->query('SELECT id, nombre, descripcion FROM especies ORDER BY nombre ASC');
        json_response(true, 'Listado especies ok', $stmt->fetchAll());
    }

    // -------------------- ESPECIES: OBTENER 1 --------------------
    // GET: api/algo.php?action=obtener_especie&id=1
    if ($action === 'obtener_especie') {
        $id = require_id($_GET['id'] ?? $_POST['id'] ?? null);

        $stmt = $pdo->prepare('SELECT id, nombre, descripcion FROM especies WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $esp = $stmt->fetch();

        if (!$esp) {
            json_response(false, 'Especie no encontrada', null, 404);
        }

        json_response(true, 'Obtener especie ok', $esp);
    }

    // -------------------- ESPECIES: INSERTAR --------------------
    // POST JSON: api/algo.php?action=insertar_especie
    // body: {"nombre":"Perro","descripcion":"Canino doméstico"}
    if ($action === 'insertar_especie') {
        $body = read_json_body();

        $nombre = trim((string)($body['nombre'] ?? ($_POST['nombre'] ?? '')));
        $descripcion = trim((string)($body['descripcion'] ?? ($_POST['descripcion'] ?? '')));

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            json_response(false, 'Nombre requerido (máx 100)', null, 400);
        }
        if ($descripcion !== '' && mb_strlen($descripcion) > 255) {
            json_response(false, 'Descripción demasiado larga (máx 255)', null, 400);
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO especies (nombre, descripcion) VALUES (?, ?)');
            $stmt->execute([$nombre, $descripcion !== '' ? $descripcion : null]);
        } catch (Throwable $e) {
            // En caso de UNIQUE (nombre repetido) u otra restricción
            json_response(false, 'No se pudo insertar la especie (nombre duplicado o error de datos).', null, 400);
        }

        $newId = (int)$pdo->lastInsertId();

        $stmt2 = $pdo->prepare('SELECT id, nombre, descripcion FROM especies WHERE id = ? LIMIT 1');
        $stmt2->execute([$newId]);
        $created = $stmt2->fetch();

        json_response(true, 'Insertado especie ok', $created);
    }

    // -------------------- ESPECIES: EDITAR --------------------
    // POST JSON: api/algo.php?action=editar_especie
    // body: {"id":1,"nombre":"Perro","descripcion":"..."}
    if ($action === 'editar_especie') {
        $body = read_json_body();

        $id = require_id($body['id'] ?? ($_POST['id'] ?? null));
        $nombre = trim((string)($body['nombre'] ?? ($_POST['nombre'] ?? '')));
        $descripcion = trim((string)($body['descripcion'] ?? ($_POST['descripcion'] ?? '')));

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            json_response(false, 'Nombre requerido (máx 100)', null, 400);
        }
        if ($descripcion !== '' && mb_strlen($descripcion) > 255) {
            json_response(false, 'Descripción demasiado larga (máx 255)', null, 400);
        }

        // Verifica existencia
        $chk = $pdo->prepare('SELECT id FROM especies WHERE id = ? LIMIT 1');
        $chk->execute([$id]);
        if (!$chk->fetch()) {
            json_response(false, 'Especie no encontrada', null, 404);
        }

        try {
            $stmt = $pdo->prepare('UPDATE especies SET nombre = ?, descripcion = ? WHERE id = ?');
            $stmt->execute([$nombre, $descripcion !== '' ? $descripcion : null, $id]);
        } catch (Throwable $e) {
            json_response(false, 'No se pudo editar la especie (nombre duplicado o error de datos).', null, 400);
        }

        $stmt2 = $pdo->prepare('SELECT id, nombre, descripcion FROM especies WHERE id = ? LIMIT 1');
        $stmt2->execute([$id]);
        $updated = $stmt2->fetch();

        json_response(true, 'Editado especie ok', $updated);
    }

    // -------------------- ESPECIES: ELIMINAR --------------------
    // POST JSON: api/algo.php?action=eliminar_especie
    // body: {"id":1}
    //
    // Importante: si hay animales usando esta especie (FK), el DELETE fallará
    // por la restricción ON DELETE RESTRICT. Se captura y se retorna mensaje claro.
    if ($action === 'eliminar_especie') {
        $body = read_json_body();
        $id = require_id($body['id'] ?? ($_POST['id'] ?? ($_GET['id'] ?? null)));

        $chk = $pdo->prepare('SELECT id FROM especies WHERE id = ? LIMIT 1');
        $chk->execute([$id]);
        if (!$chk->fetch()) {
            json_response(false, 'Especie no encontrada', null, 404);
        }

        try {
            $stmt = $pdo->prepare('DELETE FROM especies WHERE id = ?');
            $stmt->execute([$id]);
        } catch (Throwable $e) {
            // Lo más común aquí: no se puede borrar porque hay animales asociados.
            json_response(false, 'No se puede eliminar: hay animales asociados a esta especie.', null, 400);
        }

        json_response(true, 'Eliminado especie ok', ['id' => $id]);
    }
        json_response(false, 'Acción no válida. Usa: listar | obtener | insertar | editar | eliminar | listar_especies', null, 400);
} catch (Throwable $e) {
    json_response(false, 'Error del servidor: ' . $e->getMessage(), null, 500);
}