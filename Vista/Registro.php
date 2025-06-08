<?php

date_default_timezone_set('America/Bogota'); // Establece la zona horaria a Bogotá, Colombia.




// Incluye el archivo de conexión a la base de datos.
include("../database/conection.php");

// Incluye el archivo que contiene la función para verificar el rol del usuario.
include("../Controlador/control_De_Rol.php");

// Verifica que el usuario tenga el rol de 'Administrador', de lo contrario, lo redirige.
checkRole('Administrador');

setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain.1252');

// --- SECCIÓN OPCIONAL: ACTUALIZACIÓN AUTOMÁTICA DE ESTADO DE RESERVAS ---
// Esta sección actualiza automáticamente a 'Completada' las reservas 'Confirmada' cuya fecha y hora de fin ya pasaron.
// Actualmente NO es necesaria porque solo se usan los estados 'Confirmada' y 'Cancelada'.
// Si en el futuro deseas volver a usar el estado 'Completada', solo descomenta el siguiente bloque:
/*
$ahora = date('Y-m-d H:i:s');
$conn->query("UPDATE registro SET estado = 'Completada' WHERE estado = 'Confirmada' AND CONCAT(fechaReserva, ' ', horaFin) < '$ahora'");
*/
// --- FIN SECCIÓN OPCIONAL ---

// Obtener recursos para el filtro
$recursosResult = $conn->query("SELECT ID_Recurso, nombreRecurso FROM recursos");

// Verificar si se seleccionó un recurso desde el formulario
$recursoFiltrado = isset($_GET['filtro_recurso']) ? $_GET['filtro_recurso'] : '';
// Verificar si se seleccionó una fecha desde el formulario

$fechaFiltrada = isset($_GET['filtro_fecha']) ? $_GET['filtro_fecha'] : '';
// Verificar si se seleccionó una hora desde el formulario
// Verificar si se seleccionó una hora desde y hasta el formulario
$horaDesde = isset($_GET['hora_desde']) ? $_GET['hora_desde'] : '';
$horaHasta = isset($_GET['hora_hasta']) ? $_GET['hora_hasta'] : '';

// Construir la condición SQL
$filtroSQL = "";

if (!empty($recursoFiltrado)) {
    $filtroSQL .= " AND r.ID_Recurso = '" . $conn->real_escape_string($recursoFiltrado) . "'";
}

if (!empty($fechaFiltrada)) {
    $filtroSQL .= " AND r.fechaReserva = '" . $conn->real_escape_string($fechaFiltrada) . "'";
}

// Validación: si ambos campos están llenos, validar que desde <= hasta
if (!empty($horaDesde) && !empty($horaHasta)) {
    if ($horaDesde > $horaHasta) {
        echo "<script>alert('La hora \"desde\" no puede ser mayor que la hora \"hasta\"');</script>";
    } else {
        $filtroSQL .= " AND r.horaInicio >= '" . $conn->real_escape_string($horaDesde) . "' AND r.horaFin <= '" . $conn->real_escape_string($horaHasta) . "'";
    }
} elseif (!empty($horaDesde)) {
    $filtroSQL .= " AND r.horaInicio >= '" . $conn->real_escape_string($horaDesde) . "'";
} elseif (!empty($horaHasta)) {
    $filtroSQL .= " AND r.horaFin <= '" . $conn->real_escape_string($horaHasta) . "'";
}

// ...resto del código...



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Metadatos y enlaces a estilos -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    <title>Registro</title>    <link rel="stylesheet" href="../css/Style.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>



    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

</head>

<body class="Registro">

    <?php    // Incluye la barra lateral de navegación.
    include("../Vista/Sidebar.php");
    ?>
    
    <!-- BOTÓN DE MENÚ MÓVIL -->
    <button class="menu-toggle" id="menuToggle">
        <img src="../Imagen/Iconos/Menu_3lineas.svg" alt="Menú" class="menu-icon">
    </button>
    
    <!-- OVERLAY PARA CERRAR MENÚ -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php



    // Detectar si hay algún filtro activo
    $filtrosActivos = !empty($_GET['filtro_recurso']) || !empty($_GET['filtro_fecha']) || !empty($_GET['hora_desde']) || !empty($_GET['hora_hasta']);
    ?>


    <section class="Topbard">

        <input type="text" id="filtroBusqueda" placeholder="Buscar usuario, recurso o asignatura...">
        <!-- Agregar el contenedor de toast después del Topbard -->
        <div id="toastContainer" class="toast-container"></div>
        <div class="btn-reportes">


            <button
                title="Generar reportes de hoy"
                id="generarReporte"
                class="btn-reporte <?php if ($filtrosActivos) echo 'disabled'; ?>"
                <?php if ($filtrosActivos) echo 'disabled'; ?>>
                <span class="material-symbols-outlined">
                    <img src="../Imagen/Iconos/Today.svg" alt="" />
                </span>
            </button>

            <button title="Generar reportes de mañana" id="generarReporteSiguiente"
                class="btn-reporte <?php if ($filtrosActivos) echo 'disabled'; ?>"
                <?php if ($filtrosActivos) echo 'disabled'; ?>>

                <span class="material-symbols-outlined">
                    <img src="../Imagen/Iconos/Tomorrow.svg" alt="" />
                </span>
            </button>

            <!-- El de la vista actual no se modifica -->
            <!-- Nuevo botón para reporte de la vista actual -->
            <button title="Generar reporte de la vista actual" id="generarReporteVista" class="btn-reporte">
                <span class="material-symbols-outlined">
                    <img src="../Imagen/Iconos/Reporte_Vista.svg" alt="" />
                </span>
            </button>
        </div>

    </section>

    <section class="Table">
        <div class="contenedor-reservas">
            <div class="tituloyboton">
                <button class="btn-agregar" onclick="abrirModalAgregar()">
                    <img src="../Imagen/Iconos/Agregar_Registro.svg" alt="" />
                    <span class="btn-text">Agregar</span>
                </button>

                <h2>Registros</h2>

            </div>
            <form method="GET" class="filtro-form">
                <label for="filtro_recurso">Filtrar por recurso:</label>
                <select name="filtro_recurso" id="filtro_recurso" onchange="this.form.submit()">
                    <option value=""> Todos</option>
                    <?php
                    if ($recursosResult->num_rows > 0) {
                        while ($recurso = $recursosResult->fetch_assoc()) {
                            $selected = ($recurso['ID_Recurso'] == $recursoFiltrado) ? 'selected' : '';
                            echo "<option value='" . $recurso['ID_Recurso'] . "' $selected>" . htmlspecialchars($recurso['nombreRecurso']) . "</option>";
                        }
                    }

                    ?>
                </select>

                <!-- CAMPO DE FECHA -->
                <label for="filtro_fecha">Filtrar por fecha: </label>
                <input type="date" name="filtro_fecha" id="filtro_fecha" value="<?= htmlspecialchars($fechaFiltrada) ?>">

                <!-- CAMPO DE HORA -->
                <!-- NUEVOS CAMPOS DE HORA -->

                <label for="hora_desde">Hora desde:</label>
                <input type="time" name="hora_desde" id="hora_desde" value="<?= htmlspecialchars($horaDesde) ?>">

                <label for="hora_hasta">Hora hasta:</label>
                <input type="time" name="hora_hasta" id="hora_hasta" value="<?= htmlspecialchars($horaHasta) ?>">





                <!-- ddd -->
                <button type="submit" class="btn-agregar">
                    <img src="../Imagen/Iconos/Filtro.svg" alt="" />
                    <span class="btn-text">Filtrar</span>
                </button>

                <button type="button" class="btn-agregar" title="Limpiar Filtro" onclick="window.location.href='Registro.php'">
                    <img src="../Imagen/Iconos/Quitar_Filtro.svg" alt="" />
                    <span class="btn-text">Limpiar</span>
                </button>


            </form>
            <div class="tabla-scroll">
                <table class="tabla-reservas">
                    <thead>
                        <!-- Encabezados de la tabla -->
                        <tr>
                            <th>ID Registro</th>
                            <th>Recurso</th>
                            <th>Fecha</th>
                            <th>Hora Inicio</th>
                            <th>Hora Fin</th>
                            <th>Salón</th> <!-- Nuevo campo Salón -->
                            <th>Código U</th>
                            <th>Nombre Usuario</th>
                            <th>Correo</th>
                            <th>Nombre Docente</th>
                            <th>Asignatura</th>
                            <th>Programa</th>
                            <th>Semestre</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Consulta SQL para obtener los registros de reservas con sus relaciones.

                        $sql = "SELECT 
    r.ID_Registro,
    r.fechaReserva,
    r.horaInicio,
    r.horaFin,
    r.salon,
    r.ID_Recurso,
    rc.nombreRecurso,
    u.nombre AS nombreUsuario,
    u.correo AS correoUsuario,
    u.Codigo_U,
    u.ID_Rol,
    CASE 
        WHEN u.ID_Rol = (SELECT ID_Rol FROM rol WHERE nombreRol = 'Docente') THEN 'No aplica'
        ELSE COALESCE(doc.nombre, 'Sin docente')
    END AS nombreDocente,
    doc.ID_Usuario AS id_docente,
    COALESCE(asig.nombreAsignatura, 'Sin asignatura') AS asignatura,
    asig.ID_Asignatura,
    COALESCE(pr.nombrePrograma, 'Sin programa') AS programa,
    pr.ID_Programa,
    CASE 
        WHEN u.ID_Rol = (SELECT ID_Rol FROM rol WHERE nombreRol = 'Estudiante') THEN COALESCE(r.semestre, 'Sin semestre')
        ELSE 'No aplica'
    END AS semestre,
    r.estado
FROM registro r
LEFT JOIN usuario u ON r.ID_Usuario = u.ID_Usuario
LEFT JOIN recursos rc ON r.ID_Recurso = rc.ID_Recurso
LEFT JOIN docente_asignatura da ON r.ID_DocenteAsignatura = da.ID_DocenteAsignatura
LEFT JOIN usuario doc ON da.ID_Usuario = doc.ID_Usuario
LEFT JOIN asignatura asig ON da.ID_Asignatura = asig.ID_Asignatura
LEFT JOIN programa pr ON asig.ID_Programa = pr.ID_Programa
WHERE 1=1 $filtroSQL
ORDER BY r.fechaReserva DESC, r.horaInicio DESC"; // Ordenar por los más recientes primero

                        // Ejecuta la consulta y obtiene los resultados.
                        $result = $conn->query($sql);
                        $fechaHoy = date("Y-m-d"); // Obtener la fecha actual

                        // Verifica si hay registros disponibles.
                        if ($result->num_rows > 0) {
                            // Itera sobre los resultados y los muestra en la tabla.
                            $fechaAnterior = null;

                            while ($row = $result->fetch_assoc()) {
                                $fechaActual = $row['fechaReserva'];
                                $esHoy = ($fechaActual === $fechaHoy); // Verificar si es el día actual

                                if ($fechaActual !== $fechaAnterior) {
                                    // Mostrar encabezado de día SIN data-registro-id
                                    echo "<tr class='separador-dia'>
        <td colspan='15' style='background-color:#e0e0e0; font-weight:bold; text-align:center;'>
            📅 " . strftime("%A %d de %B de %Y", strtotime($fechaActual)) . "
        </td>
      </tr>";
                                    $fechaAnterior = $fechaActual;
                                }

                                // Agregar clase especial para registros del día actual y cancelados
                                $claseHoy = $esHoy ? "registro-hoy" : "";
                                $claseCancelado = $row['estado'] === 'Cancelada' ? "registro-cancelado" : "";

                                // Combinar las clases
                                $clases = trim("$claseHoy $claseCancelado");

                                // Ahora tu fila normal de datos
                                echo "<tr class='$clases' data-registro-id='" . $row['ID_Registro'] . "'>
<td>" . htmlspecialchars($row['ID_Registro']) . "</td>
<td>" . htmlspecialchars($row['nombreRecurso']) . "</td>
<td>" . date('d/m/Y', strtotime($row['fechaReserva'])) . "</td>
<td>" . date('h:i A', strtotime($row['horaInicio'])) . "</td>
<td>" . date('h:i A', strtotime($row['horaFin'])) . "</td>
<td>" . htmlspecialchars($row['salon']) . "</td>
<td>" . htmlspecialchars($row['Codigo_U']) . "</td>
<td>" . htmlspecialchars($row['nombreUsuario']) . "</td>
<td>" . htmlspecialchars($row['correoUsuario']) . "</td>
<td>" . htmlspecialchars($row['nombreDocente']) . "</td>
<td>" . htmlspecialchars($row['asignatura']) . "</td>
<td>" . htmlspecialchars($row['programa']) . "</td>
<td>" . htmlspecialchars($row['semestre']) . "</td>
<td><span class='status-" . strtolower($row['estado']) . "'>" . $row['estado'] . "</span></td>
<td>
    <div class=\"menu-acciones\">
        <button class=\"menu-boton\" onclick=\"toggleMenu(this)\">
            <img src='../Imagen/Iconos/Menu_3Puntos.svg' alt='' />
        </button>
        <div class=\"menu-desplegable\">
            <a href=\"#\" 
                onclick='mostrarModal({
                    \"ID_Registro\": \"" . $row['ID_Registro'] . "\",
                    \"fechaReserva\": \"" . date('Y-m-d', strtotime($row['fechaReserva'])) . "\",
                    \"horaInicio\": \"" . date('H:i', strtotime($row['horaInicio'])) . "\",
                    \"horaFin\": \"" . date('H:i', strtotime($row['horaFin'])) . "\",
                    \"estado\": \"" . $row['estado'] . "\",
                    \"correo\": \"" . addslashes($row['correoUsuario']) . "\",
                    \"id_recurso\": \"" . addslashes($row['ID_Recurso']) . "\",
                    \"recurso\": \"" . addslashes($row['nombreRecurso']) . "\",
                    \"id_programa\": \"" . addslashes($row['ID_Programa']) . "\",
                    \"programa\": \"" . addslashes($row['programa']) . "\",
                    \"id_docente\": \"" . addslashes($row['id_docente']) . "\",
                    \"docente\": \"" . addslashes($row['nombreDocente']) . "\",
                    \"id_asignatura\": \"" . addslashes($row['ID_Asignatura']) . "\",
                    \"asignatura\": \"" . addslashes($row['asignatura']) . "\",
                    \"salon\": \"" . addslashes($row['salon']) . "\",
                    \"semestre\": \"" . addslashes($row['semestre']) . "\",
                }); return false;' class=\"menu-opcion\">Modificar</a>
            <a href=\"javascript:void(0)\" onclick=\"confirmarEliminar('" . $row['ID_Registro'] . "')\" class=\"menu-opcion\">Eliminar</a>
        </div>
    </div>
</td>
</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='12' class='sin-reservas'>No hay registros disponibles</td></tr>";
                        }

                        // Obtener usuarios, programas y recursos
                        $usuarios = $conn->query("SELECT ID_Usuario, nombre, ID_Rol FROM usuario");
                        $usuariosData = [];
                        while ($u = $usuarios->fetch_assoc()) {
                            $usuariosData[] = $u;
                        }

                        // Obtener programas para estudiantes
                        $programas = $conn->query("SELECT ID_Programa, nombrePrograma FROM programa");
                        $programasData = [];
                        while ($p = $programas->fetch_assoc()) {
                            $programasData[] = $p;
                        }

                        // Obtener recursos para el modal
                        $recursos = $conn->query("SELECT ID_Recurso, nombreRecurso FROM recursos");
                        $recursosData = [];
                        while ($r = $recursos->fetch_assoc()) {
                            $recursosData[] = $r;
                        }

                        // Obtener usuarios para el modal de agregar antes de cerrar la conexión
                        $usuarios = $conn->query("
                            SELECT u.ID_Usuario, u.nombre, u.ID_Rol, u.codigo_u, u.correo, r.nombreRol 
                            FROM usuario u 
                            INNER JOIN rol r ON u.ID_Rol = r.ID_Rol
                            ORDER BY r.nombreRol, u.nombre
                        ");

                        echo "<!-- Debug roles: -->";
                        $usuariosData = [];
                        while ($u = $usuarios->fetch_assoc()) {
                            echo "<!-- {$u['nombre']} - Rol: {$u['nombreRol']} (ID: {$u['ID_Rol']}) -->";
                            $usuariosData[] = $u;
                        }

                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Modal para Agregar Registro -->
    <div id="modalAgregar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModalAgregar()">&times;</span>
            <h2>Agregar Registro</h2>
            <form id="formAgregarRegistro" class="form-dinamico">
                <div class="form-row">
                    <div class="form-group">
                        <label for="usuario_agregar">Usuario</label>
                        <select id="usuario_agregar" name="usuario" class="input-dinamico">
                            <option value="">Seleccione un usuario</option>
                            <?php foreach ($usuariosData as $u): ?>
                                <option value="<?= $u['ID_Usuario'] ?>" data-rol="<?= $u['ID_Rol'] ?>" data-correo="<?= htmlspecialchars($u['correo'] ?? '') ?>">
                                    <?= htmlspecialchars($u['nombre']) ?> (Código: <?= htmlspecialchars($u['codigo_u']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="correo_agregar">Correo</label>
                        <input type="email" id="correo_agregar" name="correo" class="input-dinamico" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_agregar">Fecha</label>
                        <input type="date" id="fecha_agregar" name="fecha" class="input-dinamico" required>
                    </div>
                    <div class="form-group">
                        <label for="recurso_agregar">Recurso</label>
                        <select id="recurso_agregar" name="recurso" class="input-dinamico" required>
                            <option value="">Seleccione un recurso</option>
                            <?php foreach ($recursosData as $r): ?>
                                <option value="<?= $r['ID_Recurso'] ?>" data-nombre="<?= htmlspecialchars($r['nombreRecurso']) ?>">
                                    <?= htmlspecialchars($r['nombreRecurso']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="hora_inicio_agregar">Hora Inicio</label>
                        <input type="time" id="hora_inicio_agregar" name="horaInicio" class="input-dinamico" required>
                    </div>
                    <div class="form-group">
                        <label for="hora_fin_agregar">Hora Fin</label>
                        <input type="time" id="hora_fin_agregar" name="horaFin" class="input-dinamico" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="programa_agregar">Programa/Dependencia</label>
                        <select id="programa_agregar" name="programa" class="input-dinamico">
                            <option value="">Seleccione un programa</option>
                            <?php foreach ($programasData as $p): ?>
                                <option value="<?= $p['ID_Programa'] ?>"><?= htmlspecialchars($p['nombrePrograma']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="docente_agregar">Docente</label>
                        <select id="docente_agregar" name="docente" class="input-dinamico">
                            <option value="">Seleccione un Docente</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="asignatura_agregar">Asignatura</label>
                        <select id="asignatura_agregar" name="asignatura" class="input-dinamico">
                            <option value="">Seleccione una Asignatura</option>
                        </select>
                    </div>
                </div>                <div class="form-row">
                    <div class="form-group" id="grupo_salon_agregar">
                        <label for="salon_agregar">Salón</label>
                        <input type="text" id="salon_agregar" name="salon" class="input-dinamico" placeholder="Ej: 2B, 1A...">
                        <small class="form-note">💡 Ingrese el salón donde normalmente da clase o donde se realizará la actividad</small>
                    </div>
                    <div class="form-group">
                        <label for="semestre_agregar">Semestre</label>
                        <select id="semestre_agregar" name="semestre" class="input-dinamico">
                            <option value="">Seleccione el semestre</option>
                            <?php $romanos = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
                            for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?= $romanos[$i - 1] ?>"><?= $romanos[$i - 1] ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="celular_agregar">Celular</label>
                        <input type="text" id="celular_agregar" name="celular" class="input-dinamico">
                    </div>
                </div>
                <div class="form-row" id="grupo_nombre_estudiante" style="display:none;">
                    <div class="form-group">
                        <label for="nombre_estudiante_agregar">Nombre del Estudiante</label>
                        <input type="text" id="nombre_estudiante_agregar" name="nombre_estudiante" class="input-dinamico" readonly>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-confirmar">Guardar</button>
                    <button type="button" onclick="cerrarModalAgregar()" class="btn-cancelar">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Fin Modal Agregar -->

    <!-- Modal para Modificar Registro -->
    <div id="modalModificar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2>Modificar Registro</h2>
            <form id="formModificarRegistro" class="form-dinamico">
                <input type="hidden" id="registro_id" name="registro_id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="correo_modificar">Correo</label>
                        <input type="email" id="correo_modificar" name="correo" class="input-dinamico" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_modificar">Fecha</label>
                        <input type="date" id="fecha_modificar" name="fecha" class="input-dinamico" required>
                    </div>
                    <div class="form-group">
                        <label for="recurso_modificar">Recurso</label>
                        <select id="recurso_modificar" name="recurso" class="input-dinamico" required>
                            <option value="">Seleccione un recurso</option>
                            <?php foreach ($recursosData as $r): ?>
                                <option value="<?= $r['ID_Recurso'] ?>" data-nombre="<?= htmlspecialchars($r['nombreRecurso']) ?>">
                                    <?= htmlspecialchars($r['nombreRecurso']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="hora_inicio_modificar">Hora Inicio</label>
                        <input type="time" id="hora_inicio_modificar" name="horaInicio" class="input-dinamico" required>
                    </div>
                    <div class="form-group">
                        <label for="hora_fin_modificar">Hora Fin</label>
                        <input type="time" id="hora_fin_modificar" name="horaFin" class="input-dinamico" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="programa_modificar">Programa/Dependencia</label>
                        <select id="programa_modificar" name="programa" class="input-dinamico">
                            <option value="">Seleccione un programa</option>
                            <?php foreach ($programasData as $p): ?>
                                <option value="<?= $p['ID_Programa'] ?>"><?= htmlspecialchars($p['nombrePrograma']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="docente_modificar">Docente</label>
                        <select id="docente_modificar" name="docente" class="input-dinamico">
                            <option value="">Seleccione un Docente</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="asignatura_modificar">Asignatura</label>
                        <select id="asignatura_modificar" name="asignatura" class="input-dinamico">
                            <option value="">Seleccione una Asignatura</option>
                        </select>
                    </div>
                </div>                <div class="form-row">
                    <div class="form-group" id="grupo_salon_modificar">
                        <label for="salon_modificar">Salón</label>
                        <input type="text" id="salon_modificar" name="salon" class="input-dinamico" placeholder="Ej: 2B, 1A...">
                        <small class="form-note">💡 Ingrese el salón donde normalmente da clase o donde se realizará la actividad</small>
                    </div>
                    <div class="form-group">
                        <label for="semestre_modificar">Semestre</label>
                        <select id="semestre_modificar" name="semestre" class="input-dinamico">
                            <option value="">Seleccione el semestre</option>
                            <?php $romanos = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
                            for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?= $romanos[$i - 1] ?>"><?= $romanos[$i - 1] ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="celular_modificar">Celular</label>
                        <input type="text" id="celular_modificar" name="celular" class="input-dinamico">
                    </div>
                </div>
                <div class="form-row" id="grupo_nombre_estudiante_modificar" style="display:none;">
                    <div class="form-group">
                        <label for="nombre_estudiante_modificar">Nombre del Estudiante</label>
                        <input type="text" id="nombre_estudiante_modificar" name="nombre_estudiante" class="input-dinamico" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado">
                            <option value="Confirmada">Confirmada</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-confirmar">Guardar Cambios</button>
                    <button type="button" onclick="cerrarModal()" class="btn-cancelar">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Fin Modal Modificar -->

    <!-- Modal para Confirmar Eliminación -->
    <div id="modalEliminar" class="modal">
        <div class="modal-content" style="max-width: 400px; text-align: center;">
            <span class="close" onclick="cerrarModalEliminar()">&times;</span>
            <h2>¿Eliminar registro?</h2>
            <p>¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.</p>
            <div class="form-actions" style="justify-content: center;">
                <button id="btnConfirmDelete" class="btn-confirmar">Sí, eliminar</button>
                <button type="button" onclick="cerrarModalEliminar()" class="btn-cancelar">Cancelar</button>
            </div>
        </div>
    </div>
    <!-- Fin Modal Eliminar -->

    <script src="../js/registro.js"></script>
    <script>
        $(document).ready(function() {
            // Al cambiar el usuario, poner el correo correspondiente en el campo correo y mostrar nombre estudiante si aplica
            $('#usuario_agregar').on('change', function() {
                var selected = $(this).find('option:selected');
                var correo = selected.data('correo') || '';
                var rol = selected.data('rol');
                var nombre = selected.text().split(' (')[0];
                $('#correo_agregar').val(correo);
                if (rol == 1) { // 1 = Estudiante
                    $('#grupo_nombre_estudiante').show();
                    $('#nombre_estudiante_agregar').val(nombre);
                } else {
                    $('#grupo_nombre_estudiante').hide();
                    $('#nombre_estudiante_agregar').val('');
                }
            });

            // Cargar docentes según programa seleccionado
            $('#programa_agregar').on('change', function() {
                var programaId = $(this).val();
                var docenteSelect = $('#docente_agregar');
                docenteSelect.html('<option value="">Cargando...</option>');
                $('#asignatura_agregar').html('<option value="">Seleccione una Asignatura</option>');
                if (programaId) {
                    $.ajax({
                        url: '../Controlador/ControladorObtener.php?tipo=docentes',
                        method: 'POST',
                        data: {
                            id_programa: programaId
                        },
                        dataType: 'json',
                        success: function(data) {
                            docenteSelect.html('<option value="">Seleccione un Docente</option>');
                            if (data.data) {
                                data.data.forEach(function(docente) {
                                    docenteSelect.append('<option value="' + docente.ID_Usuario + '">' + docente.nombre + '</option>');
                                });
                            }
                        }
                    });
                } else {
                    docenteSelect.html('<option value="">Seleccione un Docente</option>');
                }
            });

            // Cargar asignaturas según docente y programa
            $('#docente_agregar').on('change', function() {
                var docenteId = $(this).val();
                var programaId = $('#programa_agregar').val();
                var asignaturaSelect = $('#asignatura_agregar');
                asignaturaSelect.html('<option value="">Cargando...</option>');
                if (docenteId && programaId) {
                    $.ajax({
                        url: '../Controlador/ControladorObtener.php?tipo=asignaturas',
                        method: 'POST',
                        data: {
                            id_docente: docenteId,
                            id_programa: programaId
                        },
                        dataType: 'json',
                        success: function(data) {
                            asignaturaSelect.html('<option value="">Seleccione una Asignatura</option>');
                            if (data.data) {
                                data.data.forEach(function(asig) {
                                    asignaturaSelect.append('<option value="' + asig.ID_Asignatura + '">' + asig.nombreAsignatura + '</option>');
                                });
                            }
                        }
                    });
                } else {
                    asignaturaSelect.html('<option value="">Seleccione una Asignatura</option>');
                }
            });

            // --- MODIFICAR: Lógica dinámica para dependencias y autocompletado ---
        });

        // --- MODIFICAR: Lógica dinámica para dependencias y autocompletado ---
        function cargarDocentesModificar(programaId, docenteIdSeleccionado) {
            var docenteSelect = $('#docente_modificar');
            docenteSelect.html('<option value="">Cargando...</option>');
            $('#asignatura_modificar').html('<option value="">Seleccione una Asignatura</option>');
            if (programaId) {
                $.ajax({
                    url: '../Controlador/ControladorObtener.php?tipo=docentes',
                    method: 'POST',
                    data: {
                        id_programa: programaId
                    },
                    dataType: 'json',
                    success: function(data) {
                        docenteSelect.html('<option value="">Seleccione un Docente</option>');
                        if (data.data) {
                            data.data.forEach(function(docente) {
                                var selected = docenteIdSeleccionado == docente.ID_Usuario ? 'selected' : '';
                                docenteSelect.append('<option value="' + docente.ID_Usuario + '" ' + selected + '>' + docente.nombre + '</option>');
                            });
                        }
                    }
                });
            } else {
                docenteSelect.html('<option value="">Seleccione un Docente</option>');
            }
        }

        function cargarAsignaturasModificar(docenteId, programaId, asignaturaIdSeleccionada) {
            var asignaturaSelect = $('#asignatura_modificar');
            asignaturaSelect.html('<option value="">Cargando...</option>');
            if (docenteId && programaId) {
                $.ajax({
                    url: '../Controlador/ControladorObtener.php?tipo=asignaturas',
                    method: 'POST',
                    data: {
                        id_docente: docenteId,
                        id_programa: programaId
                    },
                    dataType: 'json',
                    success: function(data) {
                        asignaturaSelect.html('<option value="">Seleccione una Asignatura</option>');
                        if (data.data) {
                            data.data.forEach(function(asig) {
                                var selected = asignaturaIdSeleccionada == asig.ID_Asignatura ? 'selected' : '';
                                asignaturaSelect.append('<option value="' + asig.ID_Asignatura + '" ' + selected + '>' + asig.nombreAsignatura + '</option>');
                            });
                        }
                    }
                });
            } else {
                asignaturaSelect.html('<option value="">Seleccione una Asignatura</option>');
            }
        }
        // --- FIN MODIFICAR ---

        // Función para mostrar el modal de modificar y rellenar los campos
        function mostrarModal(data) {
            $('#modalModificar').show();
            $('#registro_id').val(data.ID_Registro || '');
            $('#correo_modificar').val(data.correo || '');
            $('#fecha_modificar').val(data.fechaReserva || '');
            $('#hora_inicio_modificar').val(data.horaInicio || '');
            $('#hora_fin_modificar').val(data.horaFin || '');
            $('#estado').val(data.estado || 'Confirmada');
            $('#salon_modificar').val(data.salon || '');
            $('#semestre_modificar').val(data.semestre || '');
            $('#celular_modificar').val(data.celular || '');

            if (data.id_recurso) {
                $('#recurso_modificar').val(data.id_recurso);
            }
            if (data.id_programa) {
                $('#programa_modificar').val(data.id_programa);
            }

            // Para depuración
            console.log('ID Programa:', data.id_programa);
            console.log('ID Docente:', data.id_docente);
            console.log('ID Asignatura:', data.id_asignatura);

            cargarDocentesModificar(data.id_programa, data.id_docente);

            var docenteInterval = setInterval(function() {
                var docenteSel = $('#docente_modificar');
                if (docenteSel.find('option[value="' + data.id_docente + '"]').length > 0) {
                    clearInterval(docenteInterval);
                    docenteSel.val(data.id_docente);

                    cargarAsignaturasModificar(data.id_docente, data.id_programa, data.id_asignatura);

                    var asignaturaInterval = setInterval(function() {
                        var asigSel = $('#asignatura_modificar');
                        if (asigSel.find('option[value="' + data.id_asignatura + '"]').length > 0) {
                            clearInterval(asignaturaInterval);
                            asigSel.val(data.id_asignatura);
                        }
                    }, 100);
                }
            }, 100);        }

        // Al cambiar el programa en el modal de modificar, actualizar docentes y asignaturas
        $('#programa_modificar').on('change', function() {
            var programaId = $(this).val();
            // Limpiar selección de docente y asignatura
            $('#docente_modificar').val('');
            $('#asignatura_modificar').val('');
            cargarDocentesModificar(programaId, '');
        });
        // Al cambiar el docente en el modal de modificar, actualizar asignaturas
        $('#docente_modificar').on('change', function() {
            var docenteId = $(this).val();
            var programaId = $('#programa_modificar').val();
            $('#asignatura_modificar').val('');
            cargarAsignaturasModificar(docenteId, programaId, '');        });
    </script>
    
    <script src="../js/sidebar.js"></script>
    <script src="../js/mobile_menu.js"></script>
</body>

</html>