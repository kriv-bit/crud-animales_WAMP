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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo = db();

    if ($action === 'listar') {
        $stmt = $pdo->query('SELECT id, nombre, especie, fechanacimiento FROM animales ORDER BY id DESC');
        json_response(true, 'Listado ok', $stmt->fetchAll());
    }

    if ($action === 'obtener') {
        $id = require_id($_GET['id'] ?? $_POST['id'] ?? null);

        $stmt = $pdo->prepare('SELECT id, nombre, especie, fechanacimiento FROM animales WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $animal = $stmt->fetch();

        if (!$animal) {
            json_response(false, 'Animal no encontrado', null, 404);
        }

        json_response(true, 'Obtener ok', $animal);
    }

    if ($action === 'insertar') {
        $body = read_json_body();

        $nombre  = trim((string)($body['nombre'] ?? ($_POST['nombre'] ?? '')));
        $especie = trim((string)($body['especie'] ?? ($_POST['especie'] ?? '')));
        $fn      = trim((string)($body['fechanacimiento'] ?? ($_POST['fechanacimiento'] ?? '')));

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            json_response(false, 'Nombre requerido (máx 100)', null, 400);
        }
        if ($especie === '' || mb_strlen($especie) > 100) {
            json_response(false, 'Especie requerida (máx 100)', null, 400);
        }
        if ($fn === '' || !validate_date_ymd($fn)) {
            json_response(false, 'Fecha de nacimiento inválida (YYYY-MM-DD y no futura)', null, 400);
        }

        $stmt = $pdo->prepare('INSERT INTO animales (nombre, especie, fechanacimiento) VALUES (?, ?, ?)');
        $stmt->execute([$nombre, $especie, $fn]);

        $newId = (int)$pdo->lastInsertId();

        json_response(true, 'Insertado ok', [
            'id' => $newId,
            'nombre' => $nombre,
            'especie' => $especie,
            'fechanacimiento' => $fn
        ]);
    }

    if ($action === 'editar') {
        $body = read_json_body();

        $id = require_id($body['id'] ?? ($_POST['id'] ?? null));

        $nombre  = trim((string)($body['nombre'] ?? ($_POST['nombre'] ?? '')));
        $especie = trim((string)($body['especie'] ?? ($_POST['especie'] ?? '')));
        $fn      = trim((string)($body['fechanacimiento'] ?? ($_POST['fechanacimiento'] ?? '')));

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            json_response(false, 'Nombre requerido (máx 100)', null, 400);
        }
        if ($especie === '' || mb_strlen($especie) > 100) {
            json_response(false, 'Especie requerida (máx 100)', null, 400);
        }
        if ($fn === '' || !validate_date_ymd($fn)) {
            json_response(false, 'Fecha de nacimiento inválida (YYYY-MM-DD y no futura)', null, 400);
        }

        // verifica existe
        $chk = $pdo->prepare('SELECT id FROM animales WHERE id = ? LIMIT 1');
        $chk->execute([$id]);
        if (!$chk->fetch()) {
            json_response(false, 'Animal no encontrado', null, 404);
        }

        $stmt = $pdo->prepare('UPDATE animales SET nombre = ?, especie = ?, fechanacimiento = ? WHERE id = ?');
        $stmt->execute([$nombre, $especie, $fn, $id]);

        json_response(true, 'Editado ok', [
            'id' => $id,
            'nombre' => $nombre,
            'especie' => $especie,
            'fechanacimiento' => $fn
        ]);
    }

    if ($action === 'eliminar') {
        $body = read_json_body();
        $id = require_id($body['id'] ?? ($_POST['id'] ?? ($_GET['id'] ?? null)));

        // verifica existe
        $chk = $pdo->prepare('SELECT id FROM animales WHERE id = ? LIMIT 1');
        $chk->execute([$id]);
        if (!$chk->fetch()) {
            json_response(false, 'Animal no encontrado', null, 404);
        }

        $stmt = $pdo->prepare('DELETE FROM animales WHERE id = ?');
        $stmt->execute([$id]);

        json_response(true, 'Eliminado ok', ['id' => $id]);
    }

    json_response(false, 'Acción no válida. Usa: listar | obtener | insertar | editar | eliminar', null, 400);

} catch (Throwable $e) {
    json_response(false, 'Error del servidor: ' . $e->getMessage(), null, 500);
}