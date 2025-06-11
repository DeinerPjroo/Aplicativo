<?php
// ControladorFiltrado.php - Filtrar programas y asignaturas según rol del usuario
session_start();
header('Content-Type: application/json');
include("../database/conection.php");

// Verificar autenticación
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_rol'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

$tipo = $_REQUEST['tipo'] ?? '';
$usuario_id = $_SESSION['usuario_id'];
$rol_usuario = $_SESSION['usuario_rol'];

$response = ['status' => 'error', 'message' => 'Tipo no válido', 'data' => []];

switch ($tipo) {
    case 'programas_filtrados':
        try {
            switch ($rol_usuario) {                case 'Docente':
                    // Obtener solo los programas donde el docente tiene asignaturas
                    $sql = "SELECT DISTINCT p.ID_Programa, p.nombrePrograma
                            FROM programa p
                            INNER JOIN asignatura a ON p.ID_Programa = a.ID_Programa
                            INNER JOIN docente_asignatura da ON a.ID_Asignatura = da.ID_Asignatura
                            WHERE da.ID_Usuario = ?
                            ORDER BY p.nombrePrograma";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $usuario_id);
                    break;
                    
                case 'Administrativo':
                    // Obtener solo el programa/dependencia del administrativo
                    $sql = "SELECT p.ID_Programa, p.nombrePrograma
                            FROM programa p
                            INNER JOIN usuario u ON p.ID_Programa = u.Id_Programa
                            WHERE u.ID_Usuario = ? AND p.ID_Programa IS NOT NULL";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $usuario_id);
                    break;
                    
                case 'Estudiante':
                    // Obtener solo el programa del estudiante
                    $sql = "SELECT p.ID_Programa, p.nombrePrograma
                            FROM programa p
                            INNER JOIN usuario u ON p.ID_Programa = u.Id_Programa
                            WHERE u.ID_Usuario = ? AND p.ID_Programa IS NOT NULL";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $usuario_id);
                    break;
                    
                case 'Administrador':
                default:
                    // Los administradores pueden ver todos los programas
                    $sql = "SELECT ID_Programa, nombrePrograma FROM programa ORDER BY nombrePrograma";
                    $stmt = $conn->prepare($sql);
                    break;
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $programas = [];
            while ($row = $result->fetch_assoc()) {
                $programas[] = $row;
            }
            $stmt->close();
            
            $response = ['status' => 'success', 'data' => $programas];
            
        } catch (Exception $e) {
            $response = ['status' => 'error', 'message' => 'Error al obtener programas: ' . $e->getMessage()];
        }
        break;
        
    case 'docentes_filtrados':
        $id_programa = $_POST['id_programa'] ?? $_GET['id_programa'] ?? null;
        
        if (!$id_programa) {
            $response = ['status' => 'error', 'message' => 'ID de programa requerido'];
            break;
        }
        
        try {
            switch ($rol_usuario) {
                case 'Docente':
                    // Los docentes solo pueden seleccionarse a sí mismos dentro de sus programas
                    $sql = "SELECT DISTINCT u.ID_Usuario, u.nombre, r.nombreRol as rol
                            FROM usuario u
                            INNER JOIN rol r ON u.ID_Rol = r.ID_Rol
                            INNER JOIN docente_asignatura da ON u.ID_Usuario = da.ID_Usuario
                            INNER JOIN asignatura a ON da.ID_Asignatura = a.ID_Asignatura
                            WHERE a.ID_Programa = ? AND u.ID_Usuario = ? AND r.nombreRol = 'Docente'
                            ORDER BY u.nombre";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $id_programa, $usuario_id);
                    break;
                    
                case 'Administrativo':
                    // Los administrativos solo pueden seleccionarse a sí mismos si pertenecen a esa dependencia
                    $sql = "SELECT u.ID_Usuario, u.nombre, r.nombreRol as rol
                            FROM usuario u
                            INNER JOIN rol r ON u.ID_Rol = r.ID_Rol
                            WHERE u.ID_Usuario = ? AND u.Id_Programa = ? AND r.nombreRol = 'Administrativo'";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $usuario_id, $id_programa);
                    break;
                    
                case 'Estudiante':
                    // Los estudiantes pueden ver todos los docentes y administrativos de su programa
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
                    $stmt->bind_param("ii", $id_programa, $id_programa);
                    break;
                    
                case 'Administrador':
                default:
                    // Los administradores pueden ver todos los docentes y administrativos del programa
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
                    $stmt->bind_param("ii", $id_programa, $id_programa);
                    break;
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $docentes = [];
            while ($row = $result->fetch_assoc()) {
                $docentes[] = $row;
            }
            $stmt->close();
            
            $response = ['status' => 'success', 'data' => $docentes];
            
        } catch (Exception $e) {
            $response = ['status' => 'error', 'message' => 'Error al obtener docentes: ' . $e->getMessage()];
        }
        break;
        
    case 'asignaturas_filtradas':
        $id_docente = $_POST['id_docente'] ?? $_GET['id_docente'] ?? null;
        $id_programa = $_POST['id_programa'] ?? $_GET['id_programa'] ?? null;
        
        if (!$id_docente || !$id_programa) {
            $response = ['status' => 'error', 'message' => 'ID de docente y programa requeridos'];
            break;
        }
        
        try {
            switch ($rol_usuario) {
                case 'Docente':
                    // Los docentes solo pueden ver sus propias asignaturas
                    if ($id_docente != $usuario_id) {
                        $response = ['status' => 'error', 'message' => 'No autorizado para ver asignaturas de otro docente'];
                        break 2;
                    }
                    $sql = "SELECT a.ID_Asignatura, a.nombreAsignatura
                            FROM docente_asignatura da
                            JOIN asignatura a ON da.ID_Asignatura = a.ID_Asignatura
                            WHERE da.ID_Usuario = ? AND a.ID_Programa = ?
                            ORDER BY a.nombreAsignatura";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $id_docente, $id_programa);
                    break;
                    
                case 'Administrativo':
                    // Los administrativos no tienen asignaturas
                    $response = ['status' => 'success', 'data' => []];
                    break 2;
                    
                case 'Estudiante':
                case 'Administrador':
                default:
                    // Estudiantes y administradores pueden ver las asignaturas del docente seleccionado
                    $sql = "SELECT a.ID_Asignatura, a.nombreAsignatura
                            FROM docente_asignatura da
                            JOIN asignatura a ON da.ID_Asignatura = a.ID_Asignatura
                            WHERE da.ID_Usuario = ? AND a.ID_Programa = ?
                            ORDER BY a.nombreAsignatura";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $id_docente, $id_programa);
                    break;
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $asignaturas = [];
            while ($row = $result->fetch_assoc()) {
                $asignaturas[] = $row;
            }
            $stmt->close();
            
            $response = ['status' => 'success', 'data' => $asignaturas];
            
        } catch (Exception $e) {
            $response = ['status' => 'error', 'message' => 'Error al obtener asignaturas: ' . $e->getMessage()];
        }
        break;
        
    default:
        $response = ['status' => 'error', 'message' => 'Tipo de consulta no válido'];
        break;
}

echo json_encode($response);
?>
