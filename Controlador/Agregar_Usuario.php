<?php
// Asegurarse de que no haya salida antes de los encabezados
ob_start(); // Iniciar buffer de salida
include("../database/conexion.php");

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean(); // Limpiar cualquier salida previa
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario
$id_usuario = isset($_POST['id_usuario']) ? $_POST['id_usuario'] : null;
$codigo_u = $_POST['codigo_u'];
$nombre = $_POST['nombre'];
$correo = $_POST['correo'];
$id_rol = $_POST['id_rol'];
$contraseña = isset($_POST['contraseña']) ? $_POST['contraseña'] : ''; // Capturar contraseña

// Verificar si es estudiante
$es_estudiante = ($id_rol == '1');

// Validar campos requeridos comunes
if (empty($codigo_u) || empty($nombre) || empty($correo) || empty($id_rol)) {
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
    // Si no es estudiante, usar valores nulos o vacíos
    $semestre = null;
    $id_programa = null;
}

// Inicializamos la respuesta
$response = ['status' => 'error', 'message' => ''];

try {
    // Verificar si el código de usuario ya existe
    $verificarQuery = "SELECT ID_Usuario FROM usuario WHERE codigo_u = ?";
    $stmt = $conn->prepare($verificarQuery);
    $stmt->bind_param("s", $codigo_u);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response['message'] = 'El código de usuario ya existe';
        ob_end_clean(); // Limpiar cualquier salida previa
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Verificar también si el correo ya existe
    $verificarCorreoQuery = "SELECT ID_Usuario FROM usuario WHERE correo = ?";
    $stmt = $conn->prepare($verificarCorreoQuery);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response['message'] = 'El correo electrónico ya está registrado';
        ob_end_clean(); // Limpiar cualquier salida previa
        header('Content-Type: application/json');
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