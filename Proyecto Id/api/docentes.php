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
    $id = $_GET['id'] ?? null;
    $con_asignaciones = isset($_GET['asignaciones']);

    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM docentes WHERE id = ?");
        $stmt->execute([$id]);
        $docente = $stmt->fetch();
        if (!$docente) {
            http_response_code(404);
            echo json_encode(['error' => 'Docente no encontrado']);
            return;
        }
        unset($docente['password_hash']);
        if ($con_asignaciones) {
            $st = $pdo->prepare("SELECT * FROM docente_asignaciones WHERE docente_id = ? ORDER BY carrera, cuatrimestre, materia_nombre");
            $st->execute([$id]);
            $docente['asignaciones'] = $st->fetchAll();
        }
        echo json_encode(['success' => true, 'docente' => $docente]);
        return;
    }

    $stmt = $pdo->query("SELECT id, usuario, nombre, apellido, email, created_at FROM docentes ORDER BY nombre, apellido");
    $docentes = $stmt->fetchAll();
    echo json_encode(['success' => true, 'docentes' => $docentes]);
}

function handlePost($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $tipo = $data['tipo'] ?? 'docente';

    if ($tipo === 'asignacion') {
        handlePostAsignacion($pdo, $data);
        return;
    }

    $usuario = trim($data['usuario'] ?? '');
    $password = $data['password'] ?? '';
    $nombre = trim($data['nombre'] ?? '');
    $apellido = trim($data['apellido'] ?? '');
    $email = trim($data['email'] ?? '');

    if (!$usuario || !$nombre) {
        http_response_code(400);
        echo json_encode(['error' => 'Usuario y nombre son requeridos']);
        return;
    }
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres']);
        return;
    }

    $check = $pdo->prepare("SELECT id FROM docentes WHERE usuario = ?");
    $check->execute([$usuario]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Ya existe un docente con ese usuario']);
        return;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO docentes (usuario, password_hash, nombre, apellido, email) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$usuario, $hash, $nombre, $apellido ?: null, $email ?: null]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Docente registrado']);
}

function handlePostAsignacion($pdo, $data) {
    $docente_id = $data['docente_id'] ?? null;
    $materia_nombre = trim($data['materia_nombre'] ?? '');
    $carrera = trim($data['carrera'] ?? '');
    $cuatrimestre = (int)($data['cuatrimestre'] ?? 1);
    $grupo = trim($data['grupo'] ?? '') ?: null;

    if (!$docente_id || !$materia_nombre || !$carrera) {
        http_response_code(400);
        echo json_encode(['error' => 'docente_id, materia_nombre y carrera son requeridos']);
        return;
    }
    if ($cuatrimestre < 1 || $cuatrimestre > 10) {
        http_response_code(400);
        echo json_encode(['error' => 'Cuatrimestre debe ser entre 1 y 10']);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO docente_asignaciones (docente_id, materia_nombre, carrera, cuatrimestre, grupo) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$docente_id, $materia_nombre, $carrera, $cuatrimestre, $grupo]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Asignación creada']);
}

function handlePut($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $tipo = $data['tipo'] ?? 'docente';

    if ($tipo === 'asignacion') {
        $asigId = $data['asignacion_id'] ?? null;
        if (!$asigId) { http_response_code(400); echo json_encode(['error' => 'asignacion_id requerido']); return; }
        $updates = [];
        $params = [];
        if (isset($data['materia_nombre'])) { $updates[] = 'materia_nombre = ?'; $params[] = trim($data['materia_nombre']); }
        if (isset($data['carrera'])) { $updates[] = 'carrera = ?'; $params[] = trim($data['carrera']); }
        if (isset($data['cuatrimestre'])) { $updates[] = 'cuatrimestre = ?'; $params[] = (int)$data['cuatrimestre']; }
        if (array_key_exists('grupo', $data)) { $updates[] = 'grupo = ?'; $params[] = trim($data['grupo'] ?? '') ?: null; }
        if (empty($updates)) { http_response_code(400); echo json_encode(['error' => 'Nada que actualizar']); return; }
        $params[] = $asigId;
        $sql = "UPDATE docente_asignaciones SET " . implode(', ', $updates) . " WHERE id = ?";
        $pdo->prepare($sql)->execute($params);
        echo json_encode(['success' => true, 'message' => 'Asignación actualizada']);
        return;
    }

    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); return; }
    $updates = [];
    $params = [];
    if (isset($data['nombre'])) { $updates[] = 'nombre = ?'; $params[] = trim($data['nombre']); }
    if (isset($data['apellido'])) { $updates[] = 'apellido = ?'; $params[] = trim($data['apellido'] ?? ''); }
    if (isset($data['email'])) { $updates[] = 'email = ?'; $params[] = trim($data['email'] ?? ''); }
    if (!empty($data['password'])) {
        $updates[] = 'password_hash = ?';
        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    if (empty($updates)) { http_response_code(400); echo json_encode(['error' => 'Nada que actualizar']); return; }
    $params[] = $id;
    $sql = "UPDATE docentes SET " . implode(', ', $updates) . " WHERE id = ?";
    $pdo->prepare($sql)->execute($params);
    echo json_encode(['success' => true, 'message' => 'Docente actualizado']);
}

function handleDelete($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? ($_GET['id'] ?? null);
    $tipo = $data['tipo'] ?? 'docente';

    if ($tipo === 'asignacion') {
        $asigId = $data['asignacion_id'] ?? $id;
        if (!$asigId) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); return; }
        $pdo->prepare("DELETE FROM docente_asignaciones WHERE id = ?")->execute([$asigId]);
        echo json_encode(['success' => true, 'message' => 'Asignación eliminada']);
        return;
    }

    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); return; }
    $pdo->prepare("DELETE FROM docentes WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Docente eliminado']);
}
