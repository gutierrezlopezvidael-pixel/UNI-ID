<?php
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

$pdo = getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($pdo);
        break;
    case 'POST':
        handlePost($pdo);
        break;
    case 'PUT':
        handlePut($pdo);
        break;
    case 'DELETE':
        handleDelete($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}

function handleGet($pdo) {
    $carrera_id = $_GET['carrera_id'] ?? null;
    $cuatrimestre = isset($_GET['cuatrimestre']) ? (int)$_GET['cuatrimestre'] : null;
    if ($carrera_id && $cuatrimestre !== null && $cuatrimestre >= 1 && $cuatrimestre <= 10) {
        $stmt = $pdo->prepare("SELECT m.*, c.nombre as carrera_nombre FROM materias m JOIN carreras c ON m.carrera_id = c.id WHERE m.carrera_id = ? AND m.cuatrimestre = ? ORDER BY m.nombre");
        $stmt->execute([$carrera_id, $cuatrimestre]);
        $materias = $stmt->fetchAll();
        echo json_encode(['success' => true, 'materias' => $materias]);
        return;
    }
    if ($carrera_id) {
        $stmt = $pdo->prepare("SELECT m.*, c.nombre as carrera_nombre FROM materias m JOIN carreras c ON m.carrera_id = c.id WHERE m.carrera_id = ? ORDER BY m.cuatrimestre, m.nombre");
        $stmt->execute([$carrera_id]);
        $materias = $stmt->fetchAll();
        echo json_encode(['success' => true, 'materias' => $materias]);
        return;
    }
    $stmt = $pdo->query("SELECT m.*, c.nombre as carrera_nombre FROM materias m JOIN carreras c ON m.carrera_id = c.id ORDER BY c.nombre, m.cuatrimestre, m.nombre");
    $materias = $stmt->fetchAll();
    echo json_encode(['success' => true, 'materias' => $materias]);
}

function handlePost($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $nombre = trim($data['nombre'] ?? '');
    $carrera_id = (int)($data['carrera_id'] ?? 0);
    $cuatrimestre = isset($data['cuatrimestre']) ? (int)$data['cuatrimestre'] : 1;
    if ($cuatrimestre < 1 || $cuatrimestre > 10) $cuatrimestre = 1;
    if (!$nombre || !$carrera_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Nombre y carrera son requeridos']);
        return;
    }
    $check = $pdo->prepare("SELECT id FROM materias WHERE nombre = ? AND carrera_id = ? AND cuatrimestre = ?");
    $check->execute([$nombre, $carrera_id, $cuatrimestre]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Ya existe esa materia en este cuatrimestre. Usa un nombre distinto (ej. Inglés I, Inglés II).']);
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO materias (nombre, carrera_id, cuatrimestre) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $carrera_id, $cuatrimestre]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Materia creada']);
}

function handlePut($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $nombre = isset($data['nombre']) ? trim($data['nombre']) : null;
    $carrera_id = isset($data['carrera_id']) ? (int)$data['carrera_id'] : null;
    $cuatrimestre = isset($data['cuatrimestre']) ? (int)$data['cuatrimestre'] : null;
    if ($cuatrimestre !== null && ($cuatrimestre < 1 || $cuatrimestre > 10)) $cuatrimestre = 1;
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); return; }
    $updates = [];
    $params = [];
    if ($nombre !== null) { $updates[] = 'nombre = ?'; $params[] = $nombre; }
    if ($carrera_id !== null) { $updates[] = 'carrera_id = ?'; $params[] = $carrera_id; }
    if ($cuatrimestre !== null) { $updates[] = 'cuatrimestre = ?'; $params[] = $cuatrimestre; }
    if (empty($updates)) { http_response_code(400); echo json_encode(['error' => 'Nada que actualizar']); return; }
    $cur = $pdo->prepare("SELECT nombre, carrera_id, cuatrimestre FROM materias WHERE id = ?");
    $cur->execute([$id]);
    $row = $cur->fetch();
    if (!$row) { http_response_code(404); echo json_encode(['error' => 'Materia no encontrada']); return; }
    $nombreFinal = $nombre !== null ? $nombre : $row['nombre'];
    $carreraFinal = $carrera_id !== null ? $carrera_id : $row['carrera_id'];
    $cuatFinal = $cuatrimestre !== null ? $cuatrimestre : $row['cuatrimestre'];
    $dup = $pdo->prepare("SELECT id FROM materias WHERE nombre = ? AND carrera_id = ? AND cuatrimestre = ? AND id != ?");
    $dup->execute([$nombreFinal, $carreraFinal, $cuatFinal, $id]);
    if ($dup->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Ya existe esa materia en este cuatrimestre. Usa un nombre distinto.']);
        return;
    }
    $params[] = $id;
    $pdo->prepare("UPDATE materias SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);
    echo json_encode(['success' => true, 'message' => 'Materia actualizada']);
}

function handleDelete($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? ($_GET['id'] ?? null);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); return; }
    $pdo->prepare("DELETE FROM materias WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Materia eliminada']);
}
