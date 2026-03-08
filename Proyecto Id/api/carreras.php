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
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM carreras WHERE id = ?");
        $stmt->execute([$id]);
        $c = $stmt->fetch();
        if (!$c) { http_response_code(404); echo json_encode(['error' => 'Carrera no encontrada']); return; }
        echo json_encode(['success' => true, 'carrera' => $c]);
        return;
    }
    $stmt = $pdo->query("SELECT * FROM carreras ORDER BY nombre");
    $carreras = $stmt->fetchAll();
    echo json_encode(['success' => true, 'carreras' => $carreras]);
}

function handlePost($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $nombre = trim($data['nombre'] ?? '');
    if (!$nombre) {
        http_response_code(400);
        echo json_encode(['error' => 'Nombre de carrera es requerido']);
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO carreras (nombre) VALUES (?)");
    try {
        $stmt->execute([$nombre]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Carrera creada']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode(['error' => 'Ya existe una carrera con ese nombre']);
        } else throw $e;
    }
}

function handlePut($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $nombre = trim($data['nombre'] ?? '');
    if (!$id || !$nombre) {
        http_response_code(400);
        echo json_encode(['error' => 'ID y nombre son requeridos']);
        return;
    }
    try {
        $stmt = $pdo->prepare("UPDATE carreras SET nombre = ? WHERE id = ?");
        $stmt->execute([$nombre, $id]);
        echo json_encode(['success' => true, 'message' => 'Carrera actualizada']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode(['error' => 'Ya existe una carrera con ese nombre']);
        } else throw $e;
    }
}

function handleDelete($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? ($_GET['id'] ?? null);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); return; }
    $pdo->prepare("DELETE FROM carreras WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Carrera eliminada']);
}
