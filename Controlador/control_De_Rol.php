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
        echo getErrorModal();
        exit(); // Detener la ejecución aquí
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

// Agregar el HTML del modal al final de cada página que use checkRole
function getErrorModal() {
    return <<<HTML
    <div class="modal" style="display: block;">
        <div class="modal-content">
            <h2>Acceso Denegado</h2>
            <p>No tienes permiso para acceder a esta página.</p>
            <p>Serás redirigido en 3 segundos...</p>
        </div>
    </div>
    <style>
        .modal {
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .modal-content h2 {
            color: #e74c3c;
            margin-bottom: 15px;
        }
    </style>
    <script>
        setTimeout(() => {
            window.location.href = '../Controlador/logout.php';
        }, 3000);
    </script>
HTML;
}
?>