<?php
// Iniciar o continuar la sesión
// Esto asegura que la sesión esté activa para acceder a las variables de sesión.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
// Esta función verifica si el usuario ha iniciado sesión.
// Si no se encuentra la variable de sesión 'usuario_id', redirige al usuario a la página de inicio de sesión.
function checkAuthentication() {
    if (!isset($_SESSION['usuario_id'])) {
        // Redirige al usuario a la página de inicio de sesión si no está autenticado.
        header("Location: ../Vista/Login.php");
        exit();
    }
}

// Verificar si el usuario tiene un rol específico
// Esta función verifica si el usuario tiene uno de los roles permitidos para acceder a una página.
// Si el usuario no tiene el rol adecuado, se le redirige a una página de error.
function checkRole($allowed_roles) {
    // Verifica si el usuario está autenticado antes de verificar el rol.
    checkAuthentication();
    
    // Si $allowed_roles es un string, lo convierte en un array para facilitar la comparación.
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    // Verifica si el rol del usuario está en la lista de roles permitidos.
    if (!in_array($_SESSION['usuario_rol'], $allowed_roles)) {
        // Si el rol del usuario no está permitido, redirige a una página de error.
        header("Location: ../Vista/Error_Permiso.php");
        exit();
    }
}

// Obtener el rol del usuario actual
// Esta función devuelve el rol del usuario almacenado en la sesión.
// Si no hay un rol definido, devuelve null.
function getUserRole() {
    if (isset($_SESSION['usuario_rol'])) {
        return $_SESSION['usuario_rol']; // Devuelve el rol almacenado en la sesión
    }
    return null; // Devuelve null si no hay rol definido
}
?>