<?php
// ControladorVerificar.php - Unifica verificación de código, correo y disponibilidad
header('Content-Type: application/json');
include("../database/conection.php");

date_default_timezone_set('America/Bogota');
$tipo = $_REQUEST['tipo'] ?? $_REQUEST['caso'] ?? '';
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
            }            // Primero verificar si es un videobeam para aplicar validación de cantidad
            $sqlRecurso = "SELECT nombreRecurso, cantidad_disponible FROM recursos WHERE ID_Recurso = ?";
            $stmtRecurso = $conn->prepare($sqlRecurso);
            $stmtRecurso->bind_param("i", $recurso);
            $stmtRecurso->execute();
            $resultRecurso = $stmtRecurso->get_result();
            $recursoInfo = $resultRecurso->fetch_assoc();
            
            $esVideobeam = false;
            $cantidadDisponible = 1; // Por defecto
            
            if ($recursoInfo) {
                $esVideobeam = stripos($recursoInfo['nombreRecurso'], 'videobeam') !== false;
                $cantidadDisponible = $recursoInfo['cantidad_disponible'] ?? 1;
            }
            
            // Contar reservas existentes en el mismo horario
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
            
            $reservasExistentes = $row['total'];
            
            // Para videobeams, verificar si excede la cantidad disponible
            if ($esVideobeam) {
                $disponible = $reservasExistentes < $cantidadDisponible;
                $mensaje = $disponible ? '' : "No hay videobeams disponibles en este horario. Hay {$reservasExistentes} de {$cantidadDisponible} videobeams reservados.";
            } else {
                // Para otros recursos, solo puede haber una reserva
                $disponible = $reservasExistentes == 0;
                $mensaje = $disponible ? '' : 'El recurso no está disponible en este horario.';
            }
            
            $response = [
                'disponible' => $disponible,
                'mismo_registro' => ($registroId !== null),
                'es_videobeam' => $esVideobeam,
                'cantidad_disponible' => $cantidadDisponible,
                'reservas_existentes' => $reservasExistentes,
                'mensaje' => $mensaje            ];
        } catch (Exception $e) {
            $response = [
                'disponible' => false,
                'error' => $e->getMessage()
            ];
        }
        break;
    case 'info_videobeam':
        try {
            $fecha = $_POST['fecha'] ?? '';
            $horaInicio = $_POST['hora_inicio'] ?? '';
            $horaFin = $_POST['hora_fin'] ?? '';
            
            if (!$fecha || !$horaInicio || !$horaFin) {
                throw new Exception('Faltan datos requeridos');
            }
            
            // Obtener todos los videobeams y su disponibilidad
            $sqlVideobeams = "SELECT ID_Recurso, nombreRecurso, cantidad_disponible 
                            FROM recursos 
                            WHERE LOWER(nombreRecurso) LIKE '%videobeam%'";
            $resultVideobeams = $conn->query($sqlVideobeams);
            
            $videobeams = [];
            
            while ($videobeam = $resultVideobeams->fetch_assoc()) {
                // Contar reservas existentes para este videobeam en el horario especificado
                $sqlReservas = "SELECT COUNT(*) as total 
                              FROM registro 
                              WHERE ID_Recurso = ? 
                              AND fechaReserva = ?
                              AND estado = 'Confirmada'
                              AND ((horaInicio < ? AND horaFin > ?) 
                                  OR (horaInicio < ? AND horaFin > ?) 
                                  OR (horaInicio >= ? AND horaFin <= ?))";
                
                $stmtReservas = $conn->prepare($sqlReservas);
                $stmtReservas->bind_param("isssssss", 
                    $videobeam['ID_Recurso'], $fecha, 
                    $horaFin, $horaInicio, 
                    $horaInicio, $horaFin,
                    $horaInicio, $horaFin
                );
                $stmtReservas->execute();
                $resultReservas = $stmtReservas->get_result();
                $reservasRow = $resultReservas->fetch_assoc();
                
                $reservasExistentes = $reservasRow['total'];
                $disponibles = max(0, $videobeam['cantidad_disponible'] - $reservasExistentes);
                
                $videobeams[] = [
                    'id' => $videobeam['ID_Recurso'],
                    'nombre' => $videobeam['nombreRecurso'],
                    'cantidad_total' => $videobeam['cantidad_disponible'],
                    'reservadas' => $reservasExistentes,
                    'disponibles' => $disponibles
                ];
            }
            
            $response = [
                'success' => true,
                'videobeams' => $videobeams
            ];        } catch (Exception $e) {
            $response = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        break;
    case 'obtener_recursos':
        try {
            $sql = "SELECT ID_Recurso as idRecurso, nombreRecurso, cantidad_disponible 
                    FROM recursos 
                    ORDER BY nombreRecurso";
            $result = $conn->query($sql);
            
            $recursos = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $recursos[] = $row;
                }
            }
            
            $response = [
                'success' => true,
                'recursos' => $recursos
            ];
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        break;
    case 'verificar':
        try {
            $fecha = $_POST['fecha'] ?? '';
            $horaInicio = $_POST['hora_inicio'] ?? '';
            $horaFin = $_POST['hora_fin'] ?? '';
            $recurso = $_POST['recurso'] ?? '';
            $usuario = $_POST['usuario'] ?? '';
            
            if (!$fecha || !$horaInicio || !$horaFin || !$recurso || !$usuario) {
                throw new Exception('Faltan datos requeridos');
            }
            
            // Obtener información del recurso
            $sqlRecurso = "SELECT nombreRecurso, cantidad_disponible FROM recursos WHERE ID_Recurso = ?";
            $stmtRecurso = $conn->prepare($sqlRecurso);
            $stmtRecurso->bind_param("i", $recurso);
            $stmtRecurso->execute();
            $resultRecurso = $stmtRecurso->get_result();
            $recursoInfo = $resultRecurso->fetch_assoc();
            
            if (!$recursoInfo) {
                throw new Exception('Recurso no encontrado');
            }
            
            $esVideobeam = stripos($recursoInfo['nombreRecurso'], 'videobeam') !== false;
            $cantidadDisponible = $recursoInfo['cantidad_disponible'] ?? 1;
            
            // Contar reservas existentes en el mismo horario
            $sql = "SELECT COUNT(*) as total 
                    FROM registro 
                    WHERE ID_Recurso = ? 
                    AND fechaReserva = ?
                    AND estado = 'Confirmada'
                    AND ((horaInicio < ? AND horaFin > ?) 
                        OR (horaInicio < ? AND horaFin > ?) 
                        OR (horaInicio >= ? AND horaFin <= ?))";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssss", 
                $recurso, $fecha,
                $horaFin, $horaInicio,
                $horaFin, $horaInicio,
                $horaInicio, $horaFin
            );
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $reservasExistentes = $row['total'];
            
            $disponible = false;
            $mensaje = '';
            
            if ($esVideobeam) {
                $disponiblesActual = $cantidadDisponible - $reservasExistentes;
                $disponible = $disponiblesActual > 0;
                $mensaje = $disponible ? 
                    "Videobeam disponible. {$disponiblesActual} de {$cantidadDisponible} unidades libres." :
                    "Videobeam no disponible. Todas las {$cantidadDisponible} unidades están reservadas.";
            } else {
                $disponible = $reservasExistentes == 0;
                $mensaje = $disponible ? 
                    "Recurso disponible para reserva" :
                    "Recurso no disponible. Ya está reservado en ese horario.";
            }
            
            $response = [
                'disponible' => $disponible,
                'mensaje' => $mensaje,
                'es_videobeam' => $esVideobeam,
                'cantidad_disponible' => $cantidadDisponible,
                'reservas_existentes' => $reservasExistentes,
                'recurso_info' => $recursoInfo
            ];
            
        } catch (Exception $e) {
            $response = [
                'disponible' => false,
                'error' => $e->getMessage()
            ];
        }
        break;
    case 'validar_limite_salas':
        try {
            $usuarioId = $_POST['usuario_id'] ?? '';
            $recursoId = $_POST['recurso_id'] ?? '';
            $fecha = $_POST['fecha'] ?? '';
            $registroExcluir = $_POST['registro_excluir'] ?? null; // Para modificaciones
            $asignaturaId = $_POST['asignatura_id'] ?? $_POST['asignatura'] ?? null;

            if (!$usuarioId || !$recursoId || !$fecha) {
                throw new Exception('Faltan datos requeridos para validación');
            }

            include_once("ControladorRegistro.php");
            if (!isset($conn) || !$conn) {
                include("../database/conection.php");
            }
            if (!isset($conn) || !$conn) {
                throw new Exception('No se pudo establecer la conexión a la base de datos');
            }
            $formData = new stdClass();
            $formData->usuario_id = $usuarioId;
            $formData->recurso_id = $recursoId;
            $formData->fecha = $fecha;
            $formData->registro_excluir = $registroExcluir;
            $formData->asignatura_id = $asignaturaId;

            if (!is_object($formData)) {
                $formData = (object)$formData;
            }

            $response = validarLimiteSalas($conn, $formData);
        } catch (Exception $e) {
            $response = [
                'permitido' => false,
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
