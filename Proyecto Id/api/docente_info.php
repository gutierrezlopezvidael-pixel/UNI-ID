<?php
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$pdo = getConnection();

if (!isset($_SESSION['docente_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión de docente requerida']);
    exit;
}

$docente_id = (int)$_SESSION['docente_id'];
$stmt = $pdo->prepare("SELECT id, usuario, nombre, apellido, email FROM docentes WHERE id = ?");
$stmt->execute([$docente_id]);
$docente = $stmt->fetch();

if (!$docente) {
    session_destroy();
    http_response_code(401);
    echo json_encode(['error' => 'Docente no encontrado']);
    exit;
}

$st = $pdo->prepare("SELECT id, materia_nombre, carrera, cuatrimestre, grupo FROM docente_asignaciones WHERE docente_id = ? ORDER BY carrera, cuatrimestre, materia_nombre");
$st->execute([$docente_id]);
$docente['asignaciones'] = $st->fetchAll();

echo json_encode([
    'success' => true,
    'docente' => $docente
]);
