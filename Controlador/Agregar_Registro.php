<?php
header('Content-Type: application/json');
include("../database/conection.php");

date_default_timezone_set('America/Bogota'); // Establece la zona horaria a Bogotá, Colombia.

// Activar reporte de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log para debug
error_log("Iniciando proceso de registro...");
error_log("POST data: " . print_r($_POST, true));

try {
    // Validar datos requeridos básicos
    $campos_requeridos = ['usuario', 'recurso', 'fecha', 'hora_inicio', 'hora_fin'];
    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            throw new Exception("Campo requerido faltante: {$campo}");
        }
    }

    // Obtener datos básicos
    $usuario = $_POST['usuario'];
    $recurso = $_POST['recurso'];
    $fecha = $_POST['fecha'];
    $horaInicio = $_POST['hora_inicio'];
    $horaFin = $_POST['hora_fin'];
    $estado = $_POST['estado'];

    // Obtener el rol del usuario
    $sql_rol = "SELECT u.id_rol FROM usuario u WHERE u.ID_Usuario = ?";
    $stmt = $conn->prepare($sql_rol);
    $stmt->bind_param("i", $usuario);
    $stmt->execute();
    $result_rol = $stmt->get_result();
    $rol = $result_rol->fetch_assoc()['id_rol'];

    // Si es estudiante (rol = 1), necesitamos procesar docente y programa
    if ($rol == 1 && isset($_POST['docente']) && isset($_POST['programa'])) {
        $docente = $_POST['docente'];
        $programa = $_POST['programa'];

        // Obtener el ID_DocenteAsignatura
        $sql_docente = "SELECT da.ID_DocenteAsignatura 
                       FROM docente_asignatura da 
                       INNER JOIN asignatura a ON da.ID_Asignatura = a.ID_Asignatura
                       WHERE da.ID_Usuario = ? AND a.ID_Programa = ?
                       LIMIT 1";
        
        $stmt = $conn->prepare($sql_docente);
        $stmt->bind_param("ii", $docente, $programa);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $docenteAsignatura = $row['ID_DocenteAsignatura'];
            
            // Insertar con ID_DocenteAsignatura
            $sql = "INSERT INTO registro (ID_Usuario, ID_Recurso, ID_DocenteAsignatura, fechaReserva, horaInicio, horaFin, estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiissss", $usuario, $recurso, $docenteAsignatura, $fecha, $horaInicio, $horaFin, $estado);
        } else {
            throw new Exception("No se encontró la relación docente-asignatura");
        }
    } else {
        // Inserción básica para docentes y administrativos
        $sql = "INSERT INTO registro (ID_Usuario, ID_Recurso, fechaReserva, horaInicio, horaFin, estado) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt->bind_param("iissss", $usuario, $recurso, $fecha, $horaInicio, $horaFin, $estado);
    }
    
    if ($stmt->execute()) {
        error_log("Registro insertado correctamente");
        echo json_encode(['status' => 'success', 'message' => 'Registro creado correctamente']);
    } else {
        throw new Exception("Error en la inserción: " . $stmt->error);
    }

} catch (Exception $e) {
    error_log("Error en Agregar_Registro.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
