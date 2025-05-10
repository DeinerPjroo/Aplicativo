<?php
date_default_timezone_set('America/Bogota');

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

// Capturar los datos enviados desde el formulario
$semestre = $_POST['semestre'] ?? null; // Semestre seleccionado
$id_programa = $_POST['Programa'] ?? null; // Programa seleccionado
$id_docente_asignatura = $_POST['docente'] ?? null; // Docente seleccionado

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
    header("Location: ../Vista/Nueva_Reserva_Docente.php?status=warning&message=⚠️ El recurso no está disponible en ese horario. Intenta con otra hora o recurso.");
    exit();
}

// Insertar la reserva con los datos adicionales
$sql = "INSERT INTO registro (ID_Usuario, ID_Recurso, fechaReserva, horaInicio, horaFin, ID_DocenteAsignatura, semestre) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iisssis", $id_usuario, $id_recurso, $fecha, $horaInicio, $horaFin, $id_docente_asignatura, $semestre);

if ($stmt->execute()) {
    header("Location: ../Vista/Nueva_Reserva_Docente.php?status=success&message=✅ Reserva realizada con éxito");
} else {
    header("Location: ../Vista/Nueva_Reserva_Docente.php?status=error&message=❌ Error al guardar la reserva");
}
exit();
?>
