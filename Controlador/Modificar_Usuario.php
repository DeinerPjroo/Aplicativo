<?php
include("../database/conection.php");

// Asegurarse de que no haya salida antes de los encabezados
ob_start(); // Iniciar buffer de salida

date_default_timezone_set('America/Bogota'); // Establece la zona horaria a Bogotá, Colombia.



// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean(); // Limpiar cualquier salida previa
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario
$id_usuario = isset($_POST['id_usuario']) ? $_POST['id_usuario'] : '';
$codigo_u = isset($_POST['codigo_u']) ? $_POST['codigo_u'] : '';
$nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
$correo = isset($_POST['correo']) ? $_POST['correo'] : '';
$id_rol = isset($_POST['id_rol']) ? $_POST['id_rol'] : '';
$contraseña = isset($_POST['contraseña']) ? $_POST['contraseña'] : '';

// Verificar si es estudiante
$es_estudiante = ($id_rol == '1');

// Validación básica para todos los usuarios
if (empty($id_usuario) || empty($codigo_u) || empty($nombre) || empty($correo) || empty($id_rol)) {
    ob_end_clean(); // Limpiar cualquier salida previa
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Por favor, complete todos los campos obligatorios.']);
    exit;
}

// Validación adicional solo si es estudiante
if ($es_estudiante) {
    $semestre = isset($_POST['semestre']) ? $_POST['semestre'] : '';
    $id_programa = isset($_POST['id_programa']) ? $_POST['id_programa'] : '';
    
    if (empty($semestre) || empty($id_programa)) {
        ob_end_clean(); // Limpiar cualquier salida previa
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Para usuarios estudiantes, el programa y semestre son obligatorios.']);
        exit;
    }
} else {
    // Si no es estudiante, usar valores nulos
    $semestre = null;
    $id_programa = null;
}

// Inicializamos la respuesta
$response = ['status' => 'error', 'message' => ''];

try {
    // Verificar si el código ya existe para otro usuario
    $verificarQuery = "SELECT ID_Usuario FROM usuario WHERE codigo_u = ? AND ID_Usuario != ?";
    $stmt = $conn->prepare($verificarQuery);
    $stmt->bind_param("si", $codigo_u, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response['message'] = 'El código de usuario ya está en uso por otro usuario';
        ob_end_clean(); // Limpiar cualquier salida previa
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Verificar si el correo ya existe para otro usuario
    $verificarCorreoQuery = "SELECT ID_Usuario FROM usuario WHERE correo = ? AND ID_Usuario != ?";
    $stmt = $conn->prepare($verificarCorreoQuery);
    $stmt->bind_param("si", $correo, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response['message'] = 'El correo electrónico ya está en uso por otro usuario';
        ob_end_clean(); // Limpiar cualquier salida previa
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Preparar la consulta SQL según si hay nueva contraseña o no
    if (!empty($contraseña)) {
        // Si hay contraseña nueva, actualizarla también
        $contraseña_hash = password_hash($contraseña, PASSWORD_DEFAULT);
        $updateQuery = "UPDATE usuario SET codigo_u = ?, nombre = ?, correo = ?, contraseña = ?, id_rol = ?, Id_Programa = ?, semestre = ? WHERE ID_Usuario = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssssiisi", $codigo_u, $nombre, $correo, $contraseña_hash, $id_rol, $id_programa, $semestre, $id_usuario);
    } else {
        // Si no hay contraseña nueva, no actualizarla
        $updateQuery = "UPDATE usuario SET codigo_u = ?, nombre = ?, correo = ?, id_rol = ?, Id_Programa = ?, semestre = ? WHERE ID_Usuario = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssiisi", $codigo_u, $nombre, $correo, $id_rol, $id_programa, $semestre, $id_usuario);
    }

    // Ejecutar la consulta
    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Usuario actualizado correctamente';
    } else {
        $response['message'] = 'Error al actualizar usuario: ' . $conn->error;
    }

    // Cerrar el statement
    $stmt->close();

} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Cerrar conexión
$conn->close();

// Asegurarse de que no haya salida previa
ob_end_clean();

// Establecer encabezados y enviar respuesta JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>