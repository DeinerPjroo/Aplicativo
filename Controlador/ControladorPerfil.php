<?php
// ControladorPerfil.php - Unifica actualización de datos de perfil y contraseña
session_start();
include("../database/conection.php");
include("control_De_Rol.php");

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
    exit();
}

$accion = $_REQUEST['accion'] ?? '';
$usuario_id = $_SESSION['usuario_id'];
$response = ['status' => 'error', 'message' => 'Acción no válida'];

switch ($accion) {
    case 'actualizar_datos':
        // Obtener el rol del usuario logueado
        $stmt_rol = $conn->prepare("SELECT r.nombreRol FROM usuario u INNER JOIN rol r ON u.ID_Rol = r.ID_Rol WHERE u.ID_Usuario = ?");
        $stmt_rol->bind_param("i", $usuario_id);
        $stmt_rol->execute();
        $result_rol = $stmt_rol->get_result();
        $rol_usuario = '';
        if ($row_rol = $result_rol->fetch_assoc()) {
            $rol_usuario = $row_rol['nombreRol'];
        }
        $stmt_rol->close();        $nombreUsuario = trim($_POST['nombreUsuario'] ?? '');
        $correoUsuario = trim($_POST['correoUsuario'] ?? '');
        $programaUsuario = trim($_POST['programaUsuario'] ?? '');
        $telefonoUsuario = isset($_POST['telefonoUsuario']) ? trim($_POST['telefonoUsuario']) : null;
        $errores = [];

        // Validaciones según el rol
        if ($rol_usuario === 'Administrador') {
            // Los administradores pueden modificar todos los campos
            if (empty($nombreUsuario)) {
                $errores[] = "El nombre no puede estar vacío";
            }
            if (empty($correoUsuario)) {
                $errores[] = "El correo no puede estar vacío";
            } elseif (!filter_var($correoUsuario, FILTER_VALIDATE_EMAIL)) {
                $errores[] = "El formato del correo electrónico no es válido";
            }
            
            // Verificar que el correo no esté en uso por otro usuario
            $stmt = $conn->prepare("SELECT ID_Usuario FROM usuario WHERE correo = ? AND ID_Usuario != ?");
            $stmt->bind_param("si", $correoUsuario, $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errores[] = "El correo electrónico ya está en uso por otro usuario";
            }
            $stmt->close();        } else {
            // Para otros roles, solo pueden modificar el teléfono
            // Obtener los valores actuales de la base de datos para mantenerlos
            $stmt_actual = $conn->prepare("SELECT nombre, correo, programa FROM usuario WHERE ID_Usuario = ?");
            $stmt_actual->bind_param("i", $usuario_id);
            $stmt_actual->execute();
            $result_actual = $stmt_actual->get_result();
            if ($row_actual = $result_actual->fetch_assoc()) {
                $nombreUsuario = $row_actual['nombre']; // Mantener el nombre actual
                $correoUsuario = $row_actual['correo']; // Mantener el correo actual
                $programaUsuario = $row_actual['programa']; // Mantener el programa actual
            }
            $stmt_actual->close();
        }

        if (!empty($errores)) {
            $response = ['status' => 'error', 'errores' => $errores];
            break;
        }        // Construir la consulta de actualización
        if ($rol_usuario === 'Administrador') {
            // Administradores pueden actualizar todos los campos
            $sql = "UPDATE usuario SET nombre = ?, correo = ?, programa = ?";
            $params = "sss";
            $paramValues = [$nombreUsuario, $correoUsuario, $programaUsuario];
            if (!empty($telefonoUsuario)) {
                $sql .= ", telefono = ?";
                $params .= "s";
                $paramValues[] = $telefonoUsuario;
            }
        } else {
            // Otros roles solo pueden actualizar el teléfono
            $sql = "UPDATE usuario SET telefono = ?";
            $params = "s";
            $paramValues = [$telefonoUsuario ?? ''];
        }

        $sql .= " WHERE ID_Usuario = ?";
        $params .= "i";
        $paramValues[] = $usuario_id;
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($params, ...$paramValues);
        if ($stmt->execute()) {
            $mensaje = ($rol_usuario === 'Administrador') ? 'Datos actualizados correctamente' : 'Teléfono actualizado correctamente';
            $response = ['status' => 'success', 'message' => $mensaje];
        } else {
            $response = ['status' => 'error', 'message' => 'Error al actualizar los datos: ' . $conn->error];
        }
        $stmt->close();
        break;
    case 'actualizar_contraseña':
        $passwordActual = $_POST['passwordActual'] ?? '';
        $passwordNueva = $_POST['passwordNueva'] ?? '';
        $passwordConfirmar = $_POST['passwordConfirmar'] ?? '';
        $errores = [];
        if (empty($passwordActual) || empty($passwordNueva) || empty($passwordConfirmar)) {
            $errores[] = "Todos los campos de contraseña son obligatorios";
        }
        if ($passwordNueva !== $passwordConfirmar) {
            $errores[] = "La nueva contraseña y la confirmación no coinciden";
        }
        if (empty($errores)) {
            $stmt = $conn->prepare("SELECT contraseña FROM usuario WHERE ID_Usuario = ?");
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $hash_guardado = $row['contraseña'];
                if (!password_verify($passwordActual, $hash_guardado)) {
                    $errores[] = "La contraseña actual es incorrecta";
                }
            } else {
                $errores[] = "Error al verificar la contraseña actual";
            }
        }
        if (!empty($errores)) {
            $response = ['status' => 'error', 'errores' => $errores];
            break;
        }
        $hash_nueva_password = password_hash($passwordNueva, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuario SET contraseña = ? WHERE ID_Usuario = ?");
        $stmt->bind_param("si", $hash_nueva_password, $usuario_id);
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Contraseña actualizada correctamente'];
        } else {
            $response = ['status' => 'error', 'message' => 'Error al actualizar la contraseña: ' . $conn->error];
        }
        break;
    default:
        $response = ['status' => 'error', 'message' => 'Acción no válida'];
        break;
}
$conn->close();
echo json_encode($response);
