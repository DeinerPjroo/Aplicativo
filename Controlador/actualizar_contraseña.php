<?php
// Controlador/actualizar_contraseña.php
session_start();
include("../database/conection.php");
include("control_De_Rol.php");

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    // Si no está logueado, redirigir al login
    header("Location: ../index.php");
    exit();
}

// Verificar que se haya enviado el formulario mediante POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener datos del formulario
    $passwordActual = $_POST['passwordActual'];
    $passwordNueva = $_POST['passwordNueva'];
    $passwordConfirmar = $_POST['passwordConfirmar'];
    
    // El ID del usuario desde la sesión
    $usuario_id = $_SESSION['usuario_id'];
    
    // Validaciones básicas
    $errores = [];
    
    // Validar que los campos no estén vacíos
    if (empty($passwordActual) || empty($passwordNueva) || empty($passwordConfirmar)) {
        $errores[] = "Todos los campos de contraseña son obligatorios";
    }
    
    // Validar que la nueva contraseña y la confirmación coincidan
    if ($passwordNueva !== $passwordConfirmar) {
        $errores[] = "La nueva contraseña y la confirmación no coinciden";
    }
    
    
    
    // Si no hay errores iniciales, verificar la contraseña actual
    if (empty($errores)) {
        // Obtener la contraseña actual del usuario desde la base de datos
        $stmt = $conn->prepare("SELECT contraseña FROM usuario WHERE ID_Usuario = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $hash_guardado = $row['contraseña'];
            
            // Verificar si la contraseña actual ingresada es correcta
            if (!password_verify($passwordActual, $hash_guardado)) {
                $errores[] = "La contraseña actual es incorrecta";
            }
        } else {
            $errores[] = "Error al verificar la contraseña actual";
        }
    }
    
    // Si hay errores, redirigir de vuelta al formulario con los errores
    if (!empty($errores)) {
        $_SESSION['errores_password'] = $errores;
        header("Location: ../Vista/perfil.php");
        exit();
    }
    
    // Si no hay errores, actualizar la contraseña
    // Generar hash de la nueva contraseña
    $hash_nueva_password = password_hash($passwordNueva, PASSWORD_DEFAULT);
    
    // Actualizar la contraseña en la base de datos
    $stmt = $conn->prepare("UPDATE usuario SET contraseña = ? WHERE ID_Usuario = ?");
    $stmt->bind_param("si", $hash_nueva_password, $usuario_id);
    
    if ($stmt->execute()) {
        // Actualización exitosa
        $_SESSION['success_message_password'] = "Contraseña actualizada correctamente";
    } else {
        // Error en la actualización
        $_SESSION['error_message_password'] = "Error al actualizar la contraseña: " . $conn->error;
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