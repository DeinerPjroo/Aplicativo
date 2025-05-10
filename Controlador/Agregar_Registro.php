<?php
include("../database/conexion.php");

// Verifica si los campos básicos están enviados
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fecha = $_POST['fecha'] ?? '';
    $horaInicio = $_POST['hora_inicio'] ?? '';
    $horaFin = $_POST['hora_fin'] ?? '';
    $estado = $_POST['estado'] ?? '';

    // Puedes ajustar estos valores si los deseas capturar desde el formulario
    $ID_Usuario = 1; // Por defecto, o puedes obtenerlo desde sesión
    $ID_Recurso = 1; // Recurso fijo o agregar al formulario
    $ID_DocenteAsignatura = null;

    // Validación mínima
    if (empty($fecha) || empty($horaInicio) || empty($horaFin) || empty($estado)) {
        echo "missing_fields";
        exit();
    }

    // Prepara la consulta de inserción
    $sql = "INSERT INTO registro 
        (ID_Usuario, ID_Recurso, ID_DocenteAsignatura, fechaReserva, horaInicio, horaFin, estado) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiissss", $ID_Usuario, $ID_Recurso, $ID_DocenteAsignatura, $fecha, $horaInicio, $horaFin, $estado);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    $conn->close();
}
?>
