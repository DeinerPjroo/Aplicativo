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
        $nombreUsuario = trim($_POST['nombreUsuario'] ?? '');
        $correoUsuario = trim($_POST['correoUsuario'] ?? '');
        $telefonoUsuario = isset($_POST['telefonoUsuario']) ? trim($_POST['telefonoUsuario']) : null;
        $errores = [];
        if (empty($nombreUsuario)) {
            $errores[] = "El nombre no puede estar vacío";
        }
        if (empty($correoUsuario)) {
            $errores[] = "El correo no puede estar vacío";
        } elseif (!filter_var($correoUsuario, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El formato del correo electrónico no es válido";
        }
        $stmt = $conn->prepare("SELECT ID_Usuario FROM usuario WHERE correo = ? AND ID_Usuario != ?");
        $stmt->bind_param("si", $correoUsuario, $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errores[] = "El correo electrónico ya está en uso por otro usuario";
        }
        if (!empty($errores)) {
            $response = ['status' => 'error', 'errores' => $errores];
            break;
        }
        $sql = "UPDATE usuario SET nombre = ?, correo = ?";
        $params = "ss";
        $paramValues = [$nombreUsuario, $correoUsuario];
        if (!empty($telefonoUsuario)) {
            $sql .= ", telefono = ?";
            $params .= "s";
            $paramValues[] = $telefonoUsuario;
        }
        $sql .= " WHERE ID_Usuario = ?";
        $params .= "i";
        $paramValues[] = $usuario_id;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($params, ...$paramValues);
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Datos actualizados correctamente'];
        } else {
            $response = ['status' => 'error', 'message' => 'Error al actualizar los datos: ' . $conn->error];
        }
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
