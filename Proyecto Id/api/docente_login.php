<?php
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$usuario = trim($data['usuario'] ?? '');
$password = $data['password'] ?? '';

if (empty($usuario) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Usuario y contraseña son requeridos']);
    exit;
}

$pdo = getConnection();
$stmt = $pdo->prepare("SELECT id, usuario, nombre, apellido, email, password_hash FROM docentes WHERE usuario = ?");
$stmt->execute([$usuario]);
$docente = $stmt->fetch();

if (!$docente || !password_verify($password, $docente['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Credenciales incorrectas']);
    exit;
}

$st = $pdo->prepare("SELECT materia_nombre, carrera, cuatrimestre, grupo FROM docente_asignaciones WHERE docente_id = ? ORDER BY carrera, cuatrimestre, materia_nombre");
$st->execute([$docente['id']]);
$asignaciones = $st->fetchAll();

unset($docente['password_hash']);
$docente['asignaciones'] = $asignaciones;

$_SESSION['docente_id'] = $docente['id'];
$_SESSION['docente_usuario'] = $docente['usuario'];

echo json_encode([
    'success' => true,
    'docente' => $docente
]);
