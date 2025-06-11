<?php


date_default_timezone_set('America/Bogota');
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain.1252');


include("../Controlador/control_De_Rol.php");
checkRole(['Docente', 'Estudiante', 'Administrativo']); // Allow both roles

$role = getUserRole(); // Asegúrate de que esta función devuelve el rol del usuario actual
// Incluir conexión a la base de datos
include("../database/conection.php");

// Actualizar automáticamente reservas vencidas
$actualizar = "UPDATE registro 
               SET estado = 'Completada' 
               WHERE estado = 'Confirmada' 
               AND CONCAT(fechaReserva, ' ', horaFin) < NOW() 
               AND ID_Usuario = ?";
$updateStmt = $conn->prepare($actualizar);
$updateStmt->bind_param("i", $usuarioId);
$updateStmt->execute();
echo "<!-- Reservas actualizadas: " . $updateStmt->affected_rows . " -->";



// Obtener las reservas actuales del usuario logueado
$usuarioId = $_SESSION['usuario_id'];
$fechaActual = date("Y-m-d");


// Consulta para obtener reservas del usuario actual (pendientes y confirmadas)
$sql = "SELECT r.ID_Registro, r.fechaReserva, r.horaInicio, r.horaFin, 
               rec.nombreRecurso, r.estado, r.creado_en,
               CASE 
                   WHEN r.ID_DocenteAsignatura IS NOT NULL THEN doc.nombre
                   ELSE u.nombre
               END AS nombreDocente,
               CASE 
                   WHEN r.ID_DocenteAsignatura IS NOT NULL THEN asig.nombreAsignatura
                   ELSE 'N/A'
               END AS asignatura,
               CASE 
                   WHEN r.ID_DocenteAsignatura IS NOT NULL THEN pr.nombrePrograma
                   ELSE prog_user.nombrePrograma
               END AS programa
        FROM registro r
        JOIN recursos rec ON r.ID_Recurso = rec.ID_Recurso
        JOIN usuario u ON r.ID_Usuario = u.ID_Usuario
        LEFT JOIN programa prog_user ON u.Id_Programa = prog_user.ID_Programa
        LEFT JOIN docente_asignatura da ON r.ID_DocenteAsignatura = da.ID_DocenteAsignatura
        LEFT JOIN usuario doc ON da.ID_Usuario = doc.ID_Usuario
        LEFT JOIN asignatura asig ON da.ID_Asignatura = asig.ID_Asignatura
        LEFT JOIN programa pr ON asig.ID_Programa = pr.ID_Programa
        WHERE r.ID_Usuario = ? 
        AND r.fechaReserva >= ? 
        AND r.estado = 'Confirmada'
        ORDER BY r.fechaReserva ASC, r.horaInicio ASC";


$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $usuarioId, $fechaActual);
$stmt->execute();
$resultado = $stmt->get_result();

// Procesar mensajes de respuesta
$mensaje = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'cancelada') {
        $mensaje = '<div class="alert alert-success">Reserva cancelada correctamente.</div>';
    } elseif ($_GET['msg'] == 'confirmada') {
        $mensaje = '<div class="alert alert-success">Reserva confirmada exitosamente.</div>';
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'db') {
        $mensaje = '<div class="alert alert-danger">Error al procesar la solicitud. Inténtelo nuevamente.</div>';
    } else if ($_GET['error'] == 'nopermitido') {
        $mensaje = '<div class="alert alert-danger">No tienes permiso para realizar esta acción.</div>';
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    <link rel="stylesheet" href="../css/Style.css">
    <link rel="stylesheet" href="../css/videobeam-info.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="webside icon" type="png" href="images/logo.png">    <title>Mis Reservas</title>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .swal-wide {
            width: 500px !important;
        }
        .swal2-html-container {
            text-align: left !important;
        }
    </style>
</head>

<body class="Registro">

    <!------------------------------------------------------------------------------------->    <!--SIDEBAR-->
    <?php
    include("../Vista/Sidebar.php");
    ?>
    <!------------------------------------------------------------------------------------->
      <!-- BOTÓN DE MENÚ MÓVIL -->
    <button class="menu-toggle" id="menuToggle">
        <img src="../Imagen/Iconos/Menu_3lineas.svg" alt="Menú" class="menu-icon">
    </button>
    
    <!-- OVERLAY PARA CERRAR MENÚ -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!------------------------------------------------------------------------------------->
    <section class="Main">
        <section class="Topbard">
            <h1>
                <center>Mis Reservas</center>
            </h1>
            <!-- DEBUG: Botón de prueba modal -->
          
        </section><div class="contenedor-reservas">
            <?php
            // Mostrar mensajes de confirmación o error
            if (!empty($mensaje)) {
                echo $mensaje;
            }
            
            // Mostrar mensajes de la sesión (cancelación)
            if (isset($_SESSION['mensaje_exito'])) {
                echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['mensaje_exito']) . '</div>';
                unset($_SESSION['mensaje_exito']);
            }
            
            if (isset($_SESSION['mensaje_error'])) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['mensaje_error']) . '</div>';
                unset($_SESSION['mensaje_error']);
            }
            ?>

            <?php

            if ($resultado->num_rows > 0) : ?>
                <div class="tabla-scroll">                    <table class="tabla-reservas">
                    <thead>
                        <tr>
                            <!-- Columnas principales para móviles (solo 3 columnas) -->
                            <th>Recurso</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <!-- Columnas adicionales solo para desktop -->
                            <th class="desktop-only">Hora Fin</th>
                            <th class="desktop-only">Programa</th>
                            <th class="desktop-only">Docente</th>
                            <th class="desktop-only">Asignatura</th>
                            <th class="desktop-only">Estado</th>
                            <th class="desktop-only">Fecha de Solicitud</th>
                            <th class="desktop-only">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $fechaAnterior = null;
                        while ($row = $resultado->fetch_assoc()) :
                            $fechaActual = $row['fechaReserva'];
                            if ($fechaActual !== $fechaAnterior) :
                                echo "<tr class='separador-dia'>
            <td colspan='10' style='background-color:#e0e0e0; font-weight:bold; text-align:center;'>
                📅 " . strftime("%A %d de %B de %Y", strtotime($fechaActual)) . "
            </td>
        </tr>";
                                $fechaAnterior = $fechaActual;
                            endif;
                        ?>
                            <tr>
    <td><?php echo htmlspecialchars($row['nombreRecurso']); ?></td>
    <td><?php echo date('d/m/Y', strtotime($row['fechaReserva'])); ?></td>
    <td><?php echo date('h:i A', strtotime($row['horaInicio'])); ?></td>
    <td><?php echo date('h:i A', strtotime($row['horaFin'])); ?></td>
    <td><?php echo htmlspecialchars($row['programa']); ?></td>
    <td><?php echo htmlspecialchars($row['nombreDocente']); ?></td>
    <td><?php echo htmlspecialchars($row['asignatura']); ?></td>
    <td>
        <span class="status-<?php echo strtolower($row['estado']); ?>">
            <?php echo $row['estado']; ?>
        </span>
    </td>
    <td><?php echo date('d/m/Y H:i', strtotime($row['creado_en'])); ?></td>    <td>
        <?php if ($row['estado'] === 'Confirmada'): ?>
            <button type="button" class="btn-cancelar" 
                    onclick="confirmarCancelacion('<?php echo $row['ID_Registro']; ?>', '<?php echo htmlspecialchars($row['nombreRecurso'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo date('d/m/Y', strtotime($row['fechaReserva'])); ?>', '<?php echo date('h:i A', strtotime($row['horaInicio'])); ?>')">
                Cancelar
            </button>
        <?php else: ?>
            <span>—</span>
        <?php endif; ?>
    </td>
</tr>
                        <?php endwhile; ?>                    </tbody>
                </table>
                </div>
            <?php else : ?>
                <div class="sin-reservas">
                    <p>No tienes reservas activas en este momento</p>
                </div>
            <?php endif; ?>            <center>
                <button class="btn-agregar" onclick="abrirModalReserva()">
                    <img src="../Imagen/Iconos/Mas.svg" alt="" />
                    <span class="btn-text">Crear Nueva Reserva</span>
                </button>
                
               
            </center></div>
    </section>

    <!-- Modal único para reserva -->
    <div id="modalReservaUnica" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModalReserva('modalReservaUnica')">&times;</span>
            <h2>Nueva Reserva</h2>
            <form id="reservaFormUnica" onsubmit="return guardarReservaUnica(event)">
                <input type="hidden" name="usuario" value="<?php echo $_SESSION['usuario_id']; ?>">
                <div class="form-group">
                    <label for="id_registro">ID del Registro:</label>
                    <input type="text" id="id_registro" name="id_registro" value="" readonly>
                </div>
                <div class="form-group">
                    <label for="fecha_unico">Fecha de Reserva:</label>
                    <input type="date" id="fecha_unico" name="fecha" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="recurso_unico">Recurso:</label>
                    <select id="recurso_unico" name="recurso" required>
                        <option value="">Seleccione un recurso</option>
                        <?php
                        $recursos = $conn->query("SELECT ID_Recurso, nombreRecurso FROM recursos");
                        while ($recurso = $recursos->fetch_assoc()):
                        ?>
                            <option value="<?= $recurso['ID_Recurso'] ?>" data-nombre="<?= $recurso['nombreRecurso'] ?>"><?= $recurso['nombreRecurso'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="horaInicio_unico">Hora de Inicio:</label>
                    <input type="time" id="horaInicio_unico" name="horaInicio" min="06:00" max="21:30" required>
                </div>
                <div class="form-group">
                    <label for="horaFin_unico">Hora Final:</label>
                    <input type="time" id="horaFin_unico" name="horaFin" min="06:30" max="22:00" required>
                </div>
                <div class="form-group">
                    <label for="programa_unico">Programa/Dependencia:</label>
                    <select id="programa_unico" name="programa" required>
                        <option value="">Seleccione un Programa/Dependencia</option>
                        <?php
                        $programas = $conn->query("SELECT ID_Programa, nombrePrograma FROM programa");
                        while ($programa = $programas->fetch_assoc()):
                        ?>
                            <option value="<?= $programa['ID_Programa'] ?>"><?= $programa['nombrePrograma'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="docente_unico">Docente/Administrativo:</label>
                    <select id="docente_unico" name="docente" required>
                        <option value="">Seleccione un Docente/Administrativo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="asignatura_unico">Asignatura:</label>
                    <select id="asignatura_unico" name="asignatura" required>
                        <option value="">Seleccione una Asignatura</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="nombre_alumno_unico">Nombre del Alumno:</label>
                    <input type="text" id="nombre_alumno_unico" name="nombre_alumno" value="<?php echo ($role === 'Estudiante') ? htmlspecialchars($_SESSION['usuario_nombre']) : 'N/A'; ?>" readonly>
                </div>                <div class="form-group" id="grupo_salon_unico">
                    <label for="salon_unico">Salón:</label>
                    <input type="text" id="salon_unico" name="salon" placeholder="Ej: 2B, 1A...">
                    <small class="form-note">💡 Ingrese el salón donde normalmente da clase o donde se realizará la actividad</small>
                </div>
                <div class="form-group">
                    <label for="semestre_unico">Semestre:</label>
                    <select id="semestre_unico" name="semestre" required>
                        <option value="">Seleccione el semestre</option>
                        <?php
                        $romanos = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
                        for ($i = 1; $i <= 10; $i++):
                        ?>
                            <option value="<?= $romanos[$i - 1] ?>"><?= $romanos[$i - 1] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="celular_unico">Celular:</label>
                    <input type="text" id="celular_unico" name="celular" required>
                </div>
                <div class="form-group">
                    <label for="correo_unico">Correo:</label>
                    <input type="email" id="correo_unico" name="correo" value="<?php echo htmlspecialchars($_SESSION['usuario_correo']); ?>" readonly>
                </div>
                <div id="mensajeErrorUnico" class="error-mensaje" style="display: none;"></div>
                <div class="form-actions">
                    <button type="submit" class="btn-confirmar">Reservar</button>
                    <button type="button" onclick="cerrarModalReserva('modalReservaUnica')" class="btn-cancelar">Cancelar</button>
                </div>
            </form>
        </div>    </div>    <script>
        // Variables globales del usuario
        const usuarioId = <?php echo $usuarioId; ?>;
        const rolUsuario = '<?php echo $role; ?>';
        
        // 🚨 DEBUG MODAL CANCELACIÓN 🚨
        console.log('%c🔥 INICIO SCRIPT - RESERVAS USUARIOS', 'background: #ff0000; color: white; padding: 5px; font-size: 16px; font-weight: bold;');
        console.log('🔧 SweetAlert2 disponible:', typeof Swal !== 'undefined');
        console.log('🔧 Usuario ID:', usuarioId, 'Rol:', rolUsuario);
        
        // Verificar inmediatamente si la función existe
        window.addEventListener('DOMContentLoaded', function() {
            console.log('%c✅ DOM CARGADO COMPLETAMENTE', 'background: #00ff00; color: white; padding: 5px; font-size: 14px;');
            console.log('🔧 Función confirmarCancelacion definida:', typeof confirmarCancelacion !== 'undefined');
            
            // Listar todos los botones de cancelar
            const botonesCancelar = document.querySelectorAll('.btn-cancelar');
            console.log(`🔧 Total botones de cancelar encontrados: ${botonesCancelar.length}`);
            botonesCancelar.forEach((btn, index) => {
                console.log(`   - Botón ${index + 1}:`, btn.onclick ? btn.onclick.toString() : 'Sin onclick', btn);
            });
        });        // Cargar docentes según programa
        const programaSelect = document.getElementById('programa_unico');
        const docenteSelect = document.getElementById('docente_unico');
        const asignaturaSelect = document.getElementById('asignatura_unico');
        const asignaturaGroup = document.querySelector('#asignatura_unico').closest('.form-group');
        
        programaSelect.addEventListener('change', function() {
            const programaId = this.value;
            docenteSelect.innerHTML = '<option value="">Cargando...</option>';
            asignaturaSelect.innerHTML = '<option value="">Seleccione una Asignatura</option>';
            
            fetch('../Controlador/ControladorObtener.php?tipo=docentes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id_programa=' + encodeURIComponent(programaId)
                })
                .then(response => response.json())
                .then(data => {
                    docenteSelect.innerHTML = '<option value="">Seleccione un Docente/Administrativo</option>';
                    data.data.forEach(docente => {
                        docenteSelect.innerHTML += `<option value="${docente.ID_Usuario}" data-rol="${docente.rol}">${docente.nombre} (${docente.rol})</option>`;
                    });
                });
        });        // Cambiado para manejar docentes y administrativos
        docenteSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const docenteId = this.value;
            const programaId = programaSelect.value;
            const rolUsuario = selectedOption.getAttribute('data-rol');
            
            console.log('Rol del usuario seleccionado:', rolUsuario);
            
            // Obtener referencias a los campos que se ocultan para administrativos
            const semestreGroup = document.querySelector('#semestre_unico').closest('.form-group');
            const semestreSelect = document.getElementById('semestre_unico');
            
            // Si es administrativo, ocultar campos de asignatura y semestre
            if (rolUsuario === 'Administrativo') {
                asignaturaGroup.style.display = 'none';
                asignaturaSelect.removeAttribute('required');
                asignaturaSelect.value = ''; // Limpiar valor
                
                semestreGroup.style.display = 'none';
                semestreSelect.removeAttribute('required');
                semestreSelect.value = ''; // Limpiar valor
            } else {
                // Si es docente, mostrar los campos de asignatura y semestre
                asignaturaGroup.style.display = 'block';
                asignaturaSelect.setAttribute('required', 'required');
                
                semestreGroup.style.display = 'block';
                semestreSelect.setAttribute('required', 'required');
                
                // Cargar asignaturas del docente
                if (docenteId && programaId) {
                    asignaturaSelect.innerHTML = '<option value="">Cargando...</option>';
                    fetch('../Controlador/ControladorObtener.php?tipo=asignaturas', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'id_docente=' + encodeURIComponent(docenteId) + '&id_programa=' + encodeURIComponent(programaId)
                        })
                        .then(response => response.json())
                        .then(data => {
                            asignaturaSelect.innerHTML = '<option value="">Seleccione una Asignatura</option>';
                            data.data.forEach(asig => {
                                asignaturaSelect.innerHTML += `<option value="${asig.ID_Asignatura}">${asig.nombreAsignatura}</option>`;
                            });
                        });
                }
            }
        });

        // Función común de validación para todos los formularios
        function validarRegistro(fecha, horaInicio, horaFin) {
            const hoy = new Date();
            const fechaSeleccionada = new Date(fecha + 'T00:00');

            // Validar que no sea una fecha pasada
            if (fechaSeleccionada.setHours(0, 0, 0, 0) < hoy.setHours(0, 0, 0, 0)) {
                throw new Error('No puedes seleccionar una fecha pasada');
            }

            // Validar horario de operación (6:00 AM - 10:00 PM)
            const [hInicio, mInicio] = horaInicio.split(':').map(Number);
            const [hFin, mFin] = horaFin.split(':').map(Number);
            if (hInicio < 6 || hFin > 22 || (hFin === 22 && mFin > 0)) {
                throw new Error('El horario de reserva debe estar entre las 6:00 AM y las 10:00 PM');
            }


            const fechaHoraInicio = new Date(`${fecha}T${horaInicio}`);
            const fechaHoraFin = new Date(`${fecha}T${horaFin}`);

            // Validar que si es para hoy, la hora de inicio sea al menos 10 minutos después de la actual
            const ahora = new Date();
            const hoyStr = ahora.toISOString().split('T')[0];
            if (fecha === hoyStr) {
                const margenMinutos = 10;
                const ahoraConMargen = new Date(ahora.getTime() + margenMinutos * 60000);

                if (fechaHoraInicio <= ahoraConMargen) {
                    throw new Error('Solo puedes apartar con al menos 10 minutos de anticipación');
                }
            }

            // Validar que la hora de fin sea posterior a la de inicio
            if (fechaHoraFin <= fechaHoraInicio) {
                throw new Error('La hora de finalización debe ser posterior a la hora de inicio');
            }

            // Validar duración mínima y máxima
            const duracionMin = 30; // minutos
            const duracionMax = 240;
            const duracionMs = fechaHoraFin - fechaHoraInicio;
            const duracionMinutos = duracionMs / (1000 * 60);

            if (duracionMinutos < duracionMin) {
                throw new Error('La reserva debe durar al menos 30 minutos');
            }

            if (duracionMinutos > duracionMax) {
                throw new Error('La reserva no puede exceder 4 horas');
            }

            return true;
        }

        // Función para verificar disponibilidad de recurso
        // 1. Primero, arreglemos la función de verificación de disponibilidad
        async function verificarDisponibilidad(fecha, horaInicio, horaFin, recurso) {
            const formData = new FormData();
            formData.append("fecha", fecha);
            formData.append("hora_inicio", horaInicio); // CAMBIO: debe ser hora_inicio
            formData.append("hora_fin", horaFin);      // CAMBIO: debe ser hora_fin
            formData.append("recurso", recurso);

            try {
                const response = await fetch("../Controlador/ControladorVerificar.php?tipo=disponibilidad", {
                    method: "POST",
                    body: formData
                });

                // Leer el cuerpo de la respuesta una sola vez
                const contentType = response.headers.get("content-type");
                const responseBody = contentType && contentType.includes("application/json") ?
                    await response.json() :
                    await response.text();

                if (typeof responseBody === "object") {
                    console.log("Respuesta del servidor:", responseBody); // Depuración
                    if (!responseBody.disponible) {
                        throw new Error(responseBody.mensaje || responseBody.error || 'El recurso no está disponible en ese horario');
                    }
                    return true;
                } else {
                    console.error("Respuesta no JSON:", responseBody); // Depuración
                    throw new Error('Error en la respuesta del servidor. No es un formato JSON válido.');
                }
            } catch (error) {
                console.error("Error al verificar disponibilidad:", error); // Depuración
                throw error;
            }
        }


        // Actualizar la función guardarReservaEstudiante
        async function guardarReservaUnica(event) {
            event.preventDefault();
            const form = document.getElementById('reservaFormUnica');
            const btn = form.querySelector('button[type="submit"]');
            if (btn.disabled) return; // Evita doble envío si ya está desactivado
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Reservando...';
            const fecha = form.fecha.value;
            const idInput = form.id_registro;

            if (!idInput.value) {
                idInput.value = generarIdReserva(fecha);
            }

            try {
                // Validar el formulario
                validarRegistro(
                    form.fecha.value,
                    form.horaInicio.value,
                    form.horaFin.value
                );

                // Verificar disponibilidad
                await verificarDisponibilidad(
                    form.fecha.value,
                    form.horaInicio.value,
                    form.horaFin.value,
                    form.recurso.value
                );                // Si pasa todas las validaciones, enviar el formulario
                const formData = new FormData(form);
                
                // Manejar el caso de campos opcionales para administrativos
                const docenteSelect = document.getElementById('docente_unico');
                const selectedOption = docenteSelect.options[docenteSelect.selectedIndex];
                const rolUsuario = selectedOption.getAttribute('data-rol');
                
                // Si es administrativo, eliminar campos que no aplican
                if (rolUsuario === 'Administrativo') {
                    formData.delete('asignatura'); // Eliminar el campo asignatura
                    formData.delete('semestre'); // Eliminar el campo semestre
                }
                
                const response = await fetch('../Controlador/ControladorRegistro.php?accion=agregar', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        cerrarModalReserva('modalReservaUnica');
                        setTimeout(() => {
                            location.reload();
                        }, 300); // Espera breve para asegurar el cierre visual del modal
                    });
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        // Generar ID de reserva al abrir el modal y al cambiar la fecha
        function generarIdReserva(fecha) {
            if (!fecha) return '';
            const random = Math.random().toString(36).substr(2, 4).toUpperCase();
            return fecha.replace(/-/g, '') + '-' + random;
        }        function abrirModalReserva() {
            document.getElementById('modalReservaUnica').style.display = 'block';
            // Limpiar campos manualmente
            const form = document.getElementById('reservaFormUnica');
            const asignaturaGroup = document.querySelector('#asignatura_unico').closest('.form-group');
            const asignaturaSelect = document.getElementById('asignatura_unico');
            const semestreGroup = document.querySelector('#semestre_unico').closest('.form-group');
            const semestreSelect = document.getElementById('semestre_unico');
            
            form.recurso.value = '';
            form.horaInicio.value = '';
            form.horaFin.value = '';
            form.programa.value = '';
            form.docente.value = '';
            form.asignatura.value = '';
            form.nombre_alumno.value = '<?php echo ($role === 'Estudiante') ? htmlspecialchars($_SESSION['usuario_nombre']) : 'N/A'; ?>';
            form.salon.value = '';
            form.semestre.value = '';
            form.celular.value = '';
            form.correo.value = '<?php echo htmlspecialchars($_SESSION['usuario_correo']); ?>';
            
            // Resetear los campos de asignatura y semestre al estado inicial (visibles y requeridos)
            asignaturaGroup.style.display = 'block';
            asignaturaSelect.setAttribute('required', 'required');
            
            semestreGroup.style.display = 'block';
            semestreSelect.setAttribute('required', 'required');
            
            // Asignar fecha de hoy y generar el ID
            const fechaInput = document.getElementById('fecha_unico');
            const hoy = new Date().toISOString().split('T')[0];
            fechaInput.value = hoy;
            document.getElementById('id_registro').value = generarIdReserva(hoy);
        }// Cuando el usuario selecciona una fecha, generar el ID
        document.addEventListener('DOMContentLoaded', function() {
            const fechaInput = document.getElementById('fecha_unico');
            const idInput = document.getElementById('id_registro');
            if (fechaInput) {
                fechaInput.addEventListener('change', function() {
                    idInput.value = generarIdReserva(this.value);
                });
            }
        });

        // Agregar validación en tiempo real para los campos de fecha y hora
        document.addEventListener('DOMContentLoaded', function() {
            const modales = ['modalReservaEstudiante', 'modalReservaDocente'];

            modales.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    const inputs = modal.querySelectorAll('input[type="date"], input[type="time"]');
                    inputs.forEach(input => {
                        input.addEventListener('change', function() {
                            const form = this.closest('form');
                            try {
                                const fecha = form.querySelector('input[type="date"]').value;
                                const horaInicio = form.querySelector('input[name="horaInicio"]').value;
                                const horaFin = form.querySelector('input[name="horaFin"]').value;

                                if (fecha && horaInicio && horaFin) {
                                    validarRegistro(fecha, horaInicio, horaFin);
                                    form.querySelector('.error-mensaje').style.display = 'none';
                                }
                            } catch (error) {
                                form.querySelector('.error-mensaje').textContent = error.message;
                                form.querySelector('.error-mensaje').style.display = 'block';
                            }
                        });
                    });
                }
            });
        });

        async function validarFormularioReserva(form, tipoUsuario) {
            const fecha = form.querySelector('input[name="fecha"]').value;
            const horaInicio = form.querySelector('input[name="horaInicio"]').value;
            const horaFin = form.querySelector('input[name="horaFin"]').value;
            const recurso = form.querySelector('select[name="recurso"]').value;

            // Validar campos obligatorios
            if (!fecha || !horaInicio || !horaFin || !recurso) {
                throw new Error('Todos los campos son obligatorios');
            }

            // Validar fecha y horas
            const inicio = new Date(`${fecha}T${horaInicio}`);
            const fin = new Date(`${fecha}T${horaFin}`);
            const ahora = new Date();

            if (inicio < ahora) {
                throw new Error('No puedes seleccionar una fecha y hora pasada');
            }

            if (fin <= inicio) {
                throw new Error('La hora de finalización debe ser posterior a la hora de inicio');
            }

            let duracionMinutos = (fin - inicio) / (1000 * 60);
            if (tipoUsuario === 'Estudiante') {
                if (duracionMinutos > 180) { // 3 horas máximo
                    throw new Error('Los estudiantes pueden reservar máximo 3 horas');
                }
            } else {
                if (duracionMinutos > 360) { // 6 horas máximo
                    throw new Error('Los docentes pueden reservar máximo 6 horas');
                }
            }
            // Verificar disponibilidad
            const disponibilidad = await verificarDisponibilidad(fecha, horaInicio, horaFin, recurso);
            if (!disponibilidad) {
                throw new Error('El recurso no está disponible en ese horario');
            }
            return true;
        }

        // Agregar validaciones en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const modales = ['modalReservaEstudiante', 'modalReservaDocente'];

            modales.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    const inputs = modal.querySelectorAll('input, select');
                    inputs.forEach(input => {
                        input.addEventListener('change', async function() {
                            const form = this.closest('form');
                            const tipoUsuario = modalId.includes('Estudiante') ? 'Estudiante' : 'Docente';
                            try {
                                await validarFormularioReserva(form, tipoUsuario);
                                form.querySelector('.error-mensaje').style.display = 'none';
                            } catch (error) {
                                form.querySelector('.error-mensaje').textContent = error.message;
                                form.querySelector('.error-mensaje').style.display = 'block';
                            }
                        });
                    });
                }
            });
        });

        // Validación en tiempo real con SweetAlert2 para fecha y horas
        document.addEventListener('DOMContentLoaded', function() {
            const fechaInput = document.getElementById('fecha_unico');
            const horaInicioInput = document.getElementById('horaInicio_unico');
            const horaFinInput = document.getElementById('horaFin_unico');

            // Validar solo la fecha
            fechaInput.addEventListener('change', function() {
                const fecha = fechaInput.value;
                const hoy = new Date();
                const fechaSeleccionada = new Date(fecha + 'T00:00');
                if (fecha && fechaSeleccionada.setHours(0, 0, 0, 0) < hoy.setHours(0, 0, 0, 0)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validación',
                        text: 'No puedes seleccionar una fecha pasada',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    fechaInput.value = '';
                }
            });

            // Validar hora de inicio apenas se seleccione
            horaInicioInput.addEventListener('change', function() {
                const fecha = fechaInput.value;
                const horaInicio = horaInicioInput.value;
                if (!fecha || !horaInicio) return;

                const ahora = new Date();
                const hoyStr = ahora.toISOString().split('T')[0];
                if (fecha === hoyStr) {
                    // Hora de inicio debe ser al menos 10 minutos después de la actual
                    const [h, m] = horaInicio.split(':').map(Number);
                    const fechaHoraInicio = new Date(`${fecha}T${horaInicio}`);
                    const ahoraConMargen = new Date(ahora.getTime() + 10 * 60000);
                    if (fechaHoraInicio <= ahoraConMargen) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validación',
                            text: 'Solo puedes apartar con al menos 10 minutos de anticipación',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        horaInicioInput.value = '';
                    }
                }
            });

            // Validar fecha + horas solo si la fecha es válida y ambas horas están llenas
            function validarHoras() {
                const fecha = fechaInput.value;
                const horaInicio = horaInicioInput.value;
                const horaFin = horaFinInput.value;
                if (!fecha || !horaInicio || !horaFin) return;
                try {
                    validarRegistro(fecha, horaInicio, horaFin);
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validación',
                        text: error.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    // Puedes limpiar el campo que causó el error si lo deseas
                }
            }            horaInicioInput.addEventListener('change', validarHoras);
            horaFinInput.addEventListener('change', validarHoras);
        });

        function cerrarModalReserva(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Función para confirmar cancelación de reserva
        function confirmarCancelacion(idReserva, nombreRecurso, fecha, hora) {
            console.log('Función confirmarCancelacion llamada:', {idReserva, nombreRecurso, fecha, hora});
            
            // Verificar si SweetAlert2 está disponible
            if (typeof Swal === 'undefined') {
                alert('SweetAlert2 no está cargado. Usando confirm básico.');
                if (confirm(`¿Estás seguro de cancelar la reserva?\n\nRecurso: ${nombreRecurso}\nFecha: ${fecha}\nHora: ${hora}`)) {
                    // Crear y enviar formulario
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '../Controlador/Cancelar_Reserva.php';
                    
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'id_reserva';
                    input.value = idReserva;
                    
                    const submitInput = document.createElement('input');
                    submitInput.type = 'hidden';
                    submitInput.name = 'cancelar';
                    submitInput.value = 'true';
                    
                    form.appendChild(input);
                    form.appendChild(submitInput);
                    document.body.appendChild(form);
                    form.submit();
                }
                return;
            }
            
            Swal.fire({
                title: '⚠️ ¿Cancelar Reserva?',
                html: `
                    <div style="text-align: left; margin: 20px 0;">
                        <p><strong>📋 Recurso:</strong> ${nombreRecurso}</p>
                        <p><strong>📅 Fecha:</strong> ${fecha}</p>
                        <p><strong>🕐 Hora:</strong> ${hora}</p>
                    </div>
                    <p style="color: #dc3545; font-weight: bold;">Esta acción no se puede deshacer</p>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '✅ Sí, cancelar',
                cancelButtonText: '❌ No, mantener',
                reverseButtons: true,
                focusCancel: true,
                customClass: {
                    popup: 'swal-wide'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loading
                    Swal.fire({
                        title: 'Cancelando reserva...',
                        text: 'Por favor espera',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Crear y enviar formulario
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '../Controlador/Cancelar_Reserva.php';
                    
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'id_reserva';
                    input.value = idReserva;
                    
                    const submitInput = document.createElement('input');
                    submitInput.type = 'hidden';
                    submitInput.name = 'cancelar';
                    submitInput.value = 'true';
                      form.appendChild(input);
                    form.appendChild(submitInput);
                    document.body.appendChild(form);
                    form.submit();                }
            });
        }
        
        // Script de debug para verificar que todo está funcionando
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔧 [DEBUG] DOM cargado completamente');
            console.log('🔧 [DEBUG] SweetAlert2 disponible:', typeof Swal !== 'undefined');
            console.log('🔧 [DEBUG] Función confirmarCancelacion definida:', typeof confirmarCancelacion !== 'undefined');
            
            // Verificar que los botones de cancelar existen
            const botonesCancelar = document.querySelectorAll('.btn-cancelar');
            console.log('🔧 [DEBUG] Botones de cancelar encontrados:', botonesCancelar.length);
            
            // Verificar cada botón individualmente
            botonesCancelar.forEach((btn, index) => {
                console.log(`🔧 [DEBUG] Botón ${index + 1}:`, {
                    elemento: btn,
                    onclick: btn.getAttribute('onclick'),
                    text: btn.textContent.trim()
                });
                
                // Agregar listener para debug
                btn.addEventListener('click', function(e) {
                    console.log('🔧 [DEBUG] Click detectado en botón:', e.target);
                    console.log('🔧 [DEBUG] Onclick del botón:', e.target.getAttribute('onclick'));
                });
            });
            
            // Test directo de la función
            console.log('🔧 [DEBUG] Probando función confirmarCancelacion...');
            if (typeof confirmarCancelacion === 'function') {
                console.log('🔧 [DEBUG] ✅ Función confirmarCancelacion está disponible');
            } else {
                console.error('🔧 [DEBUG] ❌ Función confirmarCancelacion NO está disponible');
            }
        });
    </script>
    <!-- JAVASCRIPT PARA MENÚ MÓVIL -->
    <script src="../js/sidebar.js"></script>
    <script src="../js/mobile_menu.js"></script>
    <script src="../js/reservas_usuarios.js"></script>
</body>

</html>