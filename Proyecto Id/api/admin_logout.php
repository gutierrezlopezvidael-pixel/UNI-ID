<?php
require_once __DIR__ . '/../config/database.php';
setApiHeaders();

session_destroy();
echo json_encode(['success' => true, 'message' => 'Sesión cerrada correctamente.']);
