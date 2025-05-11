<?php
include("../database/conexion.php");

// Obtener datos del formulario
$codigo_u = isset($_POST['codigo_u']) ? $_POST['codigo_u'] : '';
$nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
$correo = isset($_POST['correo']) ? $_POST['correo'] : '';
$contraseña = isset($_POST['contraseña']) ? $_POST['contraseña'] : '';
$id_rol = isset($_POST['id_rol']) ? $_POST['id_rol'] : '';
$id_programa = isset($_POST['id_programa']) ? $_POST['id_programa'] : '';
$semestre = isset($_POST['semestre']) ? $_POST['semestre'] : '';

// Inicializamos la respuesta
$response = ['status' => 'error', 'message' => ''];

// Validación básica
if (empty($codigo_u) || empty($nombre) || empty($correo) || empty($contraseña) || empty($id_rol) || empty($id_programa)) {
    $response['message'] = 'Todos los campos son obligatorios';
    echo json_encode($response);
    exit;
}

// Verificar si el código de usuario ya existe
$verificarQuery = "SELECT ID_Usuario FROM usuario WHERE codigo_u = ?";
$stmt = $conn->prepare($verificarQuery);
$stmt->bind_param("s", $codigo_u);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $response['message'] = 'El código de usuario ya existe';
    echo json_encode($response);
    exit;
}

// Hashear la contraseña para mayor seguridad
$contraseña_hash = password_hash($contraseña, PASSWORD_DEFAULT);

// Preparar la consulta SQL con sentencias preparadas
$insertQuery = "INSERT INTO usuario (codigo_u, nombre, correo, contraseña, id_rol, Id_Programa, semestre) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("ssssiis", $codigo_u, $nombre, $correo, $contraseña_hash, $id_rol, $id_programa, $semestre);

// Ejecutar la consulta
if ($stmt->execute()) {
    $response['status'] = 'success';
    $response['message'] = 'Usuario agregado correctamente';
} else {
    $response['message'] = 'Error al agregar usuario: ' . $conn->error;
}

// Devolver la respuesta como JSON
echo json_encode($response);

// Cerrar conexiones
$stmt->close();
$conn->close();
?>