<?php
date_default_timezone_set('America/Bogota');
session_start();
include("../database/conection.php");
include("../Controlador/control_De_Rol.php");
checkRole('Administrador');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['registro_id'];
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];
    $estado = $_POST['estado'];

    // Validación básica
    if (!$fecha || !$hora_inicio || !$hora_fin || !$id) {
        echo 'error: Faltan datos';
        exit;
    }

    // Validar anticipación mínima de 10 minutos
    $fechaHoraInicio = new DateTime("$fecha $hora_inicio");
    $ahora = new DateTime();
    $ahora->modify('+10 minutes');

    if ($fecha === date('Y-m-d') && $fechaHoraInicio < $ahora) {
        echo 'error: Solo puedes modificar con al menos 10 minutos de anticipación';
        exit;
    }

    // Obtener el ID_Recurso del registro actual
    $sqlRecurso = "SELECT ID_Recurso FROM registro WHERE ID_Registro = ?";
    $stmtRecurso = $conn->prepare($sqlRecurso);
    $stmtRecurso->bind_param("i", $id);
    $stmtRecurso->execute();
    $resultRecurso = $stmtRecurso->get_result();
    $rowRecurso = $resultRecurso->fetch_assoc();

    if (!$rowRecurso) {
        echo 'error: Registro no encontrado';
        exit;
    }

    $id_recurso = $rowRecurso['ID_Recurso'];

    // Verificar traslapes reales (cruces) para el mismo recurso
    $sqlTraslape = "SELECT COUNT(*) as traslapes FROM registro 
                    WHERE ID_Recurso = ? 
                    AND fechaReserva = ? 
                    AND ID_Registro != ?
                    AND estado = 'Confirmada'
                    AND (
                        horaInicio < ? AND horaFin > ?
                    )";

    $stmt = $conn->prepare($sqlTraslape);
    $stmt->bind_param("isiss", 
        $id_recurso, 
        $fecha, 
        $id,
        $hora_fin, 
        $hora_inicio
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['traslapes'] > 0) {
        echo 'error: El recurso no está disponible en ese horario';
        exit;
    }

    // Actualizar el registro
    $sqlUpdate = "UPDATE registro SET 
                  fechaReserva = ?,
                  horaInicio = ?,
                  horaFin = ?,
                  estado = ?
                  WHERE ID_Registro = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ssssi", 
        $fecha,
        $hora_inicio,
        $hora_fin,
        $estado,
        $id
    );

    if ($stmtUpdate->execute()) {
        echo 'success';
    } else {
        echo 'error: No se pudo actualizar';
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>
