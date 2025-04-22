<?php
include("../database/Conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fecha = $_POST['fecha'] ?? '';
    $horaInicio = $_POST['horaInicio'] ?? '';
    $horaFin = $_POST['horaFin'] ?? '';
    $id_recurso = $_POST['recurso'] ?? '';

    if ($fecha && $horaInicio && $horaFin && $id_recurso) {
        // Verifica si existe alguna reserva en ese recurso, fecha y horario que se cruce
        $sql = "SELECT * FROM registro 
                WHERE ID_Recurso = ? 
                AND fechaReserva = ? 
                AND (
                    (horaInicio < ? AND horaFin > ?) OR  -- Se cruza por inicio
                    (horaInicio < ? AND horaFin > ?) OR  -- Se cruza por fin
                    (horaInicio >= ? AND horaFin <= ?)   -- Está completamente dentro
                )
                AND estado != 'Cancelada'";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssss", $id_recurso, $fecha, $horaFin, $horaInicio, $horaInicio, $horaFin, $horaInicio, $horaFin);
        $stmt->execute();
        $result = $stmt->get_result();

        echo json_encode([
            "disponible" => $result->num_rows === 0
        ]);
    } else {
        echo json_encode([
            "error" => "Faltan parámetros"
        ]);
    }
}
