<?php
include("../database/conection.php");

// Inicializamos la respuesta
$response = ['status' => 'error', 'message' => ''];

// Verificar si se recibió el ID de usuario
if (isset($_POST['id_usuario']) && !empty($_POST['id_usuario'])) {
    $id_usuario = $_POST['id_usuario'];
    
    // Primero obtenemos el código del usuario para devolverlo en la respuesta
    $codigoQuery = "SELECT codigo_u FROM usuario WHERE ID_Usuario = ?";
    $stmt = $conn->prepare($codigoQuery);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        $codigo_usuario = $usuario['codigo_u'];
        
        // Preparar la consulta de eliminación
        $deleteQuery = "DELETE FROM usuario WHERE ID_Usuario = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $id_usuario);
        
        // Ejecutar la consulta
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Usuario eliminado correctamente';
            $response['codigo'] = $codigo_usuario; // Devolvemos el código para identificar la fila
        } else {
            $response['message'] = 'Error al eliminar usuario: ' . $conn->error;
        }
    } else {
        $response['message'] = 'Usuario no encontrado';
    }
} else {
    $response['message'] = 'ID de usuario no especificado';
}

// Devolver la respuesta como JSON
echo json_encode($response);

// Cerrar conexión
$conn->close();
?>