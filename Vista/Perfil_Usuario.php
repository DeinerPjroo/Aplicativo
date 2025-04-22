<?php
include("../Controlador/control_De_Rol.php");
checkRole('Estudiante'); // Solo estudiantes pueden acceder
checkRole('Docente'); // Solo docentes pueden acceder
checkRole('Administrativo'); // Solo administrativos pueden acceder
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

<body>

 <!------------------------------------------------------------------------------------->
     <!--SIDEBAR-->
     <?php include("../Vista/Sidebar_Docente.html"); ?>
    

<!------------------------------------------------------------------------------------->

<section class="Encabezado">
    <h1><center>Perfil</center></h1>
     </section>
     <section class="Panel-Perfil">
       
        <section class="Perfil">
            <img src="../Imagen/Foto_Uniguajira.webp" alt="">
            <section>
    </section>

    <Label>Deiner Florian Pajaro</Label>


</body>

</html>

<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../Vista/Login.php");
    exit();
}

// Verificar permisos por rol (opcional)
if ($_SESSION['usuario_rol'] != 'Administrador' && strpos($_SERVER['PHP_SELF'], 'Administrar_Usuarios.php') !== false) {
    header("Location: ../Vista/Login.php");
    exit();
}
?>