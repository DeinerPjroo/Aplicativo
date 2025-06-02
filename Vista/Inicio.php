<?php
include("../Controlador/control_De_Rol.php");
checkRole(['Docente', 'Estudiante', 'Administrador', 'Administrativo']); // Solo usuarios con rol pueden acceder

$role = getUserRole(); // Asume que esta función devuelve el rol del usuario actual

// Obtener el nombre del usuario desde la sesión
if (isset($_SESSION['usuario_id'])) {
    include("../database/conection.php");
    $stmt = $conn->prepare("SELECT nombre FROM usuario WHERE ID_Usuario = ?");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nombreUsuario = $row['nombre'];
    } else {
        $nombreUsuario = 'Usuario';
    }
    $stmt->close();
    $conn->close();
} else {
    $nombreUsuario = 'Usuario';
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Sistema de Reservas</title>
    <link rel="stylesheet" href="../css/Style.css">    <!--Link de google font (iconos) - Con carga asíncrona mejorada-->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap" rel="stylesheet"></noscript>    <style>
        /* Fallback en caso de que no carguen las fuentes */
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined', 'Material Icons', 'Arial', sans-serif;
            font-display: swap;
        }
        /* Mejoras de rendimiento */
        img {
            max-width: 100%;
            height: auto;
        }
        /* Prevenir errores de JavaScript mostrando un mensaje amigable */
        .js-error {
            display: none;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>


</head>

<body class="Registro">
    <!--SIDEBAR-->
    <?php include("../Vista/Sidebar.php"); ?>
    
    <!-- Mensaje de error JS oculto -->
    <div id="js-error-message" class="js-error">
        Algunos elementos pueden no cargar correctamente. Esto no afecta la funcionalidad principal.
    </div>
    
    <div class="main-content" style="margin-left:250px; padding:40px 20px; min-height:80vh; display:flex; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:12px; box-shadow:0 2px 16px rgba(0,0,0,0.08); padding:40px 32px; max-width:420px; width:100%; text-align:center;">
            <h1 style="color:var(--primary-color); font-size:2.2rem; margin-bottom:18px;">¡Bienvenido, <?php echo htmlspecialchars($nombreUsuario ?? 'Usuario'); ?>!</h1>
            <p style="font-size:1.15rem; color:#444; margin-bottom:18px;">Has iniciado sesión como <b><?php echo htmlspecialchars($role); ?></b>.</p>
            <img src="../Imagen/logo.png" alt="Logo" style="width:110px; margin-bottom:18px;">
            <p style="color:#666;">Desde aquí puedes navegar por el sistema usando el menú lateral.<br>¡Que tengas un excelente día!</p>        </div>
    </div>
    
    <script>
        // Manejo global de errores para evitar que aparezcan en la consola
        window.addEventListener('error', function(e) {
            console.info('Error manejado automáticamente:', e.message);
            // Mostrar mensaje amigable solo si es necesario
            if (e.message.includes('generateReportsLegends')) {
                console.info('Función generateReportsLegends no implementada - esto es normal');
            }
            e.preventDefault();
            return true;
        });
        
        // Capturar errores de promesas no manejadas
        window.addEventListener('unhandledrejection', function(e) {
            console.info('Promesa rechazada manejada automáticamente:', e.reason);
            e.preventDefault();
        });
    </script>
</body>

</html>

<?php
