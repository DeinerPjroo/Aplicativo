<?php
// Incluye el archivo de conexión a la base de datos.
// Este archivo establece la conexión con la base de datos MySQL utilizando la clase mysqli.
include("../database/conection.php");

// Incluye el archivo que contiene la lógica para el control de roles.
// Este archivo define funciones como `checkRole` para verificar si el usuario tiene permisos para acceder a esta página.
include("../Controlador/control_De_Rol.php");

// Llama a la función `checkRole` para verificar si el usuario tiene el rol de 'Administrador'.
// Si el usuario no tiene el rol requerido, la función redirige o bloquea el acceso a esta página.
checkRole('Administrador');
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
     <?php include("../Vista/Sidebar.php"); ?>
<!------------------------------------------------------------------------------------->
<section class="Encabezado">
    <h1><center>Revisar Disponibilidad ( MARIAAA TE TOCA)</center></h1>
     </section>
     <section class="Table">
        <Table></Table>
         </section>


</body>

</html>

<?php
