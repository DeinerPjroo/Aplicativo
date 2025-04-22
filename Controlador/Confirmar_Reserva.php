<?php
session_start();
include("../database/Conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id_reserva'])) {
    $id_reserva = $_POST['id_reserva'];
    $usuario_id = $_SESSION['usuario_id'];

    // Verificar que la reserva le pertenece al usuario
    $verificacion = $conn->prepare("SELECT * FROM registro WHERE ID_Registro = ? AND ID_Usuario = ?");
    $verificacion->bind_param("ii", $id_reserva, $usuario_id);
    $verificacion->execute();
    $resultado = $verificacion->get_result();

    if ($resultado->num_rows === 0) {
        header("Location: ../Vista/Reserva_Usuario.php?error=nopermitido");
        exit();
    }

    // Confirmar la reserva
    $sql = "UPDATE registro SET estado = 'Confirmada' WHERE ID_Registro = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_reserva);

    if ($stmt->execute()) {
        header("Location: ../Vista/Reservas_Usuarios.php?msg=confirmada");
    } else {
        header("Location: ../Vista/Reservas_Usuarios.php?error=db");
    }
} else {
    header("Location: ../Vista/Reservas_Usuarios.php?error=nopermitido");
}
