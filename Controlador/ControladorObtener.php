<?php
// ControladorObtener.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
include("../database/conection.php");

$tipo = $_REQUEST['tipo'] ?? '';
$response = ['status' => 'error', 'message' => 'Tipo no válido', 'data' => []];

switch ($tipo) {
    case 'asignaturas':
        $id_docente = $_POST['id_docente'] ?? $_GET['id_docente'] ?? null;
        $id_programa = $_POST['id_programa'] ?? $_GET['id_programa'] ?? null;
        if ($id_docente && $id_programa) {
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
            $response = ['status' => 'success', 'data' => $asignaturas];
            $stmt->close();
        } else {
            // Si no hay filtro, devuelve todas las asignaturas
            $sql = "SELECT * FROM asignatura";
            $result = $conn->query($sql);
            if ($result) {
                $asignaturas = [];
                while ($row = $result->fetch_assoc()) {
                    $asignaturas[] = $row;
                }
                $response = ['status' => 'success', 'data' => $asignaturas];
            } else {
                $response['message'] = 'Error al obtener asignaturas: ' . $conn->error;
            }
        }
        break;
    case 'docentes':
        $id_programa = $_POST['id_programa'] ?? $_GET['id_programa'] ?? null;
        if ($id_programa) {
            $sql = "SELECT DISTINCT u.ID_Usuario, u.nombre
                    FROM usuario u
                    INNER JOIN docente_asignatura da ON u.ID_Usuario = da.ID_Usuario
                    INNER JOIN asignatura a ON da.ID_Asignatura = a.ID_Asignatura
                    WHERE a.ID_Programa = ? AND u.ID_Rol = (SELECT ID_Rol FROM rol WHERE nombreRol = 'Docente')
                    ORDER BY u.nombre";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $id_programa);
            $stmt->execute();
            $result = $stmt->get_result();
            $docentes = [];
            while ($row = $result->fetch_assoc()) {
                $docentes[] = $row;
            }
            $response = ['status' => 'success', 'data' => $docentes];
            $stmt->close();
        } else {
            $sql = "SELECT ID_Usuario, nombre FROM usuario WHERE ID_Rol = (SELECT ID_Rol FROM rol WHERE nombreRol = 'Docente') ORDER BY nombre";
            $result = $conn->query($sql);
            if ($result) {
                $docentes = [];
                while ($row = $result->fetch_assoc()) {
                    $docentes[] = $row;
                }
                $response = ['status' => 'success', 'data' => $docentes];
            } else {
                $response['message'] = 'Error al obtener docentes: ' . $conn->error;
            }
        }
        break;
    case 'programas':
        $sql = "SELECT * FROM programa";
        $result = $conn->query($sql);
        if ($result) {
            $programas = [];
            while ($row = $result->fetch_assoc()) {
                $programas[] = $row;
            }
            $response = ['status' => 'success', 'data' => $programas];
        } else {
            $response['message'] = 'Error al obtener programas: ' . $conn->error;
        }
        break;
    case 'recursos':
        $sql = "SELECT * FROM recursos";
        $result = $conn->query($sql);
        if ($result) {
            $recursos = [];
            while ($row = $result->fetch_assoc()) {
                $recursos[] = $row;
            }
            $response = ['status' => 'success', 'data' => $recursos];
        } else {
            $response['message'] = 'Error al obtener recursos: ' . $conn->error;
        }
        break;
    default:
        $response['message'] = 'Tipo de obtención no válido';
        break;
}
$conn->close();
echo json_encode($response);
