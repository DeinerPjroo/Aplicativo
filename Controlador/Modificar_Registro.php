<?php
date_default_timezone_set('America/Bogota'); // Establece la zona horaria a Bogotá, Colombia.


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

    // Obtener el ID_Recurso del registro actual
    $sqlRecurso = "SELECT ID_Recurso FROM registro WHERE ID_Registro = ?";
    $stmtRecurso = $conn->prepare($sqlRecurso);
    $stmtRecurso->bind_param("i", $id);
    $stmtRecurso->execute();
    $resultRecurso = $stmtRecurso->get_result();
    $rowRecurso = $resultRecurso->fetch_assoc();
    $id_recurso = $rowRecurso['ID_Recurso'];

    // Verificar traslapes para el mismo recurso
    $sqlTraslape = "SELECT COUNT(*) as traslapes FROM registro 
                    WHERE ID_Recurso = ? 
                    AND fechaReserva = ? 
                    AND ID_Registro != ?
                    AND estado != 'Cancelada'
                    AND (
                        (horaInicio < ? AND horaFin > ?) OR
                        (horaInicio < ? AND horaFin > ?) OR
                        (horaInicio >= ? AND horaFin <= ?)
                    )";

    $stmt = $conn->prepare($sqlTraslape);
    $stmt->bind_param("isississs", 
        $id_recurso, 
        $fecha, 
        $id,
        $hora_fin, 
        $hora_inicio,
        $hora_inicio, 
        $hora_inicio,
        $hora_inicio, 
        $hora_fin
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['traslapes'] > 0) {
        echo 'overlap';
        exit;
    }

    // Si no hay traslapes, actualizar el registro
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
        echo 'error';
    }
    
    $stmt->close();
    $conn->close();
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "ID no proporcionado";
    exit;
}

// Obtener los datos del registro
$sql = "SELECT * FROM registro WHERE ID_Registro = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$registro = $result->fetch_assoc();

if (!$registro) {
    echo "Registro no encontrado";
    exit;
}
?>


