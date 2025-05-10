

<?php
include("../database/conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_POST['id_usuario'];

    $query = "DELETE FROM usuario WHERE ID_Usuario = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_usuario);

    if ($stmt->execute()) {
        header("Location: ../Vista/Administrar_Usuarios.php?success=Usuario eliminado correctamente");
    } else {
        header("Location: ../Vista/Administrar_Usuarios.php?error=Error al eliminar usuario");
    }

    $stmt->close();
    $conn->close();
}
?>