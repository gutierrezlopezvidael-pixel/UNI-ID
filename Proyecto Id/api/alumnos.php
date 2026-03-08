<?php
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

$pdo = getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET': handleGet($pdo); break;
    case 'POST': handlePost($pdo); break;
    case 'PUT': handlePut($pdo); break;
    case 'DELETE': handleDelete($pdo); break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}

function hidePassword(&$a) {
    if (isset($a['password_hash'])) unset($a['password_hash']);
}

function handleGet($pdo) {
    $id = $_GET['id'] ?? null;
    $matricula = $_GET['matricula'] ?? null;
    $buscar = $_GET['buscar'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM alumnos WHERE id = ?");
        $stmt->execute([$id]);
        $a = $stmt->fetch();
        if ($a) { hidePassword($a); echo json_encode(['success' => true, 'alumno' => $a]); }
        else { http_response_code(404); echo json_encode(['error' => 'Alumno no encontrado']); }
    } elseif ($matricula) {
        $stmt = $pdo->prepare("SELECT * FROM alumnos WHERE matricula = ?");
        $stmt->execute([$matricula]);
        $a = $stmt->fetch();
        if ($a) { hidePassword($a); echo json_encode(['success' => true, 'alumno' => $a]); }
        else { http_response_code(404); echo json_encode(['error' => 'Alumno no encontrado']); }
    } else {
        $query = "SELECT id, matricula, nombre, apellido, email, telefono, carrera, semestre, grupo, foto, estado, fecha_registro, updated_at FROM alumnos";
        $params = [];
        if ($buscar) {
            $query .= " WHERE nombre LIKE ? OR apellido LIKE ? OR matricula LIKE ? OR email LIKE ? OR carrera LIKE ?";
            $like = '%' . trim($buscar) . '%';
            $params = array_fill(0, 5, $like);
        }
        $query .= " ORDER BY fecha_registro DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $alumnos = $stmt->fetchAll();
        echo json_encode(['success' => true, 'alumnos' => $alumnos, 'total' => count($alumnos)]);
    }
}

function handlePost($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $required = ['matricula', 'nombre', 'apellido', 'email', 'carrera', 'password'];
    foreach ($required as $f) {
        if (empty(trim($data[$f] ?? ''))) {
            http_response_code(400);
            echo json_encode(['error' => "El campo $f es requerido."]);
            return;
        }
    }
    $matricula = trim($data['matricula']);
    $email = trim($data['email']);
    $password = $data['password'];

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres.']);
        return;
    }

    $check = $pdo->prepare("SELECT id FROM alumnos WHERE matricula = ? OR email = ?");
    $check->execute([$matricula, $email]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Ya existe un alumno con esta matrícula o email.']);
        return;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO alumnos (matricula, nombre, apellido, email, password_hash, telefono, carrera, semestre, grupo, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $matricula,
        trim($data['nombre']),
        trim($data['apellido']),
        $email,
        $hash,
        trim($data['telefono'] ?? ''),
        trim($data['carrera']),
        intval($data['semestre'] ?? 1),
        trim($data['grupo'] ?? ''),
        $data['estado'] ?? 'activo'
    ]);
    echo json_encode(['success' => true, 'message' => 'Alumno registrado.', 'id' => $pdo->lastInsertId(), 'matricula' => $matricula]);
}

function handlePut($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); return; }

    $fields = ['nombre', 'apellido', 'email', 'telefono', 'carrera', 'semestre', 'grupo', 'estado', 'foto'];
    $updates = [];
    $params = [];
    foreach ($fields as $f) {
        if (array_key_exists($f, $data)) {
            $updates[] = "$f = ?";
            $params[] = $f === 'semestre' ? (int)$data[$f] : $data[$f];
        }
    }
    if (!empty($data['password'])) {
        $updates[] = 'password_hash = ?';
        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    if (empty($updates)) { http_response_code(400); echo json_encode(['error' => 'Nada que actualizar']); return; }
    $params[] = $id;
    $pdo->prepare("UPDATE alumnos SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);
    echo json_encode(['success' => true, 'message' => 'Alumno actualizado']);
}

function handleDelete($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? $_GET['id'] ?? null;
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); return; }
    $stmt = $pdo->prepare("SELECT ruta FROM documentos WHERE alumno_id = ?");
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $d) {
        $path = __DIR__ . '/../' . $d['ruta'];
        if (file_exists($path)) unlink($path);
    }
    $stmt = $pdo->prepare("DELETE FROM alumnos WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount()) echo json_encode(['success' => true, 'message' => 'Alumno eliminado']);
else { http_response_code(404); echo json_encode(['error' => 'No encontrado']); }
}
