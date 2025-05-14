<?php
session_start();
header('Content-Type: application/json');
include("../database/conection.php");

date_default_timezone_set('America/Bogota'); // Establece la zona horaria a Bogotá, Colombia.

include("../Controlador/control_De_Rol.php");
checkRole(['Administrador', 'Docente']); // Solo roles permitidos pueden eliminar reservas.

if (isset($_GET['id'])) {
    $id_reserva = intval($_GET['id']); // Sanitiza el ID de la reserva.

    // Desactiva temporalmente las restricciones de claves foráneas (si es necesario).
    $conn->query("SET FOREIGN_KEY_CHECKS=0");

    // Preparar la consulta usando una sentencia preparada
    $stmt = $conn->prepare("DELETE FROM registro WHERE ID_Registro = ?");
    $stmt->bind_param("i", $id_reserva);

    if ($stmt->execute() && $stmt->affected_rows > 0) { // Verifica si se eliminó alguna fila.
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al eliminar la reserva o la reserva no existe.']);
    }

    // Reactiva las restricciones de claves foráneas.
    $conn->query("SET FOREIGN_KEY_CHECKS=1");

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'No ID provided']);
}

$conn->close();
?>

