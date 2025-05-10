<?php
include("../database/conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_POST['id_usuario'];
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $id_rol = $_POST['id_rol'];
    $semestre = $_POST['semestre'] ?? null;
    $id_programa = $_POST['id_programa'] ?? null;

    $query = "UPDATE usuario SET nombre = ?, correo = ?, ID_Rol = ?, semestre = ?, Id_Programa = ? WHERE ID_Usuario = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssissi", $nombre, $correo, $id_rol, $semestre, $id_programa, $id_usuario);

    if ($stmt->execute()) {
        header("Location: ../Vista/Administrar_Usuarios.php?success=Usuario modificado correctamente");
    } else {
        header("Location: ../Vista/Administrar_Usuarios.php?error=Error al modificar usuario");
    }

    $stmt->close();
    $conn->close();
}
?>
