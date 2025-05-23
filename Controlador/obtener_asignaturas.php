<?php
// obtener_asignaturas.php
include '../database/conection.php';

if (!isset($_POST['id_docente']) || !isset($_POST['id_programa'])) {
    echo json_encode([]);
    exit;
}

$id_docente = intval($_POST['id_docente']);
$id_programa = intval($_POST['id_programa']);

$sql = "SELECT a.ID_Asignatura, a.nombreAsignatura
        FROM docente_asignatura da
        JOIN asignatura a ON da.ID_Asignatura = a.ID_Asignatura
        WHERE da.ID_Usuario = ? AND a.ID_Programa = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $id_docente, $id_programa);
$stmt->execute();
$result = $stmt->get_result();

$asignaturas = [];
while ($row = $result->fetch_assoc()) {
    $asignaturas[] = $row;
}

header('Content-Type: application/json');
echo json_encode($asignaturas);
