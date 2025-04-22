<?php
session_start();
include("../database/Conexion.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../Vista/Login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['id_reserva'])) {
    $id_reserva = $_POST['id_reserva'];

    // Cambiar estado de la reserva a "Cancelada"
    $sql = "UPDATE registro SET estado = 'Cancelada' WHERE ID_Registro = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_reserva);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Reserva cancelada correctamente.'); window.location.href='../Vista/Inicio_Docente.php';</script>";
    } else {
        echo "<script>alert('❌ No se pudo cancelar la reserva.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('❗Solicitud no válida'); window.history.back();</script>";
}
?>
