<?php
// ControladorUsuario.php
header('Content-Type: application/json');
include("../database/conection.php");

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {
    case 'agregar':
        // --- Lógica de Agregar_Usuario.php ---
        ob_start();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
            break;
        }
        // --- DEBUG: Guardar POST recibido en log ---
        file_put_contents(__DIR__ . '/../debug_post.log', date('Y-m-d H:i:s') . "\n" . print_r($_POST, true) . "\n\n", FILE_APPEND);
        // --- FIN DEBUG ---
        $id_usuario = isset($_POST['id_usuario']) ? $_POST['id_usuario'] : null;
        $codigo_u = $_POST['codigo_u'];
        $nombre = $_POST['nombre'];
        $correo = $_POST['correo'];
        $telefono = isset($_POST['telefono']) ? $_POST['telefono'] : '';
        $id_rol = $_POST['id_rol'];
        $contraseña = isset($_POST['contraseña']) ? $_POST['contraseña'] : '';
        $es_estudiante = ($id_rol == '1');
        // Corregir: si id_programa es string vacío, ponerlo como null
        $id_programa = isset($_POST['id_programa']) && $_POST['id_programa'] !== '' ? $_POST['id_programa'] : null; // <-- SIEMPRE OBTENER
        if (empty($codigo_u) || empty($nombre) || empty($correo) || empty($id_rol)) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Por favor, complete todos los campos obligatorios.']);
            break;
        }
        if ($es_estudiante) {
            $semestre = isset($_POST['semestre']) ? $_POST['semestre'] : '';
            if (empty($semestre) || empty($id_programa)) {
                ob_end_clean();
                echo json_encode(['status' => 'error', 'message' => 'Para usuarios estudiantes, el programa y semestre son obligatorios.']);
                break;
            }
        } else {
            $semestre = null;
        }
        $response = ['status' => 'error', 'message' => ''];
        try {
            $verificarQuery = "SELECT ID_Usuario FROM usuario WHERE codigo_u = ?";
            $stmt = $conn->prepare($verificarQuery);
            $stmt->bind_param("s", $codigo_u);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $response['message'] = 'El código de usuario ya existe';
                ob_end_clean();
                echo json_encode($response);
                break;
            }
            $verificarCorreoQuery = "SELECT ID_Usuario FROM usuario WHERE correo = ?";
            $stmt = $conn->prepare($verificarCorreoQuery);
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $response['message'] = 'El correo electrónico ya está registrado';
                ob_end_clean();
                echo json_encode($response);
                break;
            }
            $contraseña_hash = password_hash($contraseña, PASSWORD_DEFAULT);
            $insertQuery = "INSERT INTO usuario (codigo_u, nombre, correo, telefono, contraseña, id_rol, Id_Programa, semestre) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("sssssiis", $codigo_u, $nombre, $correo, $telefono, $contraseña_hash, $id_rol, $id_programa, $semestre);
            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Usuario agregado correctamente';
            } else {
                $response['message'] = 'Error al agregar usuario: ' . $conn->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
        }
        ob_end_clean();
        echo json_encode($response);
        break;
    case 'modificar':
        // --- Lógica de Modificar_Usuario.php ---
        ob_start();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
            break;
        }
        // --- DEBUG: Guardar POST recibido en log ---
        file_put_contents(__DIR__ . '/../debug_post.log', date('Y-m-d H:i:s') . "\n" . print_r($_POST, true) . "\n\n", FILE_APPEND);
        // --- FIN DEBUG ---
        $id_usuario = isset($_POST['id_usuario']) ? $_POST['id_usuario'] : '';
        $codigo_u = isset($_POST['codigo_u']) ? $_POST['codigo_u'] : '';
        $nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
        $correo = isset($_POST['correo']) ? $_POST['correo'] : '';
        $telefono = isset($_POST['telefono']) ? $_POST['telefono'] : '';
        $id_rol = isset($_POST['id_rol']) ? $_POST['id_rol'] : '';
        $contraseña = isset($_POST['contraseña']) ? $_POST['contraseña'] : '';
        $es_estudiante = ($id_rol == '1');
        // Corregir: si id_programa es string vacío, ponerlo como null
        $id_programa = isset($_POST['id_programa']) && $_POST['id_programa'] !== '' ? $_POST['id_programa'] : null; // <-- SIEMPRE OBTENER
        if (empty($id_usuario) || empty($codigo_u) || empty($nombre) || empty($correo) || empty($id_rol)) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Por favor, complete todos los campos obligatorios.']);
            break;
        }
        if ($es_estudiante) {
            $semestre = isset($_POST['semestre']) ? $_POST['semestre'] : '';
            if (empty($semestre) || empty($id_programa)) {
                ob_end_clean();
                echo json_encode(['status' => 'error', 'message' => 'Para usuarios estudiantes, el programa y semestre son obligatorios.']);
                break;
            }
        } else {
            $semestre = null;
        }
        $response = ['status' => 'error', 'message' => ''];
        try {
            $verificarQuery = "SELECT ID_Usuario FROM usuario WHERE codigo_u = ? AND ID_Usuario != ?";
            $stmt = $conn->prepare($verificarQuery);
            $stmt->bind_param("si", $codigo_u, $id_usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $response['message'] = 'El código de usuario ya está en uso por otro usuario';
                ob_end_clean();
                echo json_encode($response);
                break;
            }
            $verificarCorreoQuery = "SELECT ID_Usuario FROM usuario WHERE correo = ? AND ID_Usuario != ?";
            $stmt = $conn->prepare($verificarCorreoQuery);
            $stmt->bind_param("si", $correo, $id_usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $response['message'] = 'El correo electrónico ya está en uso por otro usuario';
                ob_end_clean();
                echo json_encode($response);
                break;
            }
            if (!empty($contraseña)) {
                $contraseña_hash = password_hash($contraseña, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE usuario SET codigo_u = ?, nombre = ?, correo = ?, telefono = ?, contraseña = ?, id_rol = ?, Id_Programa = ?, semestre = ? WHERE ID_Usuario = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("sssssiisi", $codigo_u, $nombre, $correo, $telefono, $contraseña_hash, $id_rol, $id_programa, $semestre, $id_usuario);
            } else {
                $updateQuery = "UPDATE usuario SET codigo_u = ?, nombre = ?, correo = ?, telefono = ?, id_rol = ?, Id_Programa = ?, semestre = ? WHERE ID_Usuario = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("ssssiiis", $codigo_u, $nombre, $correo, $telefono, $id_rol, $id_programa, $semestre, $id_usuario);
            }
            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Usuario actualizado correctamente';
            } else {
                $response['message'] = 'Error al actualizar usuario: ' . $conn->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
        }
        ob_end_clean();
        echo json_encode($response);
        break;
    case 'eliminar':
        // --- Lógica de Eliminar_Usuario.php ---
        $response = ['status' => 'error', 'message' => ''];
        if (isset($_POST['id_usuario']) && !empty($_POST['id_usuario'])) {
            $id_usuario = $_POST['id_usuario'];
            $codigoQuery = "SELECT codigo_u FROM usuario WHERE ID_Usuario = ?";
            $stmt = $conn->prepare($codigoQuery);
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $usuario = $result->fetch_assoc();
                $codigo_usuario = $usuario['codigo_u'];
                $deleteQuery = "DELETE FROM usuario WHERE ID_Usuario = ?";
                $stmt = $conn->prepare($deleteQuery);
                $stmt->bind_param("i", $id_usuario);
                if ($stmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Usuario eliminado correctamente';
                    $response['codigo'] = $codigo_usuario;
                } else {
                    $response['message'] = 'Error al eliminar usuario: ' . $conn->error;
                }
            } else {
                $response['message'] = 'Usuario no encontrado';
            }
        } else {
            $response['message'] = 'ID de usuario no especificado';
        }
        echo json_encode($response);
        break;
    case 'listar':
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        $query = "SELECT u.ID_Usuario, u.codigo_u, u.nombre, u.telefono, p.nombrePrograma AS programa, u.Id_Programa, u.semestre, u.correo, r.nombreRol AS rol, u.id_rol
                 FROM usuario u
                 LEFT JOIN programa p ON u.Id_Programa = p.ID_Programa
                 LEFT JOIN rol r ON u.id_rol = r.id_rol";
        $result = $conn->query($query);
        if (!$result) {
            echo json_encode(['status' => 'error', 'message' => 'Error en la consulta: ' . $conn->error]);
            break;
        }
        $usuarios = [];
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
        $response = ['status' => 'success', 'data' => $usuarios];
        echo json_encode($response);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}
$conn->close();
