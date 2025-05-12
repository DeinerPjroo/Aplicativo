<?php
header('Content-Type: application/json');

try {
    // Lógica para verificar disponibilidad
    // ...existing code...

    echo json_encode([
        'disponible' => true // o false según la lógica
    ]);
} catch (Exception $e) {
    echo json_encode([
        'disponible' => false,
        'error' => $e->getMessage()
    ]);
}