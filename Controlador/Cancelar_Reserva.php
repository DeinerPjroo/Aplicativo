<?php
// Cancelar_Reserva.php
session_start();
include_once("../database/conection.php");
include_once("control_De_Rol.php");

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../Vista/login.html");
    exit();
}

// Verificar que se recibió el POST con los datos necesarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar']) && isset($_POST['id_reserva'])) {
    $id_reserva = $_POST['id_reserva'];
    $usuario_actual_id = $_SESSION['usuario_id'];
    
    try {        // Verificar que la reserva existe y pertenece al usuario actual
        $sql_verificar = "SELECT r.*, u.nombre, rec.nombreRecurso 
                         FROM registro r 
                         JOIN usuario u ON r.ID_Usuario = u.ID_Usuario 
                         JOIN recursos rec ON r.ID_Recurso = rec.ID_Recurso
                         WHERE r.ID_Registro = ? AND r.ID_Usuario = ? AND r.estado = 'Confirmada'";
        
        $stmt = $conn->prepare($sql_verificar);
        $stmt->bind_param("si", $id_reserva, $usuario_actual_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 0) {
            $_SESSION['mensaje_error'] = "No se encontró la reserva o no tienes permisos para cancelarla.";
            header("Location: ../Vista/Reservas_Usuarios.php");
            exit();
        }
        
        $reserva = $resultado->fetch_assoc();
        
        // Verificar que la reserva no haya pasado (opcional)
        $fecha_hora_reserva = $reserva['fechaReserva'] . ' ' . $reserva['horaInicio'];
        $timestamp_reserva = strtotime($fecha_hora_reserva);
        $timestamp_actual = time();
        
        // Actualizar el estado de la reserva a 'Cancelada'
        $sql_cancelar = "UPDATE registro SET estado = 'Cancelada' WHERE ID_Registro = ?";
        $stmt_cancelar = $conn->prepare($sql_cancelar);
        $stmt_cancelar->bind_param("s", $id_reserva);
        
        if ($stmt_cancelar->execute()) {
            $_SESSION['mensaje_exito'] = "Reserva cancelada exitosamente. Recurso: " . $reserva['nombreRecurso'] . " - Fecha: " . date('d/m/Y', strtotime($reserva['fechaReserva'])) . " - Hora: " . date('h:i A', strtotime($reserva['horaInicio']));
        } else {
            $_SESSION['mensaje_error'] = "Error al cancelar la reserva. Por favor, intenta de nuevo.";
        }
        
        $stmt_cancelar->close();
        $stmt->close();
        
    } catch (Exception $e) {
        $_SESSION['mensaje_error'] = "Error: " . $e->getMessage();
    }
    
    // Redirigir de vuelta a la página de reservas
    header("Location: ../Vista/Reservas_Usuarios.php");
    exit();
    
} else {
    // Si no se recibieron los datos correctos, redirigir con error
    $_SESSION['mensaje_error'] = "Datos incompletos para cancelar la reserva.";
    header("Location: ../Vista/Reservas_Usuarios.php");
    exit();
}

// Cerrar la conexión
$conn->close();
?>
