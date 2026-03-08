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
$password = trim($data['password'] ?? '');

if (empty($usuario) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Usuario y contraseña son requeridos.']);
    exit;
}

$pdo = getConnection();

$stmt = $pdo->prepare("SELECT * FROM admins WHERE usuario = ?");
$stmt->execute([$usuario]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Credenciales incorrectas.']);
    exit;
}

$_SESSION['admin_id'] = $admin['id'];
$_SESSION['admin_usuario'] = $admin['usuario'];
$_SESSION['admin_nombre'] = $admin['nombre'];

echo json_encode([
    'success' => true,
    'admin' => [
        'id' => $admin['id'],
        'usuario' => $admin['usuario'],
        'nombre' => $admin['nombre']
    ]
]);
