<?php
// Incluye el archivo de conexión a la base de datos.
include("../database/conexion.php");

// Incluye el archivo que contiene la función para verificar el rol del usuario.
include("../Controlador/control_De_Rol.php");

// Verifica que el usuario tenga el rol de 'Administrador', de lo contrario, lo redirige.
checkRole('Administrador');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Metadatos y enlaces a estilos -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="../css/Style.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>

<body class="Registro">

    <?php
    // Incluye la barra lateral de navegación.
    include("../Vista/Sidebar.html");
    ?>

    <section class="Encabezado">
        <!-- Encabezado de la página -->
        <h1>
            <center></center>
        </h1>
    </section>

    <section class="Table">
        <div class="contenedor-reservas">
            <h2>Registros</h2>
            <table class="tabla-reservas">
                <thead>
                    <!-- Encabezados de la tabla -->
                    <tr>
                        <th>Recurso</th>
                        <th>Fecha</th>
                        <th>Hora Inicio</th>
                        <th>Hora Fin</th>
                        <th>Nombre Usuario</th>
                        <th>Correo</th>
                        <th>Nombre Docente</th>
                        <th>Asignatura</th>
                        <th>Programa</th>
                        <th>Semestre</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Consulta SQL para obtener los registros de reservas con sus relaciones.
                    $sql = "SELECT 
                    r.ID_Registro, -- Asegúrate de incluir el identificador de la reserva.
                    r.fechaReserva,
                    r.horaInicio,
                    r.horaFin,
                    rc.nombreRecurso,
                    u.nombre AS nombreUsuario,
                    u.correo AS correoUsuario, -- Incluye el correo del usuario.
                    COALESCE(doc.nombre, 'Sin docente') AS nombreDocente, -- Si no hay docente, muestra 'Sin docente'.
                    COALESCE(asig.nombreAsignatura, 'Sin asignatura') AS asignatura, -- Si no hay asignatura, muestra 'Sin asignatura'.
                    COALESCE(pr.nombrePrograma, 'Sin programa') AS programa, -- Si no hay programa, muestra 'Sin programa'.
                    CASE 
                        WHEN u.ID_Rol = 1 THEN COALESCE(u.semestre, 'Sin semestre') -- Si el usuario es estudiante, muestra el semestre.
                        ELSE 'No aplica' -- Si no es estudiante, muestra 'No aplica'.
                    END AS semestre,
                    r.estado -- Estado de la reserva.
                FROM registro r
                LEFT JOIN usuario u ON r.ID_Usuario = u.ID_Usuario -- Relación con la tabla de usuarios.
                LEFT JOIN recursos rc ON r.ID_Recurso = rc.ID_Recurso -- Relación con la tabla de recursos.
                LEFT JOIN docente_asignatura da ON r.ID_DocenteAsignatura = da.ID_DocenteAsignatura -- Relación con docente_asignatura.
                LEFT JOIN usuario doc ON da.ID_Usuario = doc.ID_Usuario -- Relación con la tabla de usuarios para obtener el docente.
                LEFT JOIN asignatura asig ON da.ID_Asignatura = asig.ID_Asignatura -- Relación con la tabla de asignaturas.
                LEFT JOIN programa pr ON asig.ID_Programa = pr.ID_Programa -- Relación con la tabla de programas.
                ORDER BY r.fechaReserva DESC, r.horaInicio ASC"; // Ordena por fecha y hora de inicio.

                    // Ejecuta la consulta y obtiene los resultados.
                    $result = $conn->query($sql);

                    // Verifica si hay registros disponibles.
                    if ($result->num_rows > 0) {
                        // Itera sobre los resultados y los muestra en la tabla.
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                            <td>" . htmlspecialchars($row['nombreRecurso']) . "</td> <!-- Nombre del recurso -->
                            <td>" . date('d/m/Y', strtotime($row['fechaReserva'])) . "</td> <!-- Fecha de la reserva -->
                            <td>" . date('h:i A', strtotime($row['horaInicio'])) . "</td> <!-- Hora de inicio -->
                            <td>" . date('h:i A', strtotime($row['horaFin'])) . "</td> <!-- Hora de fin -->
                            <td>" . htmlspecialchars($row['nombreUsuario']) . "</td> <!-- Nombre del usuario -->
                            <td>" . htmlspecialchars($row['correoUsuario']) . "</td> <!-- Correo del usuario -->
                            <td>" . htmlspecialchars($row['nombreDocente']) . "</td> <!-- Nombre del docente -->
                            <td>" . htmlspecialchars($row['asignatura']) . "</td> <!-- Nombre de la asignatura -->
                            <td>" . htmlspecialchars($row['programa']) . "</td> <!-- Nombre del programa -->
                            <td>" . htmlspecialchars($row['semestre']) . "</td> <!-- Semestre -->
                            <td><span class='status-" . strtolower($row['estado']) . "'>" . $row['estado'] . "</span></td> <!-- Estado -->
                            <td>
                                <a href='../Controlador/Eliminar_Reserva.php?id=" . $row['ID_Registro'] .
                                "' class='btn-eliminar'><span class='material-symbols-outlined'> 
                                delete_forever
                                </span></a>  </td> 
                            
                        </tr>";
                        }
                    } else {
                        // Si no hay registros, muestra un mensaje en la tabla.
                        echo "<tr><td colspan='10' class='sin-reservas'>No hay registros disponibles</td></tr>";
                    }

                    // Cierra la conexión a la base de datos.
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </section>

</body>

</html>