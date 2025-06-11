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
        break;    case 'docentes':
        $id_programa = $_POST['id_programa'] ?? $_GET['id_programa'] ?? null;
        if ($id_programa) {
            // Obtener docentes del programa seleccionado Y administrativos de esa dependencia
            $sql = "SELECT DISTINCT u.ID_Usuario, u.nombre, r.nombreRol as rol
                    FROM usuario u
                    INNER JOIN rol r ON u.ID_Rol = r.ID_Rol
                    INNER JOIN docente_asignatura da ON u.ID_Usuario = da.ID_Usuario
                    INNER JOIN asignatura a ON da.ID_Asignatura = a.ID_Asignatura
                    WHERE a.ID_Programa = ? AND r.nombreRol = 'Docente'
                    
                    UNION
                    
                    SELECT DISTINCT u.ID_Usuario, u.nombre, r.nombreRol as rol
                    FROM usuario u
                    INNER JOIN rol r ON u.ID_Rol = r.ID_Rol
                    WHERE r.nombreRol = 'Administrativo' AND u.Id_Programa = ?
                    
                    ORDER BY nombre";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $id_programa, $id_programa);
            $stmt->execute();
            $result = $stmt->get_result();
            $docentes = [];
            while ($row = $result->fetch_assoc()) {
                $docentes[] = $row;
            }
            $response = ['status' => 'success', 'data' => $docentes];
            $stmt->close();
        } else {
            // Sin filtro de programa, traer todos los docentes y administrativos
            $sql = "SELECT u.ID_Usuario, u.nombre, r.nombreRol as rol 
                    FROM usuario u 
                    INNER JOIN rol r ON u.ID_Rol = r.ID_Rol 
                    WHERE r.nombreRol IN ('Docente', 'Administrativo') 
                    ORDER BY u.nombre";
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
        }        break;
    case 'rol_usuario':
        $id_usuario = $_POST['id_usuario'] ?? $_GET['id_usuario'] ?? null;
        if ($id_usuario) {
            $sql = "SELECT r.nombreRol FROM usuario u 
                    INNER JOIN rol r ON u.ID_Rol = r.ID_Rol 
                    WHERE u.ID_Usuario = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $id_usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $response = ['status' => 'success', 'data' => ['rol' => $row['nombreRol']]];
            } else {
                $response['message'] = 'Usuario no encontrado';
            }
            $stmt->close();
        } else {
            $response['message'] = 'ID de usuario requerido';
        }
        break;
    default:
        $response['message'] = 'Tipo de obtención no válido';
        break;
}
$conn->close();
echo json_encode($response);
