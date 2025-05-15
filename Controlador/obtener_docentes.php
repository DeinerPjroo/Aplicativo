<?php 
// Controlador/obtener_docentes.php
include("../database/conection.php");   
date_default_timezone_set('America/Bogota'); // Establece la zona horaria a Bogotá, Colombia.   

// Determinar si los datos vienen por GET o POST
if (isset($_POST['id_programa'])) {
    $idPrograma = $_POST['id_programa'];
} elseif (isset($_GET['id_programa'])) {
    $idPrograma = $_GET['id_programa'];
} else {
    echo json_encode([]);
    exit;
}

$query = "SELECT DISTINCT u.ID_Usuario, u.nombre
          FROM usuario u
          INNER JOIN docente_asignatura da ON u.ID_Usuario = da.ID_Usuario
          INNER JOIN asignatura a ON da.ID_Asignatura = a.ID_Asignatura
          WHERE a.ID_Programa = ? AND u.ID_Rol = (SELECT ID_Rol FROM rol WHERE nombreRol = 'Docente')
          ORDER BY u.nombre";

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
