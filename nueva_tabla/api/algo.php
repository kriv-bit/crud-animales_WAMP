<?php
// api/algo.php
// Este archivo funciona como una API (backend) que responde en JSON.
// Soporta acciones: listar | obtener | insertar | editar | eliminar

// Activa modo estricto de tipos: PHP intentará ser más estricto con tipos (mejor para evitar errores).
declare(strict_types=1);

// Indica que TODAS las respuestas de este archivo serán JSON con UTF-8.
header('Content-Type: application/json; charset=utf-8');

// Importa la función db() y configuración de conexión a la base de datos (PDO).
require_once __DIR__ . '/../config/db.php';

/**
 * Devuelve una respuesta estándar JSON y termina la ejecución del script.
 *
 * @param bool $ok      true si todo salió bien, false si hubo error
 * @param string $message Mensaje para el frontend (éxito o error)
 * @param mixed $data   Datos a devolver (array/objeto/id/etc.)
 * @param int $code     Código HTTP (200 OK, 400 error cliente, 404 no encontrado, 500 servidor)
 */
function json_response(bool $ok, string $message = '', $data = null, int $code = 200): void
{
    // Setea el código HTTP de la respuesta
    http_response_code($code);

    // Imprime un JSON con estructura fija: { ok, message, data }
    echo json_encode([
        'ok' => $ok,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE); // Evita que caracteres como ñ/tildes se escapen raro (\u00f1)

    // Corta la ejecución: muy importante para que no se siga ejecutando más código.
    exit;
}

/**
 * Lee el body de la petición cuando viene como JSON (por ejemplo desde fetch()).
 * Si el request NO es JSON, devuelve [].
 *
 * @return array Datos decodificados del JSON o array vacío.
 */
function read_json_body(): array
{
    // Obtiene el Content-Type del request, según el servidor.
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

    // Si es application/json, leemos el body crudo
    if (stripos($contentType, 'application/json') !== false) {
        // php://input contiene el body completo de la petición HTTP
        $raw = file_get_contents('php://input');

        // Decodifica JSON a array asociativo (true = array en vez de objeto)
        $decoded = json_decode($raw ?: '[]', true);

        // Si se pudo decodificar como array, lo devuelve; si no, devuelve []
        return is_array($decoded) ? $decoded : [];
    }

    return [];
}

/**
 * Valida fecha en formato EXACTO YYYY-MM-DD y que NO sea futura.
 *
 * @param string $date Fecha tipo "2024-05-10"
 * @return bool true si es válida y no futura
 */
function validate_date_ymd(string $date): bool
{
    // Intenta crear un DateTime con formato exacto Y-m-d
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dt) return false;

    // Asegura que el formato sea idéntico al input (evita fechas raras)
    if ($dt->format('Y-m-d') !== $date) return false;

    // Obtiene "hoy" a las 00:00 para comparar sin horas
    $today = new DateTime('today');

    // Retorna true si la fecha es hoy o antes (no futura)
    return $dt <= $today;
}

/**
 * Valida y convierte un ID a entero positivo.
 * Si el ID no es válido, responde JSON con error 400 y termina.
 *
 * @param mixed $idRaw Puede venir de GET/POST/JSON (string, int, null)
 * @return int ID entero positivo
 */
function require_id($idRaw): int
{
    // Convierte a string para validarlo fácil
    $idStr = (string)$idRaw;

    // Valida: no vacío, que sea solo dígitos, y > 0
    if ($idStr === '' || !ctype_digit($idStr) || (int)$idStr <= 0) {
        // Si es inválido, se responde JSON con error y se corta
        json_response(false, 'ID inválido', null, 400);
    }

    return (int)$idStr;
}

// Obtiene la acción desde querystring (?action=...) o desde POST.
// Ej: api/algo.php?action=listar
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    // Obtiene conexión PDO (desde config/db.php)
    $pdo = db();

    // -------------------- LISTAR --------------------
    // GET: api/algo.php?action=listar
    if ($action === 'listar') {
        // Consulta todos los animales ordenados por id desc (últimos primero)
        $stmt = $pdo->query('SELECT id, nombre, especie, fechanacimiento FROM animales ORDER BY id DESC');

        // Devuelve el listado como array
        json_response(true, 'Listado ok', $stmt->fetchAll());
    }

    // -------------------- OBTENER 1 --------------------
    // GET: api/algo.php?action=obtener&id=5
    if ($action === 'obtener') {
        // Valida el id y lo convierte a int
        $id = require_id($_GET['id'] ?? $_POST['id'] ?? null);

        // Prepared statement para evitar inyección SQL
        $stmt = $pdo->prepare('SELECT id, nombre, especie, fechanacimiento FROM animales WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $animal = $stmt->fetch();

        // Si no existe, responde 404
        if (!$animal) {
            json_response(false, 'Animal no encontrado', null, 404);
        }

        // Si existe, devuelve el registro
        json_response(true, 'Obtener ok', $animal);
    }

    // -------------------- INSERTAR --------------------
    // POST JSON: api/algo.php?action=insertar
    // body: {"nombre":"Max","especie":"Perro","fechanacimiento":"2019-05-14"}
    if ($action === 'insertar') {
        // Lee JSON del body (si no viene JSON, devuelve [])
        $body = read_json_body();

        // Toma datos desde JSON o desde $_POST (por si envían form-data)
        $nombre  = trim((string)($body['nombre'] ?? ($_POST['nombre'] ?? '')));
        $especie = trim((string)($body['especie'] ?? ($_POST['especie'] ?? '')));
        $fn      = trim((string)($body['fechanacimiento'] ?? ($_POST['fechanacimiento'] ?? '')));

        // Validaciones básicas
        if ($nombre === '' || mb_strlen($nombre) > 100) {
            json_response(false, 'Nombre requerido (máx 100)', null, 400);
        }
        if ($especie === '' || mb_strlen($especie) > 100) {
            json_response(false, 'Especie requerida (máx 100)', null, 400);
        }
        if ($fn === '' || !validate_date_ymd($fn)) {
            json_response(false, 'Fecha de nacimiento inválida (YYYY-MM-DD y no futura)', null, 400);
        }

        // Inserta en BD usando prepare para seguridad
        $stmt = $pdo->prepare('INSERT INTO animales (nombre, especie, fechanacimiento) VALUES (?, ?, ?)');
        $stmt->execute([$nombre, $especie, $fn]);

        // Obtiene el id recién insertado
        $newId = (int)$pdo->lastInsertId();

        // Devuelve el objeto insertado (incluye el id nuevo)
        json_response(true, 'Insertado ok', [
            'id' => $newId,
            'nombre' => $nombre,
            'especie' => $especie,
            'fechanacimiento' => $fn
        ]);
    }

    // -------------------- EDITAR --------------------
    // POST JSON: api/algo.php?action=editar
    // body: {"id":5,"nombre":"Nuevo","especie":"Gato","fechanacimiento":"2020-01-01"}
    if ($action === 'editar') {
        // Lee body JSON
        $body = read_json_body();

        // Valida id (puede venir por JSON o POST)
        $id = require_id($body['id'] ?? ($_POST['id'] ?? null));

        // Lee campos (JSON o POST)
        $nombre  = trim((string)($body['nombre'] ?? ($_POST['nombre'] ?? '')));
        $especie = trim((string)($body['especie'] ?? ($_POST['especie'] ?? '')));
        $fn      = trim((string)($body['fechanacimiento'] ?? ($_POST['fechanacimiento'] ?? '')));

        // Validaciones
        if ($nombre === '' || mb_strlen($nombre) > 100) {
            json_response(false, 'Nombre requerido (máx 100)', null, 400);
        }
        if ($especie === '' || mb_strlen($especie) > 100) {
            json_response(false, 'Especie requerida (máx 100)', null, 400);
        }
        if ($fn === '' || !validate_date_ymd($fn)) {
            json_response(false, 'Fecha de nacimiento inválida (YYYY-MM-DD y no futura)', null, 400);
        }

        // Verifica que el registro exista antes de actualizar
        $chk = $pdo->prepare('SELECT id FROM animales WHERE id = ? LIMIT 1');
        $chk->execute([$id]);
        if (!$chk->fetch()) {
            json_response(false, 'Animal no encontrado', null, 404);
        }

        // Actualiza el registro
        $stmt = $pdo->prepare('UPDATE animales SET nombre = ?, especie = ?, fechanacimiento = ? WHERE id = ?');
        $stmt->execute([$nombre, $especie, $fn, $id]);

        // Devuelve el objeto actualizado
        json_response(true, 'Editado ok', [
            'id' => $id,
            'nombre' => $nombre,
            'especie' => $especie,
            'fechanacimiento' => $fn
        ]);
    }

    // -------------------- ELIMINAR --------------------
    // POST JSON: api/algo.php?action=eliminar
    // body: {"id":5}
    // También soporta: GET id o POST id por compatibilidad
    if ($action === 'eliminar') {
        $body = read_json_body();

        // El id puede venir por JSON, POST o GET
        $id = require_id($body['id'] ?? ($_POST['id'] ?? ($_GET['id'] ?? null)));

        // Verifica que exista antes de eliminar
        $chk = $pdo->prepare('SELECT id FROM animales WHERE id = ? LIMIT 1');
        $chk->execute([$id]);
        if (!$chk->fetch()) {
            json_response(false, 'Animal no encontrado', null, 404);
        }

        // Elimina el registro
        $stmt = $pdo->prepare('DELETE FROM animales WHERE id = ?');
        $stmt->execute([$id]);

        // Devuelve confirmación (id eliminado)
        json_response(true, 'Eliminado ok', ['id' => $id]);
    }

    // Si action no coincide con ninguna anterior, devuelve error 400
    json_response(false, 'Acción no válida. Usa: listar | obtener | insertar | editar | eliminar', null, 400);

} catch (Throwable $e) {
    // Si ocurre cualquier error (PDOException u otro), devuelve error 500 en JSON
    json_response(false, 'Error del servidor: ' . $e->getMessage(), null, 500);
}