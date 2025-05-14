<?php
header('Content-Type: application/json');
include("../database/conection.php");

date_default_timezone_set('America/Bogota');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $usuario = $_POST['usuario'];
    $recurso = $_POST['recurso'];
    $fecha = $_POST['fecha'];
    $horaInicio = $_POST['hora_inicio'];
    $horaFin = $_POST['hora_fin'];
    $estado = 'Confirmada';

    if (!$usuario || !$recurso || !$fecha || !$horaInicio || !$horaFin) {
        throw new Exception('Todos los campos son obligatorios');
    }

    // Validar anticipación mínima de 10 minutos
    $fechaHoraInicio = new DateTime("$fecha $horaInicio");
    $ahora = new DateTime();
    $ahora->modify('+10 minutes');

    if ($fechaHoraInicio < $ahora) {
        throw new Exception('Solo puedes reservar con al menos 10 minutos de anticipación');
    }

    // Verificar disponibilidad
    $sqlVerificar = "SELECT COUNT(*) as conteo 
        FROM registro 
        WHERE ID_Recurso = ? 
        AND fechaReserva = ?
        AND estado = 'Confirmada'
        AND (
            (horaInicio < ? AND horaFin > ?) -- se solapa con el intervalo propuesto
        )";

    $stmt = $conn->prepare($sqlVerificar);
    $stmt->bind_param("isss", $recurso, $fecha, $horaFin, $horaInicio);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['conteo'] > 0) {
        throw new Exception('El recurso no está disponible en ese horario');
    }

    // Insertar registro
    $sql = "INSERT INTO registro (ID_Usuario, ID_Recurso, fechaReserva, horaInicio, horaFin, estado) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissss", $usuario, $recurso, $fecha, $horaInicio, $horaFin, $estado);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Registro agregado correctamente'
        ]);
    } else {
        throw new Exception('Error al insertar el registro');
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
