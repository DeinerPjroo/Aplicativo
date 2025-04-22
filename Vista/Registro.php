<?php
include("../database/conexion.php");
include("../Controlador/control_De_Rol.php");

checkRole('Administrador');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="../css/Style.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>

<body class="Registro">

<?php include("../Vista/Sidebar.html"); ?>

<section class="Encabezado">
    <h1><center>Registros</center></h1>
</section>

<section class="Table">
    <div class="contenedor-reservas">
        <h2>Registros</h2>
        <table class="tabla-reservas">
            <thead>
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
                $sql = "SELECT 
                    r.fechaReserva,
                    r.horaInicio,
                    r.horaFin,
                    rc.nombreRecurso,
                    u.nombre AS nombreUsuario,
                    COALESCE(doc.nombre, 'NN') AS nombreDocente,
                    COALESCE(asig.nombreAsignatura, 'NN') AS asignatura,
                    COALESCE(pr.nombrePrograma, 'NN') AS programa,
                    CASE 
                        WHEN u.ID_Rol = 1 THEN COALESCE(r.semestre, 'NN')
                        ELSE 'NN'
                    END AS semestre,
                    r.estado
                FROM registro r
                JOIN usuario u ON r.ID_Usuario = u.ID_Usuario
                LEFT JOIN recursos rc ON r.ID_Recurso = rc.ID_Recurso
                LEFT JOIN docente_asignatura da ON r.ID_DocenteAsignatura = da.ID_DocenteAsignatura
                LEFT JOIN usuario doc ON da.ID_Usuario = doc.ID_Usuario
                LEFT JOIN asignatura asig ON da.ID_Asignatura = asig.ID_Asignatura
                LEFT JOIN programa pr ON asig.ID_Programa = pr.ID_Programa";

                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
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
                } else {
                    echo "<tr><td colspan='10' class='sin-reservas'>No hay registros disponibles</td></tr>";
                }

                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</section>

</body>
</html>
