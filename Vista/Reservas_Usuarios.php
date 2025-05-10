<?php


date_default_timezone_set('America/Bogota');
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain.1252');


include("../Controlador/control_De_Rol.php");
checkRole(['Docente', 'Estudiante', 'Administrador', 'Administrativo']); // Allow both roles

$role = getUserRole(); // Asegúrate de que esta función devuelve el rol del usuario actual
// Incluir conexión a la base de datos
include("../database/Conexion.php");

// Actualizar automáticamente reservas vencidas
$actualizar = "UPDATE registro 
               SET estado = 'Completada' 
               WHERE estado = 'Confirmada' 
               AND CONCAT(fechaReserva, ' ', horaFin) < NOW() 
               AND ID_Usuario = ?";
$updateStmt = $conn->prepare($actualizar);
$updateStmt->bind_param("i", $usuarioId);
$updateStmt->execute();
echo "<!-- Reservas actualizadas: " . $updateStmt->affected_rows . " -->";



// Obtener las reservas actuales del usuario logueado
$usuarioId = $_SESSION['usuario_id'];
$fechaActual = date("Y-m-d");


// Consulta para obtener reservas del usuario actual (pendientes y confirmadas)
$sql = "SELECT r.ID_Registro, r.fechaReserva, r.horaInicio, r.horaFin, 
               rec.nombreRecurso, r.estado, r.creado_en
        FROM registro r
        JOIN recursos rec ON r.ID_Recurso = rec.ID_Recurso
        WHERE r.ID_Usuario = ? 
        AND r.fechaReserva >= ? 
        AND r.estado = 'Confirmada'
        ORDER BY r.fechaReserva ASC, r.horaInicio ASC";


$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $usuarioId, $fechaActual);
$stmt->execute();
$resultado = $stmt->get_result();

// Procesar mensajes de respuesta
$mensaje = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'cancelada') {
        $mensaje = '<div class="alert alert-success">Reserva cancelada correctamente.</div>';
    } elseif ($_GET['msg'] == 'confirmada') {
        $mensaje = '<div class="alert alert-success">Reserva confirmada exitosamente.</div>';
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'db') {
        $mensaje = '<div class="alert alert-danger">Error al procesar la solicitud. Inténtelo nuevamente.</div>';
    } else if ($_GET['error'] == 'nopermitido') {
        $mensaje = '<div class="alert alert-danger">No tienes permiso para realizar esta acción.</div>';
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="webside icon" type="png" href="images/logo.png">
    <title>Mis Reservas</title>
</head>

<body class="Registro">

    <!------------------------------------------------------------------------------------->
    <!--SIDEBAR-->
    <?php
    include("../Vista/Sidebar.php");
    ?>
    <!------------------------------------------------------------------------------------->


    <section class="Main">
        <section class="Encabezado">
            <h1>
                <center>Mis Reservas</center>
            </h1>
        </section>

        <div class="contenedor-reservas">
            <?php
            // Mostrar mensajes de confirmación o error
            if (!empty($mensaje)) {
                echo $mensaje;
            }
            ?>

            <h2>Reservas Activas</h2>


            <?php


            if ($resultado->num_rows > 0) : ?>
                <table class="tabla-reservas">
                    <thead>
                        <tr>
                            <th>Recurso</th>
                            <th>Fecha</th>
                            <th>Hora Inicio</th>
                            <th>Hora Fin</th>
                            <th>Estado</th>
                            <th>Fecha de Solicitud</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $fechaAnterior = null;
                        while ($row = $resultado->fetch_assoc()) :
                            $fechaActual = $row['fechaReserva'];

                            if ($fechaActual !== $fechaAnterior) :
                                echo "<tr class='separador-dia'>
                                    <td colspan='7' style='background-color:#e0e0e0; font-weight:bold; text-align:center;'>
                                        📅 " . strftime("%A %d de %B de %Y", strtotime($fechaActual)) . "
                                    </td>
                                </tr>";
                                $fechaAnterior = $fechaActual;
                            endif;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nombreRecurso']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['fechaReserva'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['horaInicio'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['horaFin'])); ?></td>
                                <td>
                                    <span class="status-<?php echo strtolower($row['estado']); ?>">
                                        <?php echo $row['estado']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['creado_en'])); ?></td>
                                <td>
                                    <?php if ($row['estado'] === 'Confirmada'): ?>
                                        <form method="post" action="../Controlador/Cancelar_Reserva.php" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas cancelar esta reserva?');">
                                            <input type="hidden" name="id_reserva" value="<?php echo $row['ID_Registro']; ?>">
                                            <button type="submit" name="cancelar" class="btn-cancelar">
                                                Cancelar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span>—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="sin-reservas">
                    <p>No tienes reservas activas en este momento</p>
                </div>
            <?php endif; ?>

            <center>
                <button class="btn-agregar" onclick="window.location.href='<?php echo ($role === 'Estudiante') ? '../Vista/Nueva_Reserva_Estudiante.php' : '../Vista/Nueva_Reserva_Docente.php'; ?>'">
                    <img src="../Imagen/Iconos/Mas.svg" alt="" />
                    <span class="btn-text">Crear Nueva Reserva</span>
                </button>
            </center>
        </div>
    </section>
</body>

</html>