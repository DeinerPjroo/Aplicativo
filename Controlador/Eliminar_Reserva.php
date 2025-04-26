<?php
session_start();
include("../database/Conexion.php");

// Verifica si el usuario tiene permisos para eliminar reservas (por ejemplo, Administrador o Docente).
include("../Controlador/control_De_Rol.php");
checkRole(['Administrador', 'Docente']); // Solo roles permitidos pueden eliminar reservas.

// Verifica si se ha proporcionado un ID de reserva.
if (!isset($_GET['id'])) { // Cambiado a 'id' para coincidir con el enlace en Registro.php
    echo "<script>alert('❌ ID de reserva no proporcionado.'); window.history.back();</script>";
    exit();
}

$id_reserva = intval($_GET['id']); // Sanitiza el ID de la reserva.

// Desactiva temporalmente las restricciones de claves foráneas (si es necesario).
$conn->query("SET FOREIGN_KEY_CHECKS=0");

// Consulta para eliminar la reserva.
$sql = "DELETE FROM registro WHERE ID_Registro = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_reserva);

if ($stmt->execute() && $stmt->affected_rows > 0) { // Verifica si se eliminó alguna fila.
    echo "<script>alert('✅ Reserva eliminada con éxito.'); window.location.href='../Vista/Registro.php';</script>";
} else {
    echo "<script>alert('❌ Error al eliminar la reserva o la reserva no existe.'); window.history.back();</script>";
}

// Reactiva las restricciones de claves foráneas.
$conn->query("SET FOREIGN_KEY_CHECKS=1");

$stmt->close();
$conn->close();
?>

