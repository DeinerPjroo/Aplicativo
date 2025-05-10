<?php
include("../database/Conexion.php");

// Iniciar sesión
session_start();

// Verificar si ya hay una sesión activa
if (isset($_SESSION['usuario_id'])) {
    // Si ya hay sesión activa, redirigir a la página de inicio
    header("location:../Vista/Inicio.php");
}







if (!empty($_POST["btningresar"])) {
    if (!empty($_POST["usuario"]) and !empty($_POST["contraseña"])) {
        $usuario = $_POST["usuario"];
        $contraseña = $_POST["contraseña"];
        
        // Consulta preparada que obtiene el rol del usuario
        $stmt = $conn->prepare("SELECT u.*, r.nombreRol 
                               FROM usuario u 
                               LEFT JOIN rol r ON u.ID_Rol = r.ID_Rol 
                               WHERE u.nombre = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            $usuario_data = $resultado->fetch_assoc();
            
            // Verificar la contraseña
            if ($usuario_data['contraseña'] == $contraseña) { // Ideal sería usar password_verify()
                // Guardar datos en la sesión
                $_SESSION['usuario_id'] = $usuario_data['ID_Usuario'];
                $_SESSION['codigo_usuario'] = $usuario_data['Codigo_U'];
                $_SESSION['usuario_nombre'] = $usuario_data['nombre'];
                $_SESSION['usuario_correo'] = $usuario_data['correo'];
                $_SESSION['usuario_rol'] = $usuario_data['nombreRol']; // Asegúrate de que el rol se almacena correctamente
                
                // Redirigir según el rol
                header("location:../Vista/Inicio.php");
            } else {
                echo "<div class='Error'>Contraseña incorrecta</div>";
            }
        } else {
            echo "<div class='Error'>Usuario no encontrado</div>";
        }
    } else {
        echo "<div class='Error'>*Los Campos están vacíos*</div>";
    }
}


?>
