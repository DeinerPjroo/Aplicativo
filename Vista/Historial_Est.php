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
     <?php include("../Vista/Sidebar_Docente.html"); ?>

     <section class="Encabezado">
         <h1><center>Historial de Reservas</center></h1>
     </section>

     <section class="Table">
         <table border="1">
             <thead>
                 <tr>
                     <th>ID Reserva</th>
                     <th>Usuario</th>
                     <th>Fecha</th>
                     <th>Hora</th>
                     <th>Estado</th>
                 </tr>
             </thead>
             <tbody>
                 <?php
                 // Conexión a la base de datos
                 include("../database/conexion.php");
                 $query = "SELECT ID_Registro, usuario, fecha, hora, estado FROM registro";
                 $result = $conn->query($query);

                 if ($result->num_rows > 0) {
                     while ($row = $result->fetch_assoc()) {
                         echo "<tr>";
                         echo "<td>" . $row['id_reserva'] . "</td>";
                         echo "<td>" . $row['usuario'] . "</td>";
                         echo "<td>" . $row['fecha'] . "</td>";
                         echo "<td>" . $row['hora'] . "</td>";
                         echo "<td>" . $row['estado'] . "</td>";
                         echo "</tr>";
                     }
                 } else {
                     echo "<tr><td colspan='5'>No hay historial disponible</td></tr>";
                 }
                 $conn->close();
                 ?>
             </tbody>
         </table>
     </section>

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