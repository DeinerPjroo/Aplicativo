<?php
include("../database/conection.php");

// Inicializamos la respuesta
$response = ['existe' => false];

// Verificar si se recibió el código de usuario
if (isset($_POST['codigo_u']) && !empty($_POST['codigo_u'])) {
    $codigo_u = $_POST['codigo_u'];
    
    // Consulta para verificar si el código existe
    $verificarQuery = "SELECT ID_Usuario FROM usuario WHERE codigo_u = ?";
    $stmt = $conn->prepare($verificarQuery);
    $stmt->bind_param("s", $codigo_u);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Si hay resultados, el código ya existe
    if ($result->num_rows > 0) {
        $response['existe'] = true;
    }
}

// Devolver la respuesta como JSON
echo json_encode($response);

// Cerrar conexión
$conn->close();
?>