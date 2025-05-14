<?php
header('Content-Type: application/json');
include("../database/conection.php");

$fecha = $_POST['fecha'];
$horaInicio = $_POST['hora_inicio'];
$horaFin = $_POST['hora_fin'];
$recursoId = $_POST['recurso'];
$registroId = isset($_POST['registro_id']) ? $_POST['registro_id'] : null;

// Consulta para verificar disponibilidad
$sql = "SELECT COUNT(*) as total FROM registro 
        WHERE ID_Recurso = ? 
        AND fechaReserva = ? 
        AND estado = 'Confirmada'
        AND ((horaInicio BETWEEN ? AND ?) 
        OR (horaFin BETWEEN ? AND ?)
        OR (horaInicio <= ? AND horaFin >= ?))";

if ($registroId) {
    $sql .= " AND ID_Registro != ?";
}

$stmt = $conn->prepare($sql);

if ($registroId) {
    $stmt->bind_param("isssssss", $recursoId, $fecha, $horaInicio, $horaFin, $horaInicio, $horaFin, $horaInicio, $horaFin, $registroId);
} else {
    $stmt->bind_param("isssssss", $recursoId, $fecha, $horaInicio, $horaFin, $horaInicio, $horaFin, $horaInicio, $horaFin);
}

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$response = array(
    'disponible' => ($row['total'] == 0),
    'mismo_registro' => ($registroId !== null)
);

echo json_encode($response);
$conn->close();
?>
