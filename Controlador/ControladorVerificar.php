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
            
            if (!$usuarioId || !$recursoId || !$fecha) {
                throw new Exception('Faltan datos requeridos para validación');
            }
            
            // Verificar si el recurso es una sala (no videobeam)
            $sqlRecurso = "SELECT nombreRecurso FROM recursos WHERE ID_Recurso = ?";
            $stmtRecurso = $conn->prepare($sqlRecurso);
            $stmtRecurso->bind_param("i", $recursoId);
            $stmtRecurso->execute();
            $resultRecurso = $stmtRecurso->get_result();
            $recursoInfo = $resultRecurso->fetch_assoc();
            $stmtRecurso->close();
            
            if (!$recursoInfo) {
                throw new Exception('Recurso no encontrado');
            }
            
            $esVideobeam = stripos($recursoInfo['nombreRecurso'], 'videobeam') !== false;
            $esSala = (stripos($recursoInfo['nombreRecurso'], 'sala') !== false ||
                      stripos($recursoInfo['nombreRecurso'], 'aula') !== false ||
                      stripos($recursoInfo['nombreRecurso'], 'laboratorio') !== false ||
                      stripos($recursoInfo['nombreRecurso'], 'lab') !== false) && !$esVideobeam;
            
            // Si no es una sala, permitir la reserva (videobeams y otros recursos tienen sus propias validaciones)
            if (!$esSala) {
                $response = [
                    'permitido' => true,
                    'mensaje' => 'Recurso no está sujeto a límite de salas',
                    'es_sala' => false
                ];
                break;
            }
            
            // Obtener información del usuario (rol y asignaturas si es docente)
            $sqlUsuario = "SELECT u.ID_Usuario, u.nombre, r.nombreRol, u.Id_Programa 
                          FROM usuario u 
                          INNER JOIN rol r ON u.ID_Rol = r.ID_Rol 
                          WHERE u.ID_Usuario = ?";
            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->bind_param("i", $usuarioId);
            $stmtUsuario->execute();
            $resultUsuario = $stmtUsuario->get_result();
            $usuarioInfo = $resultUsuario->fetch_assoc();
            $stmtUsuario->close();
            
            if (!$usuarioInfo) {
                throw new Exception('Usuario no encontrado');
            }
            
            $rol = $usuarioInfo['nombreRol'];
            
            // Solo aplicar límite a Docentes y Administrativos
            if (!in_array($rol, ['Docente', 'Administrativo'])) {
                $response = [
                    'permitido' => true,
                    'mensaje' => 'Rol no sujeto a límite de salas',
                    'es_sala' => true,
                    'rol' => $rol
                ];
                break;
            }
            
            // Calcular el inicio y fin de la semana de la fecha dada
            $fechaObj = new DateTime($fecha);
            $inicioSemana = clone $fechaObj;
            $inicioSemana->modify('monday this week'); // Lunes de esta semana
            $finSemana = clone $inicioSemana;
            $finSemana->modify('+6 days'); // Domingo de esta semana
            
            $fechaInicioSemana = $inicioSemana->format('Y-m-d');
            $fechaFinSemana = $finSemana->format('Y-m-d');
            
            // Calcular límite base (3 reservas)
            $limiteBase = 3;
            $multiplicadorAsignaturas = 1;
            
            // Si es docente, calcular multiplicador por asignaturas
            if ($rol === 'Docente') {
                $sqlAsignaturas = "SELECT COUNT(DISTINCT da.ID_Asignatura) as total_asignaturas
                                  FROM docente_asignatura da 
                                  WHERE da.ID_Usuario = ?";
                $stmtAsignaturas = $conn->prepare($sqlAsignaturas);
                $stmtAsignaturas->bind_param("i", $usuarioId);
                $stmtAsignaturas->execute();
                $resultAsignaturas = $stmtAsignaturas->get_result();
                $asignaturasInfo = $resultAsignaturas->fetch_assoc();
                $stmtAsignaturas->close();
                
                $multiplicadorAsignaturas = max(1, $asignaturasInfo['total_asignaturas']);
            }
            
            $limiteTotal = $limiteBase * $multiplicadorAsignaturas;
            
            // Contar reservas de salas existentes en la semana (solo salas, no videobeams)
            $sqlConteoSalas = "SELECT COUNT(*) as total_reservas_salas
                              FROM registro r 
                              INNER JOIN recursos rec ON r.ID_Recurso = rec.ID_Recurso 
                              WHERE r.ID_Usuario = ? 
                              AND r.fechaReserva BETWEEN ? AND ? 
                              AND r.estado = 'Confirmada'
                              AND (LOWER(rec.nombreRecurso) LIKE '%sala%' 
                                   OR LOWER(rec.nombreRecurso) LIKE '%aula%' 
                                   OR LOWER(rec.nombreRecurso) LIKE '%laboratorio%' 
                                   OR LOWER(rec.nombreRecurso) LIKE '%lab%')
                              AND LOWER(rec.nombreRecurso) NOT LIKE '%videobeam%'";
            
            // Excluir registro actual si se está modificando
            if ($registroExcluir) {
                $sqlConteoSalas .= " AND r.ID_Registro != ?";
            }
            
            $stmtConteo = $conn->prepare($sqlConteoSalas);
            if ($registroExcluir) {
                $stmtConteo->bind_param("isss", $usuarioId, $fechaInicioSemana, $fechaFinSemana, $registroExcluir);
            } else {
                $stmtConteo->bind_param("iss", $usuarioId, $fechaInicioSemana, $fechaFinSemana);
            }
            $stmtConteo->execute();
            $resultConteo = $stmtConteo->get_result();
            $conteoInfo = $resultConteo->fetch_assoc();
            $stmtConteo->close();
            
            $reservasActuales = $conteoInfo['total_reservas_salas'];
            $permitido = $reservasActuales < $limiteTotal;
            
            $mensaje = '';
            if (!$permitido) {
                if ($rol === 'Docente') {
                    $mensaje = "Has alcanzado el límite semanal de reservas de salas ({$limiteTotal}). Límite base: {$limiteBase} × {$multiplicadorAsignaturas} asignatura(s) = {$limiteTotal} reservas por semana.";
                } else {
                    $mensaje = "Has alcanzado el límite semanal de reservas de salas ({$limiteTotal} reservas por semana).";
                }
            }
            
            $response = [
                'permitido' => $permitido,
                'es_sala' => true,
                'rol' => $rol,
                'reservas_actuales' => $reservasActuales,
                'limite_total' => $limiteTotal,
                'limite_base' => $limiteBase,
                'multiplicador_asignaturas' => $multiplicadorAsignaturas,
                'semana_inicio' => $fechaInicioSemana,
                'semana_fin' => $fechaFinSemana,
                'mensaje' => $mensaje
            ];
            
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
