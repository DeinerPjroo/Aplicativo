<?php
include("../database/conection.php");

$idPrograma = $_GET['id_programa'];

$query = "SELECT DISTINCT u.ID_Usuario, u.nombre 
          FROM usuario u 
          INNER JOIN docente_asignatura da ON u.ID_Usuario = da.ID_Usuario
          INNER JOIN asignatura a ON da.ID_Asignatura = a.ID_Asignatura
          WHERE a.ID_Programa = ? AND u.ID_Rol = (SELECT ID_Rol FROM rol WHERE nombreRol = 'Docente')";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $idPrograma);
$stmt->execute();
$result = $stmt->get_result();

$docentes = [];
while ($row = $result->fetch_assoc()) {
    $docentes[] = $row;
}

header('Content-Type: application/json');
echo json_encode($docentes);

$conn->close();
?>
