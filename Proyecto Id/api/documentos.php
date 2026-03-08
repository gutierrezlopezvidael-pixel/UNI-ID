<?php
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

$pdo = getConnection();
$method = $_SERVER['REQUEST_METHOD'];

$uploadDir = __DIR__ . '/../uploads/documentos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

switch ($method) {
    case 'GET':
        handleGet($pdo);
        break;
    case 'POST':
        handlePost($pdo, $uploadDir);
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
    $tipo_documento = $_GET['tipo_documento'] ?? null;
    
    $sql = "SELECT d.*, a.nombre as alumno_nombre, a.apellido as alumno_apellido, a.matricula FROM documentos d JOIN alumnos a ON d.alumno_id = a.id WHERE 1=1";
    $params = [];
    if ($alumno_id) {
        $sql .= " AND d.alumno_id = ?";
        $params[] = $alumno_id;
    }
    if ($tipo_documento) {
        $sql .= " AND (d.descripcion LIKE ? OR d.descripcion = ?)";
        $params[] = '%' . $tipo_documento . '%';
        $params[] = $tipo_documento;
    }
    $sql .= " ORDER BY d.fecha_subida DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $documentos = $stmt->fetchAll();
    echo json_encode(['success' => true, 'documentos' => $documentos]);
}

function handlePost($pdo, $uploadDir) {
    $alumno_id = $_POST['alumno_id'] ?? null;
    $descripcion = $_POST['descripcion'] ?? '';
    $tipo_documento = $_POST['tipo_documento'] ?? '';
    if ($tipo_documento === 'kardex') $descripcion = 'Kardex';
    
    if (!$alumno_id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID del alumno es requerido.']);
        return;
    }
    
    $check = $pdo->prepare("SELECT id, matricula FROM alumnos WHERE id = ?");
    $check->execute([$alumno_id]);
    $alumno = $check->fetch();
    if (!$alumno) {
        http_response_code(404);
        echo json_encode(['error' => 'Alumno no encontrado.']);
        return;
    }
    
    if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'No se recibió ningún archivo o hubo un error en la subida.']);
        return;
    }
    
    $file = $_FILES['documento'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => 'El archivo excede el tamaño máximo de 10MB.']);
        return;
    }
    
    $alumnoDir = $uploadDir . $alumno['matricula'] . '/';
    if (!is_dir($alumnoDir)) {
        mkdir($alumnoDir, 0755, true);
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeFilename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
    $destPath = $alumnoDir . $safeFilename;
    $relativePath = 'uploads/documentos/' . $alumno['matricula'] . '/' . $safeFilename;
    
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar el archivo.']);
        return;
    }
    
    $stmt = $pdo->prepare("INSERT INTO documentos (alumno_id, nombre_archivo, ruta, tipo, tamano, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $alumno_id,
        $file['name'],
        $relativePath,
        $file['type'],
        $file['size'],
        $descripcion
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Documento subido exitosamente.',
        'documento' => [
            'id' => $pdo->lastInsertId(),
            'nombre_archivo' => $file['name'],
            'ruta' => $relativePath,
            'tipo' => $file['type'],
            'tamano' => $file['size']
        ]
    ]);
}

function handleDelete($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? ($_GET['id'] ?? null);
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID del documento es requerido.']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT ruta FROM documentos WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
    
    if (!$doc) {
        http_response_code(404);
        echo json_encode(['error' => 'Documento no encontrado.']);
        return;
    }
    
    $filePath = __DIR__ . '/../' . $doc['ruta'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    $del = $pdo->prepare("DELETE FROM documentos WHERE id = ?");
    $del->execute([$id]);
    
    echo json_encode(['success' => true, 'message' => 'Documento eliminado exitosamente.']);
}
