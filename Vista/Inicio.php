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

 <!------------------------------------------------------------------------------------->
     <!--SIDEBAR-->
     <?php 
     include("../Vista/Sidebar.php");
     ?>
<!------------------------------------------------------------------------------------->


</body>

</html>

<?php
