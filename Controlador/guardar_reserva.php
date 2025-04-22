<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "<script>alert('Acceso no permitido'); window.location.href='../Vista/Nueva_Reserva_Usuario.php';</script>";
    exit();
}

session_start();
include("../database/Conexion.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: Login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$fecha = $_POST['fecha'];
$horaInicio = $_POST['horaInicio'];
$horaFin = $_POST['horaFin'];
$id_recurso = $_POST['recurso'];
$id_docente_asignatura = $_POST['docente_asignatura']; // puede venir vacío si no es docente

// Verificar si el recurso ya está reservado en ese rango de fecha y hora
$sql = "SELECT * FROM registro 
        WHERE ID_Recurso = ? AND fechaReserva = ? 
        AND ((horaInicio < ? AND horaFin > ?) OR (horaInicio < ? AND horaFin > ?) OR (horaInicio >= ? AND horaFin <= ?)) 
        AND estado != 'Cancelada'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isssssss", $id_recurso, $fecha, $horaFin, $horaFin, $horaInicio, $horaInicio, $horaInicio, $horaFin);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Recurso ocupado
    echo "<script>alert('⚠️ El recurso no está disponible en ese horario. Intenta con otra hora o recurso.'); window.history.back();</script>";
    exit();
}

// Insertar la reserva
$sql = "INSERT INTO registro (ID_Usuario, ID_Recurso, fechaReserva, horaInicio, horaFin, ID_DocenteAsignatura) 
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iisssi", $id_usuario, $id_recurso, $fecha, $horaInicio, $horaFin, $id_docente_asignatura);

if ($stmt->execute()) {
    echo "<script>alert('✅ Reserva realizada con éxito'); window.location.href='../Vista/Inicio_Docente.php';</script>";
} else {
    echo "<script>alert('❌ Error al guardar la reserva'); window.history.back();</script>";
}
?>
