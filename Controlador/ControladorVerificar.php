<?php
// ControladorVerificar.php - Unifica verificación de código, correo y disponibilidad
header('Content-Type: application/json');
include("../database/conection.php");

date_default_timezone_set('America/Bogota');
$tipo = $_REQUEST['tipo'] ?? '';
$response = [];

switch ($tipo) {
    case 'codigo':
        $response = ['existe' => false];
        if (isset($_POST['codigo_u']) && !empty($_POST['codigo_u'])) {
            $codigo_u = $_POST['codigo_u'];
            $verificarQuery = "SELECT ID_Usuario FROM usuario WHERE codigo_u = ?";
            $stmt = $conn->prepare($verificarQuery);
            $stmt->bind_param("s", $codigo_u);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $response['existe'] = true;
            }
        }
        break;
    case 'correo':
        $response = ['existe' => false];
        if (isset($_POST['correo'])) {
            $correo = $_POST['correo'];
            $idUsuario = isset($_POST['id_usuario']) ? $_POST['id_usuario'] : '';
            if (!empty($idUsuario)) {
                $query = "SELECT ID_Usuario FROM usuario WHERE correo = ? AND ID_Usuario != ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("si", $correo, $idUsuario);
            } else {
                $query = "SELECT ID_Usuario FROM usuario WHERE correo = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $correo);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $response['existe'] = true;
            }
            $stmt->close();
        }
        break;
    case 'disponibilidad':
        try {
            $fecha = $_POST['fecha'] ?? '';
            $horaInicio = $_POST['hora_inicio'] ?? '';
            $horaFin = $_POST['hora_fin'] ?? '';
            $recurso = $_POST['recurso'] ?? '';
            $registroId = $_POST['registro_id'] ?? null;
            if (!$fecha || !$horaInicio || !$horaFin || !$recurso) {
                throw new Exception('Faltan datos requeridos');
            }
            $fechaHoy = date('Y-m-d');
            if ($fecha === $fechaHoy) {
                $horaActual = new DateTime();
                $horaReserva = new DateTime("$fecha $horaInicio");
                $horaMinima = clone $horaActual;
                $horaMinima->modify('+10 minutes');
                if ($horaReserva < $horaMinima) {
                    $response = [
                        'disponible' => false,
                        'mensaje' => 'Solo puedes apartar con al menos 10 minutos de anticipación'
                    ];
                    break;
                }
            }
            $sql = "SELECT COUNT(*) as total 
                    FROM registro 
                    WHERE ID_Recurso = ? 
                    AND fechaReserva = ?
                    AND estado = 'Confirmada'
                    AND ((horaInicio < ? AND horaFin > ?) 
                        OR (horaInicio < ? AND horaFin > ?) 
                        OR (horaInicio >= ? AND horaFin <= ?))";
            if ($registroId) {
                $sql .= " AND ID_Registro != ?";
            }
            $stmt = $conn->prepare($sql);
            if ($registroId) {
                $stmt->bind_param("isssssssi", 
                    $recurso, $fecha, 
                    $horaFin, $horaInicio, 
                    $horaInicio, $horaFin,
                    $horaInicio, $horaFin,
                    $registroId
                );
            } else {
                $stmt->bind_param("isssssss", 
                    $recurso, $fecha, 
                    $horaFin, $horaInicio, 
                    $horaInicio, $horaFin,
                    $horaInicio, $horaFin
                );
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $response = [
                'disponible' => ($row['total'] == 0),
                'mismo_registro' => ($registroId !== null)
            ];
        } catch (Exception $e) {
            $response = [
                'disponible' => false,
                'error' => $e->getMessage()
            ];
        }
        break;
    default:
        $response = ['error' => 'Tipo de verificación no válido'];
        break;
}

$conn->close();
echo json_encode($response);
