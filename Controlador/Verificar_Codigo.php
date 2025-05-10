<?php
// Verificar_Codigo.php
include("../database/conexion.php");

// Verificar si la solicitud es de tipo POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener el código de usuario desde la solicitud
    $codigo_u = isset($_POST['codigo_u']) ? $_POST['codigo_u'] : '';
    
    // Preparar la consulta para verificar si el código ya existe
    $query = "SELECT COUNT(*) as total FROM usuario WHERE codigo_u = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $codigo_u);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Devolver respuesta en formato JSON
    header('Content-Type: application/json');
    echo json_encode(['existe' => ($row['total'] > 0)]);
    
    // Cerrar la conexión
    $stmt->close();
    $conn->close();
} else {
    // Si no es una solicitud POST, devolver error
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Método no permitido']);
}
?>