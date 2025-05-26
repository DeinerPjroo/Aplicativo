<?php
include("../Controlador/control_De_Rol.php");
checkRole(['Docente', 'Estudiante', 'Administrador', 'Administrativo']); // Solo usuarios con rol pueden acceder

$role = getUserRole(); // Asume que esta función devuelve el rol del usuario actual
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="../css/Style.css">
    <!--Link de google font (iconos)-->

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">


</head>

<body class="Registro">
    <!--SIDEBAR-->
    <?php include("../Vista/Sidebar.php"); ?>
    <div class="main-content" style="margin-left:250px; padding:40px 20px; min-height:80vh; display:flex; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:12px; box-shadow:0 2px 16px rgba(0,0,0,0.08); padding:40px 32px; max-width:420px; width:100%; text-align:center;">
            <h1 style="color:var(--primary-color); font-size:2.2rem; margin-bottom:18px;">¡Bienvenido, <?php echo htmlspecialchars($nombreUsuario ?? 'Usuario'); ?>!</h1>
            <p style="font-size:1.15rem; color:#444; margin-bottom:18px;">Has iniciado sesión como <b><?php echo htmlspecialchars($role); ?></b>.</p>
            <img src="../Imagen/logo.png" alt="Logo" style="width:110px; margin-bottom:18px;">
            <p style="color:#666;">Desde aquí puedes navegar por el sistema usando el menú lateral.<br>¡Que tengas un excelente día!</p>
        </div>
    </div>
</body>

</html>

<?php
