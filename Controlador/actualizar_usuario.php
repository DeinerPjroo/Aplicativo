<?php
// Controlador/actualizar_usuario.php
session_start();
include("../database/conection.php");
include("control_De_Rol.php");

// OBSOLETO: Este archivo ha sido reemplazado por ControladorPerfil.php para la actualización de datos de usuario y contraseña.

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    // Si no está logueado, redirigir al login
    header("Location: ../Login.php");
    exit();
}

// Verificar que se haya enviado el formulario mediante POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener datos del formulario
    $nombreUsuario = trim($_POST['nombreUsuario']);
    $correoUsuario = trim($_POST['correoUsuario']);
    $telefonoUsuario = isset($_POST['telefonoUsuario']) ? trim($_POST['telefonoUsuario']) : null;
    
    // El ID del usuario desde la sesión
    $usuario_id = $_SESSION['usuario_id'];
    
    // Validaciones básicas
    $errores = [];
    
    // Validar nombre (no vacío)
    if (empty($nombreUsuario)) {
        $errores[] = "El nombre no puede estar vacío";
    }
    
    // Validar correo electrónico (formato válido)
    if (empty($correoUsuario)) {
        $errores[] = "El correo no puede estar vacío";
    } elseif (!filter_var($correoUsuario, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del correo electrónico no es válido";
    }
    
    // Validar que el correo no esté en uso por otro usuario
    $stmt = $conn->prepare("SELECT ID_Usuario FROM usuario WHERE correo = ? AND ID_Usuario != ?");
    $stmt->bind_param("si", $correoUsuario, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errores[] = "El correo electrónico ya está en uso por otro usuario";
    }
    
    // Si hay errores, redirigir de vuelta al formulario con los errores
    if (!empty($errores)) {
        $_SESSION['errores'] = $errores;
        header("Location: ../Vista/perfil.php");
        exit();
    }
    
    // Si no hay errores, actualizar la información del usuario
    
    // Consulta SQL para actualizar (no se actualiza el programa aquí, ya que requeriría lógica adicional)
    $sql = "UPDATE usuario SET nombre = ?, correo = ?";
    $params = "ss";
    $paramValues = [$nombreUsuario, $correoUsuario];
    
    // Si se proporciona el teléfono, añadirlo a la actualización
    if (!empty($telefonoUsuario)) {
        $sql .= ", telefono = ?";
        $params .= "s";
        $paramValues[] = $telefonoUsuario;
    }
    
    // Completar la consulta con la condición WHERE
    $sql .= " WHERE ID_Usuario = ?";
    $params .= "i";
    $paramValues[] = $usuario_id;
    
    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($params, ...$paramValues);
    
    if ($stmt->execute()) {
        // Actualización exitosa
        $_SESSION['success_message'] = "Datos actualizados correctamente";
    } else {
        // Error en la actualización
        $_SESSION['error_message'] = "Error al actualizar los datos: " . $conn->error;
    }
    
    // Redirigir de vuelta al perfil
    header("Location: ../Vista/perfil.php");
    exit();
} else {
    // Si no se envió el formulario por POST, redirigir
    header("Location: ../Vista/perfil.php");
    exit();
}
?>