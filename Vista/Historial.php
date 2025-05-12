<?php
date_default_timezone_set('America/Bogota');
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain.1252');

include("../Controlador/control_De_Rol.php");
checkRole(['Docente', 'Estudiante', 'Administrador', 'Administrativo']); // Solo usuarios con rol pueden acceder

$role = getUserRole(); // Asume que esta función devuelve el rol del usuario actual
?>

<?php


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
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial</title>
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

     <section class="Encabezado">
         <h1><center>Historial de Reservas</center></h1>
     </section>

     <section class="Table">
        <div class="contenedor-reservas">
            <h2>Historial</h2>
            <table class="tabla-reservas">
                <thead>
                    <!-- Encabezados de la tabla -->
                    <tr>
                        <th>Recurso</th>
                        <th>Fecha</th>
                        <th>Hora Inicio</th>
                        <th>Hora Fin</th>
                        <th>Nombre Usuario</th>
                        <th>Nombre Docente</th>
                        <th>Asignatura</th>
                        <th>Programa</th>
                        <th>Semestre</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php
// Conexión a la base de datos
include("../database/conection.php");

// Obtener el ID del usuario logueado
$usuario_id = $_SESSION['usuario_id'];

// Consulta para obtener el historial del usuario logueado
$sql = "SELECT 
            r.ID_Registro,
            r.fechaReserva,
            r.horaInicio,
            r.horaFin,
            rc.nombreRecurso,
            u.nombre AS nombreUsuario,
            COALESCE(doc.nombre, 'Sin docente') AS nombreDocente,
            COALESCE(asig.nombreAsignatura, 'Sin asignatura') AS asignatura,
            COALESCE(pr.nombrePrograma, 'Sin programa') AS programa,
            CASE 
                WHEN u.ID_Rol = 1 THEN COALESCE(u.semestre, 'Sin semestre')
                ELSE 'No aplica'
            END AS semestre,
            r.estado
        FROM registro r
        LEFT JOIN usuario u ON r.ID_Usuario = u.ID_Usuario
        LEFT JOIN recursos rc ON r.ID_Recurso = rc.ID_Recurso
        LEFT JOIN docente_asignatura da ON r.ID_DocenteAsignatura = da.ID_DocenteAsignatura
        LEFT JOIN usuario doc ON da.ID_Usuario = doc.ID_Usuario
        LEFT JOIN asignatura asig ON da.ID_Asignatura = asig.ID_Asignatura
        LEFT JOIN programa pr ON asig.ID_Programa = pr.ID_Programa
        WHERE r.ID_Usuario = ? -- Filtrar por el usuario logueado
        ORDER BY r.fechaReserva DESC, r.horaInicio ASC";

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id); // Vincular el ID del usuario logueado
$stmt->execute();
$result = $stmt->get_result();

// Mostrar los resultados en la tabla
if ($result->num_rows > 0) {
    $fechaAnterior = null;
    
    while ($row = $result->fetch_assoc()) {
        if ($row['estado'] === 'Confirmada' || $row['estado'] === 'Cancelada') {
            $fechaActual = $row['fechaReserva'];
            
            // Si la fecha es diferente, mostrar el separador
            if ($fechaActual !== $fechaAnterior) {
                echo "<tr class='separador-dia'>
                    <td colspan='10' style='background-color:#e0e0e0; font-weight:bold; text-align:center;'>
                        📅 " . strftime("%A %d de %B de %Y", strtotime($fechaActual)) . "
                    </td>
                </tr>";
                $fechaAnterior = $fechaActual;
            }
            
            // Mostrar la fila de datos
            echo "<tr>
                <td>" . htmlspecialchars($row['nombreRecurso']) . "</td>
                <td>" . date('d/m/Y', strtotime($row['fechaReserva'])) . "</td>
                <td>" . date('h:i A', strtotime($row['horaInicio'])) . "</td>
                <td>" . date('h:i A', strtotime($row['horaFin'])) . "</td>
                <td>" . htmlspecialchars($row['nombreUsuario']) . "</td>
                <td>" . htmlspecialchars($row['nombreDocente']) . "</td>
                <td>" . htmlspecialchars($row['asignatura']) . "</td>
                <td>" . htmlspecialchars($row['programa']) . "</td>
                <td>" . htmlspecialchars($row['semestre']) . "</td>
                <td><span class='status-" . strtolower($row['estado']) . "'>" . $row['estado'] . "</span></td>
            </tr>";
        }
    }
} else {
    echo "<tr><td colspan='10' class='sin-reservas'>No hay registros disponibles</td></tr>";
}

// Cerrar la conexión
$stmt->close();
$conn->close();
?>
                 
                 
             </tbody>
         </table>
     </section>

</body>

</html>