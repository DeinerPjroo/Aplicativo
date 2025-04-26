<?php
include("../database/Conexion.php");

if (isset($_POST['id_programa'])) {
    $idPrograma = $_POST['id_programa'];

    $sql = "SELECT u.ID_Usuario, u.nombre 
            FROM usuario u
            WHERE u.ID_Programa = ? AND u.ID_Rol = (
                SELECT ID_Rol FROM rol WHERE nombreRol = 'Docente'
            )";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idPrograma);
    $stmt->execute();
    $result = $stmt->get_result();

    $docentes = [];
    while ($row = $result->fetch_assoc()) {
        $docentes[] = $row;
    }

    echo json_encode($docentes);
}
?>
