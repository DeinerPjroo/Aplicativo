<?php
date_default_timezone_set('America/Bogota');

include("../database/conection.php");
include("../Controlador/control_De_Rol.php");
checkRole('Administrador');

$id = $_POST['id'];
$fecha = $_POST['fechaReserva'];
$horaInicio = $_POST['horaInicio'];
$horaFin = $_POST['horaFin'];
$estado = $_POST['estado'];

if (!in_array($estado, ['Confirmada', 'Cancelada'])) {
    echo "Estado no válido.";
    exit();
}

$sql = "UPDATE registro SET fechaReserva = ?, horaInicio = ?, horaFin = ?, estado = ? WHERE ID_Registro = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $fecha, $horaInicio, $horaFin, $estado, $id);

if ($stmt->execute()) {
    header("Location: ../Vista/Registro.php");
} else {
    echo "Error al actualizar: " . $conn->error;
}
