<?php
// Incluye el archivo de conexión a la base de datos
include("../database/Conexion.php");

// Verifica si la solicitud es de tipo POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Obtiene los parámetros enviados por POST o asigna un valor vacío si no están definidos
    $fecha = $_POST['fecha'] ?? '';
    $horaInicio = $_POST['horaInicio'] ?? '';
    $horaFin = $_POST['horaFin'] ?? '';
    $id_recurso = $_POST['recurso'] ?? '';

    // Verifica que todos los parámetros requeridos estén presentes
    if ($fecha && $horaInicio && $horaFin && $id_recurso) {
        // Consulta SQL para verificar si existe un cruce de horarios en las reservas
        $sql = "SELECT * FROM registro 
                WHERE ID_Recurso = ?  -- Filtra por el recurso específico
                AND fechaReserva = ?  -- Filtra por la fecha específica
                AND (
                    (horaInicio < ? AND horaFin > ?) OR  -- Verifica si el inicio del nuevo horario se cruza
                    (horaInicio < ? AND horaFin > ?) OR  -- Verifica si el fin del nuevo horario se cruza
                    (horaInicio >= ? AND horaFin <= ?)   -- Verifica si el nuevo horario está completamente dentro de otro
                )
                AND estado != 'Cancelada'";  // Excluye reservas canceladas

        // Prepara la consulta SQL
        $stmt = $conn->prepare($sql);
        // Asocia los parámetros a la consulta preparada
        $stmt->bind_param("isssssss", $id_recurso, $fecha, $horaFin, $horaInicio, $horaInicio, $horaFin, $horaInicio, $horaFin);
        // Ejecuta la consulta
        $stmt->execute();
        // Obtiene el resultado de la consulta
        $result = $stmt->get_result();

        // Devuelve un JSON indicando si el recurso está disponible
        echo json_encode([
            "disponible" => $result->num_rows === 0  // Está disponible si no hay resultados
        ]);
    } else {
        // Devuelve un JSON indicando que faltan parámetros
        echo json_encode([
            "error" => "Faltan parámetros"
        ]);
    }
}
