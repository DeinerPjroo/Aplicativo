<?php
header('Content-Type: application/json');
include("../database/conection.php");

date_default_timezone_set('America/Bogota');

try {
    $fecha = $_POST['fecha'] ?? '';
    $horaInicio = $_POST['hora_inicio'] ?? '';
    $horaFin = $_POST['hora_fin'] ?? '';
    $recurso = $_POST['recurso'] ?? '';
    $registroId = $_POST['registro_id'] ?? null;

    if (!$fecha || !$horaInicio || !$horaFin || !$recurso) {
        throw new Exception('Faltan datos requeridos');
    }

    // Validación de margen mínimo de 10 minutos si es para hoy
    $fechaHoy = date('Y-m-d');
    if ($fecha === $fechaHoy) {
        $horaActual = new DateTime(); // Hora actual
        $horaReserva = new DateTime("$fecha $horaInicio");

        // Margen de al menos 10 minutos
        $horaMinima = clone $horaActual;
        $horaMinima->modify('+10 minutes');

        if ($horaReserva < $horaMinima) {
            echo json_encode([
                'disponible' => false,
                'mensaje' => 'Solo puedes apartar con al menos 10 minutos de anticipación'
            ]);
            exit;
        }
    }

    // Verificación de disponibilidad
    $sql = "SELECT COUNT(*) as total 
            FROM registro 
            WHERE ID_Recurso = ? 
            AND fechaReserva = ?
            AND estado = 'Confirmada'
            AND (
                (horaInicio < ? AND horaFin > ?) -- se traslapa al inicio
                OR (horaInicio < ? AND horaFin > ?) -- se traslapa al final
                OR (horaInicio >= ? AND horaFin <= ?) -- completamente contenido
            )";

    if ($registroId) {
        $sql .= " AND ID_Registro != ?";
    }

    $stmt = $conn->prepare($sql);

    if ($registroId) {
        $stmt->bind_param("issssssssi", 
            $recurso, $fecha, 
            $horaFin, $horaInicio, 
            $horaInicio, $horaFin,
            $horaInicio, $horaFin,
            $registroId
        );
    } else {
        $stmt->bind_param("isssssss", 
            $recurso, $fecha, 
            $horaFin, $horaInicio, 
            $horaInicio, $horaFin,
            $horaInicio, $horaFin
        );
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    echo json_encode([
        'disponible' => ($row['total'] == 0),
        'mismo_registro' => ($registroId !== null)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'disponible' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
