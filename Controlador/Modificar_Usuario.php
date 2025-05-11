<?php
include("../database/conexion.php");

// Obtener datos del formulario
$id_usuario = isset($_POST['id_usuario']) ? $_POST['id_usuario'] : '';
$codigo_u = isset($_POST['codigo_u']) ? $_POST['codigo_u'] : '';
$nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
$correo = isset($_POST['correo']) ? $_POST['correo'] : '';
$id_rol = isset($_POST['id_rol']) ? $_POST['id_rol'] : '';
$id_programa = isset($_POST['id_programa']) ? $_POST['id_programa'] : '';
$semestre = isset($_POST['semestre']) ? $_POST['semestre'] : '';
$contraseña = isset($_POST['contraseña']) ? $_POST['contraseña'] : '';

// Inicializamos la respuesta
$response = ['status' => 'error', 'message' => ''];

// Validación básica
if (empty($id_usuario) || empty($codigo_u) || empty($nombre) || empty($correo) || empty($id_rol) || empty($id_programa)) {
    $response['message'] = 'Todos los campos son obligatorios';
    echo json_encode($response);
    exit;
}

// Verificar si el código ya existe para otro usuario
$verificarQuery = "SELECT ID_Usuario FROM usuario WHERE codigo_u = ? AND ID_Usuario != ?";
$stmt = $conn->prepare($verificarQuery);
$stmt->bind_param("si", $codigo_u, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $response['message'] = 'El código de usuario ya está en uso por otro usuario';
    echo json_encode($response);
    exit;
}

// Preparar la consulta SQL
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

// Devolver la respuesta como JSON
echo json_encode($response);

// Cerrar conexiones
$stmt->close();
$conn->close();
?>