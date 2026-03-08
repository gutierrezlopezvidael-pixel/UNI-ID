<?php
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email y contraseña son requeridos.']);
    exit;
}

$pdo = getConnection();
$stmt = $pdo->prepare("SELECT * FROM alumnos WHERE email = ? AND estado = 'activo'");
$stmt->execute([$email]);
$alumno = $stmt->fetch();

if (!$alumno || !password_verify($password, $alumno['password_hash'] ?? '')) {
    http_response_code(401);
    echo json_encode(['error' => 'Credenciales incorrectas.']);
    exit;
}

$stmtDocs = $pdo->prepare("SELECT id, nombre_archivo, tipo, tamano, descripcion, fecha_subida FROM documentos WHERE alumno_id = ? ORDER BY fecha_subida DESC");
$stmtDocs->execute([$alumno['id']]);
$documentos = $stmtDocs->fetchAll();

unset($alumno['password_hash']);
$_SESSION['alumno_id'] = $alumno['id'];
$_SESSION['alumno_email'] = $alumno['email'];

echo json_encode([
    'success' => true,
    'alumno' => $alumno,
    'documentos' => $documentos
]);
