<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Bogota'); // Establece la zona horaria a Bogotá, Colombia.

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['status' => 'error', 'message' => 'Acceso no permitido']);
    exit();
}

session_start();
include("../database/conection.php");

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
    exit();
}

// Recibir datos del formulario
$id_registro = $_POST['id_registro'] ?? null;
$id_usuario = $_SESSION['usuario_id'];
$fecha = $_POST['fecha'];
$horaInicio = $_POST['horaInicio'];
$horaFin = $_POST['horaFin'];
$id_recurso = $_POST['recurso'];
$id_docente_asignatura = $_POST['docente'] ?? null; // Puede venir vacío si no es docente
$semestre = $_POST['semestre'] ?? null; // Semestre seleccionado
$id_programa = $_POST['Programa'] ?? null; // Programa seleccionado

// Validar campos obligatorios
if (!$id_registro || !$id_usuario || !$fecha || !$horaInicio || !$horaFin || !$id_recurso) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios']);
    exit();
}

// Verificar unicidad del ID
$stmt = $conn->prepare("SELECT 1 FROM registro WHERE ID_Registro = ?");
$stmt->bind_param("s", $id_registro);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'El ID de la reserva ya existe, por favor intente de nuevo.']);
    exit();
}
$stmt->close();

// Verificar disponibilidad del recurso
$sql = "SELECT 1 FROM registro 
        WHERE ID_Recurso = ? AND fechaReserva = ? 
        AND ((horaInicio < ? AND horaFin > ?) OR (horaInicio < ? AND horaFin > ?) OR (horaInicio >= ? AND horaFin <= ?)) 
        AND estado != 'Cancelada'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isssssss", $id_recurso, $fecha, $horaFin, $horaFin, $horaInicio, $horaInicio, $horaInicio, $horaFin);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => '⚠️ El recurso no está disponible en ese horario. Intenta con otra hora o recurso.']);
    exit();
}
$stmt->close();

// Insertar la reserva
$sql = "INSERT INTO registro (ID_Registro, ID_Usuario, ID_Recurso, fechaReserva, horaInicio, horaFin, ID_DocenteAsignatura, semestre) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("siisssis", $id_registro, $id_usuario, $id_recurso, $fecha, $horaInicio, $horaFin, $id_docente_asignatura, $semestre);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => '✅ Reserva realizada con éxito']);
} else {
    echo json_encode(['status' => 'error', 'message' => '❌ Error al guardar la reserva']);
}
exit();
?>
