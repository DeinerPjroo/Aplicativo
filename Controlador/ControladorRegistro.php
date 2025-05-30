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

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'agregar':
        // Lógica de Agregar_Registro.php con ID personalizado
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            file_put_contents(__DIR__ . '/debug_reserva.txt', json_encode($_POST));
            $id_registro = $_POST['id_registro'] ?? null;
            $usuario = $_POST['usuario'] ?? $_POST['id_usuario'] ?? null;
            $recurso = $_POST['recurso'] ?? null;
            $fecha = $_POST['fecha'] ?? null;
            $horaInicio = $_POST['hora_inicio'] ?? $_POST['horaInicio'] ?? null;
            $horaFin = $_POST['hora_fin'] ?? $_POST['horaFin'] ?? null;
            $estado = 'Confirmada';
            // NUEVO: obtener docente y asignatura
            $docente = $_POST['docente'] ?? null;
            $asignatura = $_POST['asignatura'] ?? null;
            $semestre = $_POST['semestre'] ?? null; // <-- Agregado correctamente
            $salon = $_POST['salon'] ?? null; // <-- Agregado correctamente
            $id_docente_asignatura = null;
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
            }
            $stmt->close();
            // Validar traslape de horario
            $sqlVerificar = "SELECT COUNT(*) as conteo FROM registro WHERE ID_Recurso = ? AND fechaReserva = ? AND estado = 'Confirmada' AND (horaInicio < ? AND horaFin > ? )";
            $stmt = $conn->prepare($sqlVerificar);
            $stmt->bind_param("isss", $recurso, $fecha, $horaFin, $horaInicio);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row['conteo'] > 0) {
                throw new Exception('El recurso no está disponible en ese horario');
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
                    }

                    // Nombre de la asignatura
                    $nombre_asignatura = '';
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

        // ... resto de tus casos (modificar, actualizar, cancelar, etc) igual que antes ...
        // (No necesitas repetir el correo en los otros casos, solo en 'agregar')

        // ... (todo el resto del código igual) ...

}
$conn->close();
