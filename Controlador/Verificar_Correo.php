<?php
// Controlador/Verificar_Correo.php
include("../database/conexion.php");

header('Content-Type: application/json');

if (isset($_POST['correo'])) {
    $correo = $_POST['correo'];
    $idUsuario = isset($_POST['id_usuario']) ? $_POST['id_usuario'] : '';
    
    // Consulta SQL para verificar si el correo existe
    if (!empty($idUsuario)) {
        // Si es una edición, excluimos el usuario actual
        $query = "SELECT ID_Usuario FROM usuario WHERE correo = ? AND ID_Usuario != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $correo, $idUsuario);
    } else {
        // Si es una inserción, verificamos todos
        $query = "SELECT ID_Usuario FROM usuario WHERE correo = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $correo);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode([
        'existe' => $result->num_rows > 0
    ]);
    
    $stmt->close();
} else {
    echo json_encode([
        'existe' => false
    ]);
}

$conn->close();
?>