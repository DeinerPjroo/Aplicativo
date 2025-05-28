<?php
// ControladorRegistro.php
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');



session_start();
include_once("../database/conection.php");
include_once("control_De_Rol.php");

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'agregar':
        // Lógica de Agregar_Registro.php con ID personalizado
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            file_put_contents(__DIR__.'/debug_reserva.txt', json_encode($_POST));
            $id_registro = $_POST['id_registro'] ?? null;
            $usuario = $_POST['usuario'] ?? $_POST['id_usuario'] ?? null;
            $recurso = $_POST['recurso'] ?? null;
            $fecha = $_POST['fecha'] ?? null;
            $horaInicio = $_POST['hora_inicio'] ?? $_POST['horaInicio'] ?? null;
            $horaFin = $_POST['hora_fin'] ?? $_POST['horaFin'] ?? null;
            $estado = 'Confirmada';
            // NUEVO: obtener docente y asignatura
            $docente = $_POST['docente'] ?? null;
            $asignatura = $_POST['asignatura'] ?? null;
            $id_docente_asignatura = null;
            if ($docente && $asignatura) {
                // Buscar el ID_DocenteAsignatura correspondiente
                $stmtDA = $conn->prepare("SELECT ID_DocenteAsignatura FROM docente_asignatura WHERE ID_Usuario = ? AND ID_Asignatura = ? LIMIT 1");
                $stmtDA->bind_param("ii", $docente, $asignatura);
                $stmtDA->execute();
                $resDA = $stmtDA->get_result();
                if ($rowDA = $resDA->fetch_assoc()) {
                    $id_docente_asignatura = $rowDA['ID_DocenteAsignatura'];
                }
                $stmtDA->close();
            }
            // ...validaciones existentes...
            if (!$id_registro) { throw new Exception('Falta id_registro'); }
            if (!$usuario) { throw new Exception('Falta usuario'); }
            if (!$recurso) { throw new Exception('Falta recurso'); }
            if (!$fecha) { throw new Exception('Falta fecha'); }
            if (!$horaInicio) { throw new Exception('Falta horaInicio'); }
            if (!$horaFin) { throw new Exception('Falta horaFin'); }
            $fechaHoraInicio = new DateTime("$fecha $horaInicio");
            $ahora = new DateTime();
            $ahora->modify('+10 minutes');
            if ($fechaHoraInicio < $ahora) {
                throw new Exception('Solo puedes reservar con al menos 10 minutos de anticipación');
            }
            // Validar que el ID no exista
            $stmt = $conn->prepare("SELECT 1 FROM registro WHERE ID_Registro = ?");
            $stmt->bind_param("s", $id_registro);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                throw new Exception('El ID de la reserva ya existe, por favor intente de nuevo.');
            }
            $stmt->close();
            // Validar traslape de horario
            $sqlVerificar = "SELECT COUNT(*) as conteo FROM registro WHERE ID_Recurso = ? AND fechaReserva = ? AND estado = 'Confirmada' AND (horaInicio < ? AND horaFin > ? )";
            $stmt = $conn->prepare($sqlVerificar);
            $stmt->bind_param("isss", $recurso, $fecha, $horaFin, $horaInicio);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row['conteo'] > 0) {
                throw new Exception('El recurso no está disponible en ese horario');
            }
            // Insertar con el ID personalizado y el ID_DocenteAsignatura si existe
            $sql = "INSERT INTO registro (ID_Registro, ID_Usuario, ID_Recurso, fechaReserva, horaInicio, horaFin, estado, ID_DocenteAsignatura) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siissssi", $id_registro, $usuario, $recurso, $fecha, $horaInicio, $horaFin, $estado, $id_docente_asignatura);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Registro agregado correctamente']);
            } else {
                throw new Exception('Error al insertar el registro');
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;
    case 'modificar':
        // Lógica de Modificar_Registro.php
        checkRole('Administrador');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['registro_id'];
            $fecha = $_POST['fecha'];
            $hora_inicio = $_POST['hora_inicio'];
            $hora_fin = $_POST['hora_fin'];
            $estado = $_POST['estado'];
            if (!$fecha || !$hora_inicio || !$hora_fin || !$id) {
                echo json_encode(['status' => 'error', 'message' => 'Faltan datos']);
                exit;
            }
            $fechaHoraInicio = new DateTime("$fecha $hora_inicio");
            $ahora = new DateTime();
            $ahora->modify('+10 minutes');
            if ($fecha === date('Y-m-d') && $fechaHoraInicio < $ahora) {
                echo json_encode(['status' => 'error', 'message' => 'Solo puedes modificar con al menos 10 minutos de anticipación']);
                exit;
            }
            $sqlRecurso = "SELECT ID_Recurso FROM registro WHERE ID_Registro = ?";
            $stmtRecurso = $conn->prepare($sqlRecurso);
            $stmtRecurso->bind_param("i", $id);
            $stmtRecurso->execute();
            $resultRecurso = $stmtRecurso->get_result();
            $rowRecurso = $resultRecurso->fetch_assoc();
            if (!$rowRecurso) {
                echo json_encode(['status' => 'error', 'message' => 'Registro no encontrado']);
                exit;
            }
            $id_recurso = $rowRecurso['ID_Recurso'];
            $sqlTraslape = "SELECT COUNT(*) as traslapes FROM registro WHERE ID_Recurso = ? AND fechaReserva = ? AND ID_Registro != ? AND estado = 'Confirmada' AND (horaInicio < ? AND horaFin > ?)";
            $stmt = $conn->prepare($sqlTraslape);
            $stmt->bind_param("isiss", $id_recurso, $fecha, $id, $hora_fin, $hora_inicio);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row['traslapes'] > 0) {
                echo json_encode(['status' => 'error', 'message' => 'El recurso no está disponible en ese horario']);
                exit;
            }
            $sqlUpdate = "UPDATE registro SET fechaReserva = ?, horaInicio = ?, horaFin = ?, estado = ? WHERE ID_Registro = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("ssssi", $fecha, $hora_inicio, $hora_fin, $estado, $id);
            if ($stmtUpdate->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Registro actualizado correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar']);
            }
        }
        break;
    case 'actualizar':
        // Lógica de Actualizar_Registro.php
        checkRole('Administrador');
        $id = $_POST['id'];
        $fecha = $_POST['fechaReserva'];
        $horaInicio = $_POST['horaInicio'];
        $horaFin = $_POST['horaFin'];
        $estado = $_POST['estado'];
        if (!in_array($estado, ['Confirmada', 'Cancelada'])) {
            echo json_encode(['status' => 'error', 'message' => 'Estado no válido.']);
            exit();
        }
        $sql = "UPDATE registro SET fechaReserva = ?, horaInicio = ?, horaFin = ?, estado = ? WHERE ID_Registro = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $fecha, $horaInicio, $horaFin, $estado, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Registro actualizado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar: ' . $conn->error]);
        }
        break;
    case 'cancelar':
        // Lógica de Cancelar_Reserva.php
        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
            exit();
        }
        if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['id_reserva'])) {
            $id_reserva = $_POST['id_reserva'];
            $sql = "UPDATE registro SET estado = 'Cancelada' WHERE ID_Registro = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_reserva);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Reserva cancelada correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo cancelar la reserva.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Solicitud no válida']);
        }
        break;
    case 'eliminar':
        // Lógica de Eliminar_Reserva.php
        checkRole(['Administrador', 'Docente']);
        if (isset($_GET['id'])) {
            $id_reserva = $_GET['id'];
            $conn->query("SET FOREIGN_KEY_CHECKS=0");
            $stmt = $conn->prepare("DELETE FROM registro WHERE ID_Registro = ?");
            $stmt->bind_param("s", $id_reserva); // Cambiado a 's' para ID alfanumérico
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al eliminar la reserva o la reserva no existe.']);
            }
            $conn->query("SET FOREIGN_KEY_CHECKS=1");
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'No ID provided']);
        }
        break;
    case 'guardar':
        // Lógica de guardar_reserva.php
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo json_encode(['status' => 'error', 'message' => 'Acceso no permitido']);
            exit();
        }
        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
            exit();
        }
        $id_registro = $_POST['id_registro'] ?? null;
        $id_usuario = $_SESSION['usuario_id'];
        $fecha = $_POST['fecha'];
        $horaInicio = $_POST['horaInicio'];
        $horaFin = $_POST['horaFin'];
        $id_recurso = $_POST['recurso'];
        $id_docente_asignatura = $_POST['docente'] ?? null;
        $semestre = $_POST['semestre'] ?? null;
        $id_programa = $_POST['Programa'] ?? null;
        $salon = $_POST['salon'] ?? null;
        if (!$id_registro || !$id_usuario || !$fecha || !$horaInicio || !$horaFin || !$id_recurso) {
            echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios']);
            exit();
        }
        $stmt = $conn->prepare("SELECT 1 FROM registro WHERE ID_Registro = ?");
        $stmt->bind_param("s", $id_registro);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'El ID de la reserva ya existe, por favor intente de nuevo.']);
            exit();
        }
        $stmt->close();
        $sql = "SELECT 1 FROM registro WHERE ID_Recurso = ? AND fechaReserva = ? AND ((horaInicio < ? AND horaFin > ?) OR (horaInicio < ? AND horaFin > ?) OR (horaInicio >= ? AND horaFin <= ?)) AND estado != 'Cancelada'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssss", $id_recurso, $fecha, $horaFin, $horaFin, $horaInicio, $horaInicio, $horaInicio, $horaFin);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'El recurso no está disponible en ese horario. Intenta con otra hora o recurso.']);
            exit();
        }
        $stmt->close();
        $sql = "INSERT INTO registro (ID_Registro, ID_Usuario, ID_Recurso, fechaReserva, horaInicio, horaFin, ID_DocenteAsignatura, semestre, salon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siisssiss", $id_registro, $id_usuario, $id_recurso, $fecha, $horaInicio, $horaFin, $id_docente_asignatura, $semestre, $salon);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Reserva realizada con éxito']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar la reserva']);
        }
        exit();
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}
$conn->close();
