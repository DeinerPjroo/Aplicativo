<?php
// test_modificar.php - Prueba simple para el modal de modificar
header('Content-Type: application/json');
session_start();

echo json_encode([
    'status' => 'debug',
    'session_active' => isset($_SESSION['usuario_id']),
    'usuario_id' => $_SESSION['usuario_id'] ?? 'No definido',
    'post_data' => $_POST,
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
