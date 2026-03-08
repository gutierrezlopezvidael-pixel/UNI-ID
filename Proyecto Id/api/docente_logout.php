<?php
require_once __DIR__ . '/../config/database.php';
setApiHeaders();
$_SESSION = [];
session_destroy();
echo json_encode(['success' => true]);
