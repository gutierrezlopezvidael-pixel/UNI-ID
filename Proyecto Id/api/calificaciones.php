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
    $alumno_id = $_GET['alumno_id'] ?? null;
    $carrera = $_GET['carrera'] ?? null;
    $cuatrimestre = $_GET['cuatrimestre'] ?? null;

    if ($alumno_id) {
        $stmt = $pdo->prepare("SELECT * FROM calificaciones WHERE alumno_id = ? ORDER BY cuatrimestre, materia_nombre");
        $stmt->execute([$alumno_id]);
        $calificaciones = $stmt->fetchAll();
        echo json_encode(['success' => true, 'calificaciones' => $calificaciones]);
        return;
    }

    if ($carrera !== null && $carrera !== '' && $cuatrimestre !== null && $cuatrimestre !== '') {
        $stmt = $pdo->prepare("SELECT a.id, a.matricula, a.nombre, a.apellido, a.carrera, a.semestre FROM alumnos a WHERE a.carrera = ? AND a.semestre = ? AND a.estado = 'activo' ORDER BY a.apellido, a.nombre");
        $stmt->execute([$carrera, (int)$cuatrimestre]);
        $alumnos = $stmt->fetchAll();
        foreach ($alumnos as &$a) {
            $st = $pdo->prepare("SELECT * FROM calificaciones WHERE alumno_id = ? AND cuatrimestre = ? ORDER BY materia_nombre");
            $st->execute([$a['id'], (int)$cuatrimestre]);
            $a['calificaciones'] = $st->fetchAll();
        }
        echo json_encode(['success' => true, 'alumnos' => $alumnos]);
        return;
    }

    $stmt = $pdo->query("SELECT DISTINCT carrera FROM alumnos WHERE estado = 'activo' ORDER BY carrera");
    $carreras = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['success' => true, 'carreras' => $carreras]);
}

function handlePost($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $alumno_id = $data['alumno_id'] ?? null;
    $materia_nombre = trim($data['materia_nombre'] ?? '');
    $cuatrimestre = (int)($data['cuatrimestre'] ?? 1);
    $calificacion = (float)($data['calificacion'] ?? 0);
    $periodo = trim($data['periodo'] ?? '');

    if (!$alumno_id || $materia_nombre === '') {
        http_response_code(400);
        echo json_encode(['error' => 'alumno_id y materia_nombre son requeridos.']);
        return;
    }
    if ($cuatrimestre < 1 || $cuatrimestre > 10) {
        http_response_code(400);
        echo json_encode(['error' => 'Cuatrimestre debe ser entre 1 y 10.']);
        return;
    }
    if ($calificacion < 0 || $calificacion > 100) {
        http_response_code(400);
        echo json_encode(['error' => 'Calificación debe ser entre 0 y 100.']);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO calificaciones (alumno_id, materia_nombre, cuatrimestre, calificacion, periodo) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$alumno_id, $materia_nombre, $cuatrimestre, $calificacion, $periodo]);
    echo json_encode([
        'success' => true,
        'message' => 'Calificación registrada.',
        'id' => $pdo->lastInsertId()
    ]);
}

function handlePut($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID es requerido.']);
        return;
    }
    $calificacion = isset($data['calificacion']) ? (float)$data['calificacion'] : null;
    $materia_nombre = isset($data['materia_nombre']) ? trim($data['materia_nombre']) : null;
    $periodo = isset($data['periodo']) ? trim($data['periodo']) : null;

    $updates = [];
    $params = [];
    if ($calificacion !== null) { $updates[] = 'calificacion = ?'; $params[] = $calificacion; }
    if ($materia_nombre !== null) { $updates[] = 'materia_nombre = ?'; $params[] = $materia_nombre; }
    if ($periodo !== null) { $updates[] = 'periodo = ?'; $params[] = $periodo; }
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nada que actualizar.']);
        return;
    }
    $params[] = $id;
    $sql = "UPDATE calificaciones SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'message' => 'Calificación actualizada.']);
}

function handleDelete($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? ($_GET['id'] ?? null);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID es requerido.']);
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM calificaciones WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Calificación eliminada.']);
}
