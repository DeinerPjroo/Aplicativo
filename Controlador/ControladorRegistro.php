<?php
// ControladorRegistro.php
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');

session_start();
include_once("../database/conection.php");
include_once("control_De_Rol.php");

require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Función para obtener datos del usuario logueado
function obtenerUsuarioLogueado($conn) {
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Usuario no logueado');
    }
    
    $stmt = $conn->prepare("
        SELECT u.ID_Usuario, u.Id_Programa, r.nombreRol 
        FROM usuario u 
        INNER JOIN rol r ON u.ID_Rol = r.ID_Rol 
        WHERE u.ID_Usuario = ?
    ");
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception('Usuario no encontrado');
    }
    
    $userData = $result->fetch_assoc();
    $stmt->close();
    return $userData;
}

// Función para validar límite de reservas de salas
function validarLimiteSalas($conn, $data) {
    try {
        // Asegurar conexión a la base de datos
        if (!isset($conn) || !$conn) {
            include_once __DIR__ . '/../database/conection.php';
        }
        if (!isset($conn) || !$conn) {
            return ['permitido' => false, 'mensaje' => 'No se pudo establecer la conexión a la base de datos'];
        }

        // Convertir a objeto si es necesario
        if (!is_object($data)) {
            $data = (object)$data;
        }

        $usuarioId = $data->usuario_id;
        $recursoId = $data->recurso_id;
        $fecha = $data->fecha;
        $registroExcluir = $data->registro_excluir ?? null;
        $asignaturaId = $data->asignatura_id ?? ($data->asignatura ?? null); // Permitir recibir asignatura explícitamente

        // Verificar que el recurso es una sala (no videobeam)
        $sqlRecurso = "SELECT nombreRecurso FROM recursos WHERE ID_Recurso = ?";
        $stmtRecurso = $conn->prepare($sqlRecurso);
        $stmtRecurso->bind_param("i", $recursoId);
        $stmtRecurso->execute();
        $resultRecurso = $stmtRecurso->get_result();
        $recursoInfo = $resultRecurso->fetch_assoc();
        $stmtRecurso->close();
        
        if (!$recursoInfo) {
            return ['permitido' => false, 'mensaje' => 'Recurso no encontrado'];
        }
        
        $esVideobeam = stripos($recursoInfo['nombreRecurso'], 'videobeam') !== false;
        $esSala = (stripos($recursoInfo['nombreRecurso'], 'sala') !== false ||
                  stripos($recursoInfo['nombreRecurso'], 'aula') !== false ||
                  stripos($recursoInfo['nombreRecurso'], 'laboratorio') !== false ||
                  stripos($recursoInfo['nombreRecurso'], 'lab') !== false) && !$esVideobeam;
        
        // Si no es una sala, permitir la reserva
        if (!$esSala) {
            return ['permitido' => true, 'mensaje' => 'Recurso no está sujeto a límite de salas'];
        }
        
        // Obtener información del usuario
        $sqlUsuario = "SELECT u.ID_Usuario, u.nombre, r.nombreRol 
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
            return ['permitido' => false, 'mensaje' => 'Usuario no encontrado'];
        }
        
        $rol = $usuarioInfo['nombreRol'];
        
        // Solo aplicar límite a Docentes (para administrativos se aplica lógica diferente)
        if ($rol !== 'Docente') {
            // Para administrativos y otros roles, mantener la lógica anterior (sin límite por asignatura/mes)
            return ['permitido' => true, 'mensaje' => 'Rol no sujeto a límite mensual por asignatura'];
        }

        // Validar que se reciba la asignatura
        if (!$asignaturaId) {
            return ['permitido' => false, 'mensaje' => 'No se especificó la asignatura para validar el límite mensual'];
        }

        // Calcular el mes y año de la fecha de la reserva
        $fechaObj = new DateTime($fecha);
        $anio = $fechaObj->format('Y');
        $mes = $fechaObj->format('m');
        $primerDiaMes = "$anio-$mes-01";
        $ultimoDiaMes = (new DateTime("$anio-$mes-01"))->modify('last day of this month')->format('Y-m-d');

        // Contar reservas confirmadas de este docente para esta asignatura en el mes
        $sqlConteo = "SELECT COUNT(*) as total_reservas
            FROM registro r
            INNER JOIN docente_asignatura da ON r.ID_DocenteAsignatura = da.ID_DocenteAsignatura
            WHERE da.ID_Usuario = ?
              AND da.ID_Asignatura = ?
              AND r.fechaReserva BETWEEN ? AND ?
              AND r.estado = 'Confirmada'";
        if ($registroExcluir) {
            $sqlConteo .= " AND r.ID_Registro != ?";
        }

        $stmtConteo = $conn->prepare($registroExcluir ? $sqlConteo : $sqlConteo);
        if ($registroExcluir) {
            $stmtConteo->bind_param("iisss", $usuarioId, $asignaturaId, $primerDiaMes, $ultimoDiaMes, $registroExcluir);
        } else {
            $stmtConteo->bind_param("iiss", $usuarioId, $asignaturaId, $primerDiaMes, $ultimoDiaMes);
        }        $stmtConteo->execute();
        $resultConteo = $stmtConteo->get_result();
        $conteoInfo = $resultConteo->fetch_assoc();
        $stmtConteo->close();
        
        $reservasActuales = $conteoInfo['total_reservas'];
        $limite = 3; // 3 reservas por mes por asignatura
        $permitido = $reservasActuales < $limite;
        
        $mensaje = '';
        if (!$permitido) {
            $mensaje = "Has alcanzado el límite mensual de reservas confirmadas para esta asignatura ($limite por mes).";
        }
        
        return [
            'permitido' => $permitido,
            'reservas_actuales' => $reservasActuales,
            'limite' => $limite,
            'mensaje' => $mensaje
        ];
        
    } catch (Exception $e) {
        return ['permitido' => false, 'mensaje' => 'Error al validar límite: ' . $e->getMessage()];
    }
}

// --- FUNCIÓN: Validar límite de videobeams por bloque horario ---
function validarLimiteVideobeamsPorBloque($conn, $fecha, $horaInicio, $horaFin, $recursoId, $registroExcluir = null) {
    // 1. Verificar si el recurso es videobeam
    $stmt = $conn->prepare("SELECT nombreRecurso FROM recursos WHERE ID_Recurso = ?");
    $stmt->bind_param("i", $recursoId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if (!$row) return [false, 'Recurso no encontrado'];
    if (stripos($row['nombreRecurso'], 'videobeam') === false) return [true, 'OK'];

    // 2. Definir bloques de 45 minutos desde 6:30 a 22:15
    $bloques = [];
    $inicio = strtotime('06:30');
    $fin = strtotime('22:15');
    while ($inicio < $fin) {
        $bloqueFin = $inicio + 45 * 60;
        $bloques[] = [date('H:i', $inicio), date('H:i', $bloqueFin)];
        $inicio = $bloqueFin;
    }

    // 3. Determinar bloques afectados por la reserva
    $bloquesAfectados = [];
    foreach ($bloques as $i => $b) {
        // Si el rango de la reserva se cruza con el bloque
        if (!(
            $horaFin <= $b[0] || // termina antes de que inicie el bloque
            $horaInicio >= $b[1] // inicia después de que termina el bloque
        )) {
            $bloquesAfectados[] = $i;
        }
    }
    if (empty($bloquesAfectados)) return [true, 'OK'];

    // 4. Para cada bloque afectado, contar reservas de videobeam
    foreach ($bloquesAfectados as $i) {
        $b = $bloques[$i];
        $sql = "SELECT COUNT(*) as total FROM registro r 
                INNER JOIN recursos rc ON r.ID_Recurso = rc.ID_Recurso
                WHERE r.fechaReserva = ?
                  AND rc.nombreRecurso LIKE '%videobeam%'
                  AND r.estado = 'Confirmada'
                  AND ((r.horaInicio < ? AND r.horaFin > ?) OR (r.horaInicio < ? AND r.horaFin > ?))";
        $params = [$fecha, $b[1], $b[0], $b[1], $b[0]];
        if ($registroExcluir) {
            $sql .= " AND r.ID_Registro != ?";
            $params[] = $registroExcluir;
        }
        $stmt = $conn->prepare($sql);
        if ($registroExcluir) {
            $stmt->bind_param("ssssss", $fecha, $b[1], $b[0], $b[1], $b[0], $registroExcluir);
        } else {
            $stmt->bind_param("sssss", $fecha, $b[1], $b[0], $b[1], $b[0]);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        if ($row['total'] >= 4) {
            return [false, "No hay videobeams disponibles en el bloque de {$b[0]} a {$b[1]} "];
        }
    }
    return [true, 'OK'];
}

// Función para validar permisos según rol
function validarPermisosReserva($conn, $usuarioLogueado, $programa, $docente, $asignatura) {
    $rol = $usuarioLogueado['nombreRol'];
    $idUsuarioLogueado = $usuarioLogueado['ID_Usuario'];
    $programaUsuarioLogueado = $usuarioLogueado['Id_Programa'];
      switch ($rol) {
        case 'Docente':
            // VALIDACIÓN CRÍTICA: Verificar que el docente seleccionado sea el mismo usuario logueado
            if ($docente && $docente != $idUsuarioLogueado) {
                throw new Exception('Los docentes solo pueden hacer reservas a su propio nombre');
            }
            
            // Verificar que el programa seleccionado sea uno donde el docente tenga asignaturas
            if ($programa) {
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as count 
                    FROM docente_asignatura da
                    INNER JOIN asignatura a ON da.ID_Asignatura = a.ID_Asignatura
                    WHERE da.ID_Usuario = ? AND a.ID_Programa = ?
                ");
                $stmt->bind_param("ii", $idUsuarioLogueado, $programa);
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_assoc()['count'];
                $stmt->close();
                
                if ($count == 0) {
                    throw new Exception('Debe seleccionar un programa donde tenga asignaturas registradas');
                }
            }
            
            // Verificar que la asignatura seleccionada sea suya
            if ($asignatura) {
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as count 
                    FROM docente_asignatura 
                    WHERE ID_Usuario = ? AND ID_Asignatura = ?
                ");
                $stmt->bind_param("ii", $idUsuarioLogueado, $asignatura);
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_assoc()['count'];
                $stmt->close();
                
                if ($count == 0) {
                    throw new Exception('Debe seleccionar una de sus asignaturas registradas');
                }
            }
            break;
            
        case 'Estudiante':
            // Verificar que el programa seleccionado sea el suyo
            if ($programa && $programa != $programaUsuarioLogueado) {
                throw new Exception('Debe seleccionar su programa de estudios');
            }
            
            // Verificar que el docente/asignatura pertenezcan a SU programa
            if ($docente && $asignatura && $programa) {
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as count 
                    FROM docente_asignatura da
                    INNER JOIN asignatura a ON da.ID_Asignatura = a.ID_Asignatura
                    WHERE da.ID_Usuario = ? AND da.ID_Asignatura = ? AND a.ID_Programa = ?
                ");
                $stmt->bind_param("iii", $docente, $asignatura, $programa);
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_assoc()['count'];
                $stmt->close();
                
                if ($count == 0) {
                    throw new Exception('El docente/asignatura seleccionado no pertenece a su programa');
                }
            }
            break;
              case 'Administrativo':
            // VALIDACIÓN CRÍTICA: Verificar que el administrativo seleccionado sea el mismo usuario logueado
            if ($docente && $docente != $idUsuarioLogueado) {
                throw new Exception('Los administrativos solo pueden hacer reservas a su propio nombre');
            }
            
            // Verificar que el programa seleccionado sea el suyo
            if ($programa && $programa != $programaUsuarioLogueado) {
                throw new Exception('Debe seleccionar su dependencia');
            }
            break;
            
        case 'Administrador':
            // Los administradores pueden hacer reservas para cualquier programa/docente/asignatura
            // No aplicar restricciones
            break;
            
        default:
            throw new Exception('Rol no reconocido para hacer reservas');
    }
}

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'agregar':
        // Lógica de Agregar_Registro.php con ID personalizado
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            // file_put_contents(__DIR__ . '/debug_reserva.txt', json_encode($_POST)); // DEBUG: Eliminar o comentar en producción
            $id_registro = $_POST['id_registro'] ?? null;
            $usuario = $_POST['usuario'] ?? $_POST['id_usuario'] ?? null;
            $recurso = $_POST['recurso'] ?? null;
            $fecha = $_POST['fecha'] ?? null;
            $horaInicio = $_POST['hora_inicio'] ?? $_POST['horaInicio'] ?? null;
            $horaFin = $_POST['hora_fin'] ?? $_POST['horaFin'] ?? null;
            $estado = 'Confirmada';            // NUEVO: obtener docente y asignatura
            $docente = $_POST['docente'] ?? null;
            $asignatura = $_POST['asignatura'] ?? null;
            $semestre = $_POST['semestre'] ?? null; // <-- Agregado correctamente
            $salon = $_POST['salon'] ?? null; // <-- Agregado correctamente
            $id_docente_asignatura = null;
              // NUEVA VALIDACIÓN DE PERMISOS: Verificar que el usuario logueado tenga permisos para hacer esta reserva
            $usuarioLogueado = obtenerUsuarioLogueado($conn);
            $programa = $_POST['programa'] ?? null;
            validarPermisosReserva($conn, $usuarioLogueado, $programa, $docente, $asignatura);

            // VALIDACIÓN PARA ESTUDIANTES: Solo pueden reservar videobeams
            if ($usuarioLogueado['nombreRol'] === 'Estudiante') {
                $stmtRecurso = $conn->prepare("SELECT nombreRecurso FROM recursos WHERE ID_Recurso = ?");
                $stmtRecurso->bind_param("i", $recurso);
                $stmtRecurso->execute();
                $resultRecurso = $stmtRecurso->get_result();
                
                if ($resultRecurso->num_rows > 0) {
                    $recursoInfo = $resultRecurso->fetch_assoc();
                    $nombreRecurso = $recursoInfo['nombreRecurso'];
                    
                    // Verificar si el recurso es un videobeam
                    if (stripos($nombreRecurso, 'videobeam') === false) {
                        $stmtRecurso->close();
                        throw new Exception('Los estudiantes solo pueden reservar videobeams. Recurso seleccionado: ' . $nombreRecurso);
                    }
                } else {
                    $stmtRecurso->close();
                    throw new Exception('Recurso no encontrado');
                }
                $stmtRecurso->close();
            }

            // VALIDACIÓN DE SEGURIDAD: Verificar consistencia de datos según el rol del usuario
            if ($docente) {
                $stmtRol = $conn->prepare("
                    SELECT r.nombreRol 
                    FROM usuario u 
                    INNER JOIN rol r ON u.ID_Rol = r.ID_Rol 
                    WHERE u.ID_Usuario = ?
                ");
                $stmtRol->bind_param("i", $docente);
                $stmtRol->execute();
                $resultRol = $stmtRol->get_result();
                
                if ($resultRol->num_rows > 0) {
                    $rolReal = $resultRol->fetch_assoc()['nombreRol'];
                    
                    // Validar consistencia según el rol
                    if ($rolReal === 'Administrativo') {
                        // Los administrativos NO pueden tener asignatura
                        if (!empty($asignatura)) {
                            $stmtRol->close();
                            throw new Exception('Error de validación: Los administrativos no pueden tener asignaturas asignadas');
                        }
                        // Para administrativos, forzar valores apropiados
                        $asignatura = null;
                        $semestre = null;
                        
                    } else if ($rolReal === 'Docente') {
                        // Los docentes SÍ deben tener asignatura (validación opcional, ya que el frontend lo maneja)
                        if (empty($asignatura)) {
                            $stmtRol->close();
                            throw new Exception('Error de validación: Los docentes deben tener una asignatura asignada');
                        }
                    }
                } else {
                    $stmtRol->close();
                    throw new Exception('Error: Usuario docente no encontrado');
                }
                $stmtRol->close();
            }
            
            // Solo buscar ID_DocenteAsignatura si tenemos AMBOS: docente Y asignatura
            // Si es administrativo, no tendrá asignatura y no necesitamos ID_DocenteAsignatura
            if ($docente && $asignatura) {
                // Buscar el ID_DocenteAsignatura correspondiente
                $stmtDA = $conn->prepare("SELECT ID_DocenteAsignatura FROM docente_asignatura WHERE ID_Usuario = ? AND ID_Asignatura = ? LIMIT 1");
                $stmtDA->bind_param("ii", $docente, $asignatura);
                $stmtDA->execute();
                $resDA = $stmtDA->get_result();
                if ($rowDA = $resDA->fetch_assoc()) {
                    $id_docente_asignatura = $rowDA['ID_DocenteAsignatura'];
                }
                $stmtDA->close();
            } else if ($docente && !$asignatura) {
                // Caso para administrativos: solo tenemos docente pero no asignatura
                // En este caso, ID_DocenteAsignatura permanece NULL
                // Esto es válido para usuarios con rol 'Administrativo'
                $id_docente_asignatura = null;
            }
            // ...validaciones existentes...
            if (!$id_registro) {
                throw new Exception('Falta id_registro');
            }
            if (!$usuario) {
                throw new Exception('Falta usuario');
            }
            if (!$recurso) {
                throw new Exception('Falta recurso');
            }
            if (!$fecha) {
                throw new Exception('Falta fecha');
            }
            if (!$horaInicio) {
                throw new Exception('Falta horaInicio');
            }
            if (!$horaFin) {
                throw new Exception('Falta horaFin');
            }
            $fechaHoraInicio = new DateTime("$fecha $horaInicio");
            $ahora = new DateTime();
            $ahora->modify('+10 minutes');
            if ($fechaHoraInicio < $ahora) {
                throw new Exception('Solo puedes reservar con al menos 10 minutos de anticipación');
            }
            // Validar que el ID no exista
            $stmt = $conn->prepare("SELECT 1 FROM registro WHERE ID_Registro = ?");
            $stmt->bind_param("s", $id_registro);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                throw new Exception('El ID de la reserva ya existe, por favor intente de nuevo.');
            }            $stmt->close();            // NUEVA VALIDACIÓN: Verificar límite de reservas de salas
            // CORRECCIÓN CRÍTICA: Si hay un docente seleccionado (ej: estudiante reserva para docente), 
            // validar el límite contra ese docente, no contra el usuario logueado
            $usuarioParaValidarLimite = $docente ?? $usuario;
            
            $formData = new stdClass();
            $formData->usuario_id = $usuarioParaValidarLimite;
            $formData->recurso_id = $recurso;
            $formData->fecha = $fecha;
            $formData->asignatura_id = $asignatura;
            
            $validacionLimite = validarLimiteSalas($conn, $formData);
            if (!$validacionLimite['permitido']) {
                throw new Exception($validacionLimite['mensaje']);
            }
            
            // --- VALIDACIÓN DE VIDEOBEAMS POR BLOQUE ---
            list($okVideobeam, $msgVideobeam) = validarLimiteVideobeamsPorBloque($conn, $fecha, $horaInicio, $horaFin, $recurso);
            if (!$okVideobeam) {
                throw new Exception($msgVideobeam);
            }

            // Validar traslape de horario SOLO si NO es videobeam
            $stmtTipo = $conn->prepare("SELECT nombreRecurso FROM recursos WHERE ID_Recurso = ?");
            $stmtTipo->bind_param("i", $recurso);
            $stmtTipo->execute();
            $resTipo = $stmtTipo->get_result();
            $esVideobeam = false;
            if ($rowTipo = $resTipo->fetch_assoc()) {
                $esVideobeam = stripos($rowTipo['nombreRecurso'], 'videobeam') !== false;
            }
            $stmtTipo->close();
            if (!$esVideobeam) {
                $sqlVerificar = "SELECT COUNT(*) as conteo FROM registro WHERE ID_Recurso = ? AND fechaReserva = ? AND estado = 'Confirmada' AND (horaInicio < ? AND horaFin > ? )";
                $stmt = $conn->prepare($sqlVerificar);
                $stmt->bind_param("isss", $recurso, $fecha, $horaFin, $horaInicio);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                if ($row['conteo'] > 0) {
                    throw new Exception('El recurso no está disponible en ese horario');
                }
            }
            // Insertar con todos los campos relevantes
            $sql = "INSERT INTO registro (ID_Registro, ID_Usuario, ID_Recurso, fechaReserva, horaInicio, horaFin, estado, ID_DocenteAsignatura, semestre, salon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siissssiss", $id_registro, $usuario, $recurso, $fecha, $horaInicio, $horaFin, $estado, $id_docente_asignatura, $semestre, $salon);
            if ($stmt->execute()) {
                // ---------------- CORREO DE CONFIRMACIÓN ----------------
                try {
                    // Obtener correo del usuario
                    $correo_usuario = null;
                    $stmtCorreo = $conn->prepare("SELECT correo FROM usuario WHERE ID_Usuario = ? LIMIT 1");
                    $stmtCorreo->bind_param("i", $usuario);
                    $stmtCorreo->execute();
                    $resCorreo = $stmtCorreo->get_result();
                    if ($rowCorreo = $resCorreo->fetch_assoc()) {
                        $correo_usuario = $rowCorreo['correo'];
                    }
                    $stmtCorreo->close();
                    if (!$correo_usuario) {
                        throw new Exception('No se pudo obtener el correo del usuario.');
                    }
                    $mail = new PHPMailer(true);
                    // Configuración del servidor SMTP
                    $mail->SMTPDebug = 0; // Desactivar debug para evitar salida extra
                    // $mail->Debugoutput = 'html';

                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com'; // Cambia esto por tu servidor SMTP
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'deinerpjroo@gmail.com'; // Cambia por tu correo
                    $mail->Password   = 'jykz daih exel tauh';        // Cambia por tu contraseña
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;

                    // Remitente y destinatario
                    $mail->setFrom('deinerpjroo@gmail.com', 'Sistema de Reservas');
                    $mail->addAddress($correo_usuario); // Correo real del usuario

                    // Obtener datos para el correo
                    // Nombre del usuario
                    $nombre_usuario = '';
                    $stmtNombreUsuario = $conn->prepare("SELECT nombre FROM usuario WHERE ID_Usuario = ? LIMIT 1");
                    $stmtNombreUsuario->bind_param("i", $usuario);
                    $stmtNombreUsuario->execute();
                    $resNombreUsuario = $stmtNombreUsuario->get_result();
                    if ($rowNombreUsuario = $resNombreUsuario->fetch_assoc()) {
                        $nombre_usuario = $rowNombreUsuario['nombre'];
                    }
                    $stmtNombreUsuario->close();

                    // Nombre del recurso (sala)
                    $nombre_recurso = '';
                    $stmtRecurso = $conn->prepare("SELECT nombreRecurso FROM recursos WHERE ID_Recurso = ? LIMIT 1");
                    $stmtRecurso->bind_param("i", $recurso);
                    $stmtRecurso->execute();
                    $resRecurso = $stmtRecurso->get_result();
                    if ($rowRecurso = $resRecurso->fetch_assoc()) {
                        $nombre_recurso = $rowRecurso['nombreRecurso'];
                    }
                    $stmtRecurso->close();

                    // Nombre del programa
                    $nombre_programa = '';
                    if (isset($_POST['programa'])) {
                        $id_programa = $_POST['programa'];
                        $stmtPrograma = $conn->prepare("SELECT nombrePrograma FROM programa WHERE ID_Programa = ? LIMIT 1");
                        $stmtPrograma->bind_param("i", $id_programa);
                        $stmtPrograma->execute();
                        $resPrograma = $stmtPrograma->get_result();
                        if ($rowPrograma = $resPrograma->fetch_assoc()) {
                            $nombre_programa = $rowPrograma['nombrePrograma'];
                        }
                        $stmtPrograma->close();
                    }

                    // Nombre del docente
                    $nombre_docente = '';
                    if ($docente) {
                        $stmtDocente = $conn->prepare("SELECT nombre FROM usuario WHERE ID_Usuario = ? LIMIT 1");
                        $stmtDocente->bind_param("i", $docente);
                        $stmtDocente->execute();
                        $resDocente = $stmtDocente->get_result();
                        if ($rowDocente = $resDocente->fetch_assoc()) {
                            $nombre_docente = $rowDocente['nombre'];
                        }
                        $stmtDocente->close();
                    }                    // Nombre de la asignatura (solo si existe)
                    $nombre_asignatura = 'N/A';
                    if ($asignatura) {
                        $stmtAsignatura = $conn->prepare("SELECT nombreAsignatura FROM asignatura WHERE ID_Asignatura = ? LIMIT 1");
                        $stmtAsignatura->bind_param("i", $asignatura);
                        $stmtAsignatura->execute();
                        $resAsignatura = $stmtAsignatura->get_result();
                        if ($rowAsignatura = $resAsignatura->fetch_assoc()) {
                            $nombre_asignatura = $rowAsignatura['nombreAsignatura'];
                        }
                        $stmtAsignatura->close();
                    }

                    // Nombre del alumno (si aplica)
                    $nombre_alumno = $_POST['nombre_alumno'] ?? '';

                    // Formatear horario
                    $horario = date('H:i', strtotime($horaInicio)) . ' - ' . date('H:i', strtotime($horaFin));

                    // Formato de correo solicitado
                    $mail->isHTML(true);
                    $mail->Subject = 'Reserva confirmada';
                    $mail->Body =
                        "Estimado(a) {$nombre_usuario}<br><br>" .
                        "Se ha registrado la siguiente reserva:<br>" .
                        "🆔<b>ID:</b> {$id_registro}<br>" .
                        "📅<b>Fecha:</b> {$fecha}<br>" .
                        "🏢<b>Sala:</b> {$nombre_recurso}<br>" .
                        "⏰<b>Horario:</b> {$horario}<br>" .
                        "🎓<b>Programa:</b> {$nombre_programa}<br>" .
                        "👨‍🏫<b>Docente:</b> {$nombre_docente}<br>" .
                        "📖<b>Asignatura:</b> {$nombre_asignatura}<br>" .
                        "👨‍🎓<b>Alumno:</b> {$nombre_alumno}<br><br>" .
                        "Saludos cordiales.";

                    $mail->send();
                } catch (Exception $e) {
                    echo json_encode(['status' => 'error', 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
                    exit;
                }
                // --------------------------------------------------------
                echo json_encode(['status' => 'success', 'message' => 'Registro agregado correctamente']);
            } else {
                throw new Exception('Error al insertar el registro');
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'eliminar':
        // Eliminar un registro por ID_Registro
        $id = $_REQUEST['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID de registro no especificado']);
            break;
        }
        try {
            $stmt = $conn->prepare("DELETE FROM registro WHERE ID_Registro = ?");
            $stmt->bind_param("s", $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No se pudo eliminar el registro']);
            }
            $stmt->close();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'modificar':
        // Modificar un registro existente
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $registro_id = $_POST['registro_id'] ?? null;
            $correo = $_POST['correo'] ?? null;
            $fecha = $_POST['fecha'] ?? null;
            $horaInicio = $_POST['horaInicio'] ?? null;
            $horaFin = $_POST['horaFin'] ?? null;
            $recurso = $_POST['recurso'] ?? null;
            $programa = $_POST['programa'] ?? null;
            $docente = $_POST['docente'] ?? null;
            $asignatura = $_POST['asignatura'] ?? null;
            $salon = $_POST['salon'] ?? null;
            $semestre = $_POST['semestre'] ?? null;            $celular = $_POST['celular'] ?? null;
            $estado = $_POST['estado'] ?? 'Confirmada';
              // NUEVA VALIDACIÓN DE PERMISOS: Verificar que el usuario logueado tenga permisos para hacer esta reserva (MODIFICAR)
            $usuarioLogueado = obtenerUsuarioLogueado($conn);
            validarPermisosReserva($conn, $usuarioLogueado, $programa, $docente, $asignatura);

            // VALIDACIÓN PARA ESTUDIANTES: Solo pueden reservar videobeams (MODIFICAR)
            if ($usuarioLogueado['nombreRol'] === 'Estudiante') {
                $stmtRecurso = $conn->prepare("SELECT nombreRecurso FROM recursos WHERE ID_Recurso = ?");
                $stmtRecurso->bind_param("i", $recurso);
                $stmtRecurso->execute();
                $resultRecurso = $stmtRecurso->get_result();
                
                if ($resultRecurso->num_rows > 0) {
                    $recursoInfo = $resultRecurso->fetch_assoc();
                    $nombreRecurso = $recursoInfo['nombreRecurso'];
                    
                    // Verificar si el recurso es un videobeam
                    if (stripos($nombreRecurso, 'videobeam') === false) {
                        $stmtRecurso->close();
                        throw new Exception('Los estudiantes solo pueden reservar videobeams. Recurso seleccionado: ' . $nombreRecurso);
                    }
                } else {
                    $stmtRecurso->close();
                    throw new Exception('Recurso no encontrado');
                }
                $stmtRecurso->close();
            }

            // VALIDACIÓN DE SEGURIDAD: Verificar consistencia de datos según el rol del usuario (MODIFICAR)
            if ($docente) {
                $stmtRol = $conn->prepare("
                    SELECT r.nombreRol 
                    FROM usuario u 
                    INNER JOIN rol r ON u.ID_Rol = r.ID_Rol 
                    WHERE u.ID_Usuario = ?
                ");
                $stmtRol->bind_param("i", $docente);
                $stmtRol->execute();
                $resultRol = $stmtRol->get_result();
                
                if ($resultRol->num_rows > 0) {
                    $rolReal = $resultRol->fetch_assoc()['nombreRol'];
                    
                    // Validar consistencia según el rol
                    if ($rolReal === 'Administrativo') {
                        // Los administrativos NO pueden tener asignatura
                        if (!empty($asignatura)) {
                            $stmtRol->close();
                            throw new Exception('Error de validación: Los administrativos no pueden tener asignaturas asignadas');
                        }
                        // Para administrativos, forzar valores apropiados
                        $asignatura = null;
                        $semestre = null;
                        
                    } else if ($rolReal === 'Docente') {
                        // Los docentes SÍ deben tener asignatura (validación opcional, ya que el frontend lo maneja)
                        if (empty($asignatura)) {
                            $stmtRol->close();
                            throw new Exception('Error de validación: Los docentes deben tener una asignatura asignada');
                        }
                    }
                } else {
                    $stmtRol->close();
                    throw new Exception('Error: Usuario docente no encontrado');
                }
                $stmtRol->close();
            }

            // Validaciones básicas
            if (!$registro_id) {
                throw new Exception('Falta ID del registro');
            }
            if (!$fecha) {
                throw new Exception('Falta fecha');
            }
            if (!$horaInicio) {
                throw new Exception('Falta hora de inicio');
            }
            if (!$horaFin) {
                throw new Exception('Falta hora de fin');
            }
            if (!$recurso) {
                throw new Exception('Falta recurso');
            }

            // Verificar que el registro existe
            $stmtExiste = $conn->prepare("SELECT ID_Usuario FROM registro WHERE ID_Registro = ?");
            $stmtExiste->bind_param("s", $registro_id);
            $stmtExiste->execute();
            $resultExiste = $stmtExiste->get_result();
            if ($resultExiste->num_rows === 0) {
                throw new Exception('El registro no existe');
            }
            $rowExiste = $resultExiste->fetch_assoc();
            $usuario_id = $rowExiste['ID_Usuario'];
            $stmtExiste->close();            // Obtener ID_DocenteAsignatura si se proporcionaron docente y asignatura
            $id_docente_asignatura = null;
            if ($docente && $asignatura) {
                $stmtDA = $conn->prepare("SELECT ID_DocenteAsignatura FROM docente_asignatura WHERE ID_Usuario = ? AND ID_Asignatura = ? LIMIT 1");
                $stmtDA->bind_param("ii", $docente, $asignatura);
                $stmtDA->execute();
                $resDA = $stmtDA->get_result();
                if ($rowDA = $resDA->fetch_assoc()) {
                    $id_docente_asignatura = $rowDA['ID_DocenteAsignatura'];
                }
                $stmtDA->close();
            } else if ($docente && !$asignatura) {
                // Caso para administrativos: solo tenemos docente pero no asignatura
                // En este caso, ID_DocenteAsignatura permanece NULL
                $id_docente_asignatura = null;            }            // NUEVA VALIDACIÓN: Verificar límite de reservas de salas (modificación)
            // CORRECCIÓN CRÍTICA: Si hay un docente seleccionado (ej: estudiante modifica reserva para docente), 
            // validar el límite contra ese docente, no contra el usuario original del registro
            $usuarioParaValidarLimiteModificar = $docente ?? $usuario_id;
            
            $formDataModificar = new stdClass();
            $formDataModificar->usuario_id = $usuarioParaValidarLimiteModificar;
            $formDataModificar->recurso_id = $recurso;
            $formDataModificar->fecha = $fecha;
            $formDataModificar->registro_excluir = $registro_id; // Excluir el registro actual
            $formDataModificar->asignatura_id = $asignatura;
            
            $validacionLimiteModificar = validarLimiteSalas($conn, $formDataModificar);
            if (!$validacionLimiteModificar['permitido']) {
                throw new Exception($validacionLimiteModificar['mensaje']);
            }

            // --- VALIDACIÓN DE VIDEOBEAMS POR BLOQUE (MODIFICAR) ---
            list($okVideobeam, $msgVideobeam) = validarLimiteVideobeamsPorBloque($conn, $fecha, $horaInicio, $horaFin, $recurso, $registro_id);
            if (!$okVideobeam) {
                throw new Exception($msgVideobeam);
            }

            // Validar traslape de horario SOLO si NO es videobeam (excluyendo el registro actual)
            $stmtTipo = $conn->prepare("SELECT nombreRecurso FROM recursos WHERE ID_Recurso = ?");
            $stmtTipo->bind_param("i", $recurso);
            $stmtTipo->execute();
            $resTipo = $stmtTipo->get_result();
            $esVideobeam = false;
            if ($rowTipo = $resTipo->fetch_assoc()) {
                $esVideobeam = stripos($rowTipo['nombreRecurso'], 'videobeam') !== false;
            }
            $stmtTipo->close();
            if (!$esVideobeam) {
                $sqlVerificar = "SELECT COUNT(*) as conteo FROM registro "+
                                " WHERE ID_Recurso = ? AND fechaReserva = ? AND estado = 'Confirmada' "+
                                " AND ID_Registro != ? "+
                                " AND (horaInicio < ? AND horaFin > ?)";
                $stmtVerificar = $conn->prepare($sqlVerificar);
                $stmtVerificar->bind_param("issss", $recurso, $fecha, $registro_id, $horaFin, $horaInicio);
                $stmtVerificar->execute();
                $resultVerificar = $stmtVerificar->get_result();
                $rowVerificar = $resultVerificar->fetch_assoc();
                $stmtVerificar->close();
                if ($rowVerificar['conteo'] > 0) {
                    throw new Exception('El recurso no está disponible en ese horario');
                }
            }
            // Actualizar el registro
            $sql = "UPDATE registro SET 
                    fechaReserva = ?, 
                    horaInicio = ?, 
                    horaFin = ?, 
                    ID_Recurso = ?, 
                    ID_DocenteAsignatura = ?, 
                    salon = ?, 
                    semestre = ?, 
                    estado = ?";

            $params = "sssiisss";
            $paramValues = [$fecha, $horaInicio, $horaFin, $recurso, $id_docente_asignatura, $salon, $semestre, $estado];

            // Si se proporciona celular, actualizar también el usuario
            if ($celular !== null) {
                $stmtUsuario = $conn->prepare("UPDATE usuario SET telefono = ? WHERE ID_Usuario = ?");
                $stmtUsuario->bind_param("si", $celular, $usuario_id);
                $stmtUsuario->execute();
                $stmtUsuario->close();
            }

            $sql .= " WHERE ID_Registro = ?";
            $params .= "s";
            $paramValues[] = $registro_id;

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($params, ...$paramValues);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Registro modificado correctamente']);
            } else {
                throw new Exception('Error al actualizar el registro: ' . $conn->error);
            }
            $stmt->close();

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

        // ... resto de tus casos (actualizar, cancelar, etc) igual que antes ...
        // (No necesitas repetir el correo en los otros casos, solo en 'agregar')

        // ... (todo el resto del código igual) ...
}
$conn->close();
