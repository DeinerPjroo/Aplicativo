<?php

date_default_timezone_set('America/Bogota'); // Establece la zona horaria a Bogotá, Colombia.




// Incluye el archivo de conexión a la base de datos.
include("../database/conexion.php");

// Incluye el archivo que contiene la función para verificar el rol del usuario.
include("../Controlador/control_De_Rol.php");

// Verifica que el usuario tenga el rol de 'Administrador', de lo contrario, lo redirige.
checkRole('Administrador');

setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain.1252');

// Obtener recursos para el filtro
$recursosResult = $conn->query("SELECT ID_Recurso, nombreRecurso FROM recursos");

// Verificar si se seleccionó un recurso desde el formulario
$recursoFiltrado = isset($_GET['filtro_recurso']) ? $_GET['filtro_recurso'] : '';
// Verificar si se seleccionó una fecha desde el formulario

$fechaFiltrada = isset($_GET['filtro_fecha']) ? $_GET['filtro_fecha'] : '';



// Construir la condición SQL
$filtroSQL = "";

if (!empty($recursoFiltrado)) {
    $filtroSQL .= " AND r.ID_Recurso = '" . $conn->real_escape_string($recursoFiltrado) . "'";
}

if (!empty($fechaFiltrada)) {
    $filtroSQL .= " AND r.fechaReserva = '" . $conn->real_escape_string($fechaFiltrada) . "'";
}



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Metadatos y enlaces a estilos -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="../css/Style.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
    .contenedor-usuarios {
        width: 90%;
        margin: 20px auto;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-left: 110px !important;
    }

    .contenedor-usuarios h2 {
        color: #333;
        margin-bottom: 15px;
        text-align: center;
    }

    .tabla-usuarios {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .tabla-usuarios th,
    .tabla-usuarios td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .tabla-usuarios th {
        background-color: rgb(45, 158, 178);
        color: white;
        font-weight: 600;
    }

    .tabla-usuarios tr:hover {
        background-color: #f5f5f5;
    }

    .sin-usuarios {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
        font-style: italic;
        background-color: #f8f9fa;
        border-radius: 5px;
        margin: 20px 0;
    }

    .btn {
        padding: 8px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin: 5px;
    }


    .btn-modificar {
        background-color: #ffc107;
        color: black;
    }

    .btn-eliminar {
        background-color: #dc3545;
        color: white;
    }

    .btn:hover {
        opacity: 0.9;
    }

    /* Estilos del modal mejorados */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        /* Cambiado de auto a hidden para evitar el scroll externo */
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 400px;
        max-width: 90%;
        border-radius: 10px;
        max-height: 80vh;
        /* Altura máxima del 80% de la ventana */
        overflow-y: auto;
        /* Añadir scroll vertical solo cuando sea necesario */
        position: relative;
        /* Para posicionamiento de elementos internos */
    }

    /* Estilos para la barra de desplazamiento */
    .modal-content::-webkit-scrollbar {
        width: 8px;
    }

    .modal-content::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .modal-content::-webkit-scrollbar-thumb {
        background: #d07c2e;
        border-radius: 10px;
    }

    .modal-content::-webkit-scrollbar-thumb:hover {
        background: #b9651f;
    }

    /* Mantén el botón de cerrar siempre visible */
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        position: sticky;
        top: 0;
        right: 0;
    }

    /* Resto de estilos del modal */
    .modal-content label {
        display: block;
        margin-top: 10px;
        font-weight: bold;
    }

    .modal-content input,
    .modal-content select {
        width: 100%;
        padding: 8px;
        margin-top: 5px;
        margin-bottom: 15px;
        border-radius: 4px;
        border: 1px solid #ccc;
        box-sizing: border-box;
        /* Asegura que el padding no afecte el ancho total */
    }

    .modal-content button[type="submit"] {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 10px;
        width: 100%;
        border-radius: 4px;
        cursor: pointer;
        margin-top: 10px;
        margin-bottom: 5px;
    }

    .modal-content button[type="submit"]:hover {
        background-color: #218838;
    }

    /* barra de busques estilo / */
    /* hola */
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
    }

    .barra-superior {
        background-color: #d07c2e;
        /* Naranja similar */
        padding: 15px 0;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .busqueda-container {
        background-color: white;
        padding: 6px 10px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        width: 600px;
        max-width: 90%;
    }

    .busqueda-container input[type="text"] {
        border: none;
        outline: none;
        font-size: 14px;
        flex: 1;
        padding: 8px;
    }

    .busqueda-container button {
        background-color: #d07c2e;
        color: white;
        border: none;
        padding: 8px 12px;
        margin-left: 8px;
        border-radius: 4px;
        cursor: pointer;
    }

    .busqueda-container button:hover {
        background-color: #b9651f;
    }

    #resultado {
        text-align: center;
        margin-top: 30px;
        font-size: 18px;
    }

    /* Estilo para el mensaje de error */
    .error-message {
        color: #e74c3c;
        font-size: 12px;
        margin-top: -12px;
        margin-bottom: 8px;
    }
</style>

</head>

<body class="Registro">

    <?php
    // Incluye la barra lateral de navegación.
    include("../Vista/Sidebar.php");
    ?>

    <section class="Topbard">

        <input type="text" id="filtroBusqueda" placeholder="Buscar usuario, recurso o asignatura...">
        <div class="btn-reportes">
            <button title="Generar reportes de hoy" id="generarReporte" class="btn-reporte"><span class="material-symbols-outlined">
                    today
                </span></button>
            <button title="Generar reportes de mañana" id="generarReporteSiguiente" class="btn-reporte"><span class="material-symbols-outlined">
                    event_upcoming
                </span></button>
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
            <table class="tabla-reservas">
                <thead>
                    <!-- Encabezados de la tabla -->
                    <tr>
                        <th>Recurso</th>
                        <th>Fecha</th>
                        <th>Hora Inicio</th>
                        <th>Hora Fin</th>
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
                    rc.nombreRecurso,
                    u.nombre AS nombreUsuario,
                    u.correo AS correoUsuario,
                    CASE 
                        WHEN u.ID_Rol = (SELECT ID_Rol FROM rol WHERE nombreRol = 'Docente') THEN 'No aplica'
                        ELSE COALESCE(doc.nombre, 'Sin docente')
                    END AS nombreDocente,
                    COALESCE(asig.nombreAsignatura, 'Sin asignatura') AS asignatura,
                    COALESCE(pr.nombrePrograma, 'Sin programa') AS programa,
                    CASE 
                        WHEN u.ID_Rol = (SELECT ID_Rol FROM rol WHERE nombreRol = 'Estudiante') THEN COALESCE(u.semestre, 'Sin semestre')
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
                                // Mostrar encabezado de día
                                echo "<tr class='separador-dia' data-registro-id='" . $row['ID_Registro'] . "'>
                <td colspan='12' style='background-color:#e0e0e0; font-weight:bold; text-align:center;'>
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
        <td>" . htmlspecialchars($row['nombreRecurso']) . "</td>
        <td>" . date('d/m/Y', strtotime($row['fechaReserva'])) . "</td>
        <td>" . date('h:i A', strtotime($row['horaInicio'])) . "</td>
        <td>" . date('h:i A', strtotime($row['horaFin'])) . "</td>
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
                    <a href=\"#\" onclick='mostrarModal({
                        \"ID_Registro\": \"" . $row['ID_Registro'] . "\",
                        \"fechaReserva\": \"" . date('Y-m-d', strtotime($row['fechaReserva'])) . "\",
                        \"horaInicio\": \"" . date('H:i', strtotime($row['horaInicio'])) . "\",
                        \"horaFin\": \"" . date('H:i', strtotime($row['horaFin'])) . "\",
                        \"estado\": \"" . $row['estado'] . "\"
                    }); return false;' class=\"menu-opcion\">Modificar</a>
                    <a href=\"../Controlador/Eliminar_Reserva.php?id=" . $row['ID_Registro'] . "\" class=\"menu-opcion\">Eliminar</a>
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
                        SELECT u.ID_Usuario, u.nombre, u.ID_Rol, r.nombreRol 
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
    </section>

    <script>
        // Agregar esta función de validación común
        function validarRegistro(fecha, horaInicio, horaFin) {
            // Validar fecha no anterior a hoy
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            const fechaSeleccionada = new Date(fecha);
            fechaSeleccionada.setHours(0, 0, 0, 0);

            if (fechaSeleccionada < hoy) {
                throw new Error('No puedes seleccionar una fecha pasada');
            }

            // Validar horario de operación (6:00 AM - 10:00 PM)
            const horaInicioNum = parseInt(horaInicio.split(':')[0]);
            const horaFinNum = parseInt(horaFin.split(':')[0]);
            if (horaInicioNum < 6 || horaFinNum > 22) {
                throw new Error('El horario de reserva debe estar entre las 6:00 AM y las 10:00 PM');
            }

            // Validar hora actual si es el mismo día
            const fechaHoraInicio = new Date(`${fecha}T${horaInicio}`);
            const fechaHoraFin = new Date(`${fecha}T${horaFin}`);
            const ahora = new Date();

            if (fechaSeleccionada.getTime() === hoy.getTime() && fechaHoraInicio < ahora) {
                throw new Error('No puedes seleccionar una hora que ya pasó');
            }

            // Validar que hora fin sea posterior a hora inicio
            if (fechaHoraFin <= fechaHoraInicio) {
                throw new Error('La hora de finalización debe ser posterior a la hora de inicio');
            }

            return true;
        }

        // Script de busaqueda en la tabla de reservas.
        // Este script permite filtrar las filas de la tabla según el texto ingresado en el campo de búsqueda.
        document.addEventListener("DOMContentLoaded", () => {
            const input = document.getElementById("filtroBusqueda");
            input.addEventListener("keyup", () => {
                const filtro = input.value.toLowerCase();
                const filas = document.querySelectorAll(".tabla-reservas tbody tr");

                filas.forEach(fila => {
                    const texto = fila.textContent.toLowerCase();
                    if (texto.includes(filtro)) {
                        fila.style.display = "";
                    } else {
                        fila.style.display = "none";
                    }
                });
            });

            document.getElementById("generarReporte").addEventListener("click", () => {
                const fechaHoy = new Date();
                const dia = String(fechaHoy.getDate()).padStart(2, '0');
                const mes = String(fechaHoy.getMonth() + 1).padStart(2, '0');
                const anio = fechaHoy.getFullYear();
                const fechaObjetivo = `${dia}/${mes}/${anio}`; // Formato dd/mm/yyyy
                const fechaArchivo = `${anio}-${mes}-${dia}`; // Formato yyyy-mm-dd

                const filas = document.querySelectorAll(".tabla-reservas tbody tr.registro-hoy");
                let reporte = `==========================================\n`;
                reporte += `     REPORTE DE RECURSOS - ${fechaObjetivo}\n`;
                reporte += `==========================================\n\n`;

                let registrosEncontrados = false;

                filas.forEach(fila => {
                    const columnas = fila.querySelectorAll("td");
                    const datos = Array.from(columnas).map(columna => columna.textContent.trim());

                    registrosEncontrados = true;
                    reporte += `------------------------------------------\n`;
                    reporte += `Recurso:    ${datos[0]}\n`;
                    reporte += `Fecha:      ${datos[1]}\n`;
                    reporte += `Inicio:     ${datos[2]}\n`;
                    reporte += `Fin:        ${datos[3]}\n`;
                    reporte += `Usuario:    ${datos[4]}\n`;
                    reporte += `Correo:     ${datos[5]}\n`;
                    reporte += `Docente:    ${datos[6]}\n`;
                    reporte += `Asignatura: ${datos[7]}\n`;
                    reporte += `Programa:   ${datos[8]}\n`;
                    reporte += `Semestre:   ${datos[9]}\n`;
                    reporte += `Estado:     ${datos[10]}\n`;
                    reporte += `------------------------------------------\n\n`;
                });

                if (!registrosEncontrados) {
                    reporte += "No hay recursos programados para esta fecha.\n";
                }

                reporte += `\n==========================================\n`;
                reporte += `Fin del reporte - Generado: ${new Date().toLocaleString()}\n`;
                reporte += `==========================================`;

                const blob = new Blob([reporte], {
                    type: "text/plain"
                });
                const enlace = document.createElement("a");
                enlace.href = URL.createObjectURL(blob);
                enlace.download = `Reporte_Recursos_${fechaArchivo}.txt`;
                enlace.click();
            });

            document.getElementById("generarReporteSiguiente").addEventListener("click", () => {
                const hoy = new Date();
                hoy.setDate(hoy.getDate() + 1); // Obtener fecha de mañana

                const dia = String(hoy.getDate()).padStart(2, '0');
                const mes = String(hoy.getMonth() + 1).padStart(2, '0');
                const anio = hoy.getFullYear();
                const fechaObjetivo = `${dia}/${mes}/${anio}`; // Formato dd/mm/yyyy
                const fechaArchivo = `${anio}-${mes}-${dia}`; // Formato yyyy-mm-dd

                const filas = document.querySelectorAll(".tabla-reservas tbody tr:not(.separador-dia)");
                let reporte = `==========================================\n`;
                reporte += `     REPORTE DE RECURSOS - ${fechaObjetivo}\n`;
                reporte += `==========================================\n\n`;

                let registrosEncontrados = false;

                filas.forEach(fila => {
                    const columnas = fila.querySelectorAll("td");
                    const datos = Array.from(columnas).map(columna => columna.textContent.trim());

                    // Verificar si la fecha de la fila coincide con la fecha objetivo
                    if (datos[1] === fechaObjetivo) {
                        registrosEncontrados = true;
                        reporte += `------------------------------------------\n`;
                        reporte += `Recurso:    ${datos[0]}\n`;
                        reporte += `Fecha:      ${datos[1]}\n`;
                        reporte += `Inicio:     ${datos[2]}\n`;
                        reporte += `Fin:        ${datos[3]}\n`;
                        reporte += `Usuario:    ${datos[4]}\n`;
                        reporte += `Correo:     ${datos[5]}\n`;
                        reporte += `Docente:    ${datos[6]}\n`;
                        reporte += `Asignatura: ${datos[7]}\n`;
                        reporte += `Programa:   ${datos[8]}\n`;
                        reporte += `Semestre:   ${datos[9]}\n`;
                        reporte += `Estado:     ${datos[10]}\n`;
                        reporte += `------------------------------------------\n\n`;
                    }
                });

                if (!registrosEncontrados) {
                    reporte += "No hay recursos programados para esta fecha.\n";
                }

                reporte += `\n==========================================\n`;
                reporte += `Fin del reporte - Generado: ${new Date().toLocaleString()}\n`;
                reporte += `==========================================`;

                // Crear y descargar el archivo
                const blob = new Blob([reporte], {
                    type: "text/plain"
                });
                const enlace = document.createElement("a");
                enlace.href = URL.createObjectURL(blob);
                enlace.download = `Reporte_Recursos_${fechaArchivo}.txt`;
                enlace.click();
            });


        });

        function toggleMenu(button) {
            const menu = button.nextElementSibling; // Selecciona el menú desplegable asociado al botón
            menu.style.display = menu.style.display === "block" ? "none" : "block";
        }

        // Modificar la función guardarCambios
        function guardarCambios(event) {
            event.preventDefault();
            
            try {
                const fecha = document.getElementById('fecha').value;
                const horaInicio = document.getElementById('hora_inicio').value;
                const horaFin = document.getElementById('hora_fin').value;

                validarRegistro(fecha, horaInicio, horaFin);

                const formData = new FormData(document.getElementById('formModificar'));
                
                fetch('../Controlador/Modificar_Registro.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    if (result === 'success') {
                        alert('Registro actualizado correctamente');
                        cerrarModal();
                        location.reload();
                    } else {
                        throw new Error(result);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al actualizar el registro: ' + error.message);
                });
            } catch (error) {
                alert(error.message);
            }
        }

        // Funciones para el modal de modificar
        function mostrarModal(registro) {
            document.getElementById('registro_id').value = registro.ID_Registro;
            document.getElementById('fecha').value = registro.fechaReserva;
            document.getElementById('hora_inicio').value = registro.horaInicio;
            document.getElementById('hora_fin').value = registro.horaFin;
            document.getElementById('estado').value = registro.estado;

            document.getElementById('modalModificar').style.display = 'block';
        }

        function cerrarModal() {
            document.getElementById('modalModificar').style.display = 'none';
        }

        // Modificar la función guardarNuevoRegistro
        function guardarNuevoRegistro(event) {
            event.preventDefault();
            
            try {
                const fecha = document.getElementById('fecha_agregar').value;
                const horaInicio = document.getElementById('hora_inicio_agregar').value;
                const horaFin = document.getElementById('hora_fin_agregar').value;

                validarRegistro(fecha, horaInicio, horaFin);

                const formData = new FormData(document.getElementById('formAgregar'));
                
                fetch('../Controlador/Verificar_Disponibilidad.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.disponible) {
                        return fetch('../Controlador/Agregar_Registro.php', {
                            method: 'POST',
                            body: formData
                        });
                    } else {
                        throw new Error('El recurso no está disponible en ese horario');
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Registro agregado correctamente');
                        cerrarModalAgregar();
                        location.reload();
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud: ' + error.message);
                });
            } catch (error) {
                alert(error.message);
            }
        }

        // Funciones para el modal de agregar
        function abrirModalAgregar() {
            const modal = document.getElementById('modalAgregar');
            if (modal) {
                modal.style.display = 'block';
            }
        }

        function cerrarModalAgregar() {
            const modal = document.getElementById('modalAgregar');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        function cargarDatosUsuario(idUsuario) {
            const selectUsuario = document.getElementById('usuario_agregar');
            const selectedOption = selectUsuario.options[selectUsuario.selectedIndex];
            const rol = selectedOption.getAttribute('data-rol');

            console.log('Rol seleccionado:', rol); // Para debugging

            // Ocultar todos los campos adicionales primero
            document.getElementById('campoAsignaturas').style.display = 'none';
            document.getElementById('campoPrograma').style.display = 'none';
            document.getElementById('campoDocente').style.display = 'none';

            // Validar el rol del usuario
            if (rol === '2') { // Docente
                fetch(`../Controlador/Obtener_Asignaturas.php?id_usuario=${idUsuario}`)
                    .then(res => res.json())
                    .then(data => {
                        const listaAsignaturas = document.getElementById('listaAsignaturas');
                        listaAsignaturas.innerHTML = '';
                        data.forEach(asig => {
                            listaAsignaturas.innerHTML += `<div>${asig.nombreAsignatura}</div>`;
                        });
                        document.getElementById('campoAsignaturas').style.display = 'block';
                    })
                    .catch(error => console.error('Error:', error));
            } else if (rol === '1') { // Estudiante
                document.getElementById('campoPrograma').style.display = 'block';
                $('#programa_agregar').select2({
                    placeholder: 'Seleccione un programa...',
                    width: '100%'
                });
            }
            // Para otros roles (como Administrativo), no mostrar campos adicionales
        }

        function cargarDocentes(idPrograma) {
            if (!idPrograma) {
                // Si no se selecciona un programa, limpiar el campo de docentes y ocultarlo
                const docenteSelect = document.getElementById('docente_agregar');
                docenteSelect.innerHTML = '<option value="">Seleccione un docente</option>';
                document.getElementById('campoDocente').style.display = 'none';
                return;
            }

            // Realizar la solicitud para obtener los docentes asociados al programa
            fetch(`../Controlador/Obtener_Docentes.php?id_programa=${idPrograma}`)
                .then(res => res.json())
                .then(data => {
                    const docenteSelect = document.getElementById('docente_agregar');
                    docenteSelect.innerHTML = '<option value="">Seleccione un docente</option>';

                    // Iterar sobre los datos recibidos y agregarlos al select
                    data.forEach(doc => {
                        const option = document.createElement('option');
                        option.value = doc.ID_Usuario;
                        option.textContent = doc.nombre;
                        docenteSelect.appendChild(option);
                    });

                    // Mostrar el campo de docentes si hay datos
                    if (data.length > 0) {
                        document.getElementById('campoDocente').style.display = 'block';
                    } else {
                        alert('No hay docentes disponibles para este programa.');
                        document.getElementById('campoDocente').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error al cargar los docentes:', error);
                    alert('Hubo un error al cargar los docentes. Intente nuevamente.');
                });
        }

        // Manejador de clics fuera de los modales
        window.onclick = function(event) {
            const modalAgregar = document.getElementById('modalAgregar');
            const modalModificar = document.getElementById('modalModificar');
            if (event.target === modalAgregar) {
                cerrarModalAgregar();
            }
            if (event.target === modalModificar) {
                cerrarModal();
            }
        };

        // Filtrar usuarios en el select del modal de agregar
        document.getElementById('buscarUsuario').addEventListener('input', function() {
            const filtro = this.value.toLowerCase();
            const opciones = document.querySelectorAll('#usuario_agregar option');
            opciones.forEach(opcion => {
                const nombre = opcion.getAttribute('data-nombre');
                if (nombre && nombre.includes(filtro)) {
                    opcion.style.display = '';
                } else {
                    opcion.style.display = 'none';
                }
            });
        });

        $(document).ready(function() {
            $('#usuario_agregar').select2({
                placeholder: 'Buscar usuario...',
                width: '100%',
                language: {
                    noResults: function() {
                        return "No se encontraron resultados";
                    }
                }
            });
        });
    </script>

    <!-- Modal para modificar registros -->
    <div id="modalModificar" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="cerrarModal()">&times;</span>
            <h2>Modificar Registro</h2>
            <form id="formModificar" onsubmit="guardarCambios(event)">
                <input type="hidden" id="registro_id" name="registro_id">
                <div class="form-group">
                    <label for="fecha">Fecha:</label>
                    <input type="date" id="fecha" name="fecha" required>
                </div>
                <div class="form-group">
                    <label for="hora_inicio">Hora Inicio:</label>
                    <input type="time" id="hora_inicio" name="hora_inicio" required>
                </div>
                <div class="form-group">
                    <label for="hora_fin">Hora Fin:</label>
                    <input type="time" id="hora_fin" name="hora_fin" required>
                </div>
                <div class="form-group">
                    <label for="estado">Estado:</label>
                    <select id="estado" name="estado" required>
                        <option value="Confirmada">Confirmada</option>
                        <option value="Cancelada">Cancelada</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-confirmar">Guardar cambios</button>
                    <button type="button" onclick="cerrarModal()" class="btn-cancelar">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para agregar registros -->
    <div id="modalAgregar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModalAgregar()">&times;</span>
            <h2>Agregar Registro</h2>
            <form id="formAgregar" onsubmit="guardarNuevoRegistro(event)">
                <div class="form-group">
                    <label for="usuario_agregar">Usuario:</label>
                    <select id="usuario_agregar" name="usuario" required onchange="cargarDatosUsuario(this.value)" style="width: 100%;">
                        <option value="">Seleccione un usuario</option>
                        <?php foreach ($usuariosData as $u): ?>
                            <option value="<?php echo $u['ID_Usuario']; ?>"
                                data-rol="<?php echo $u['ID_Rol']; ?>">
                                <?php echo htmlspecialchars($u['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Campo para mostrar asignaturas de docentes -->
                <div class="form-group" id="campoAsignaturas" style="display:none;">
                    <label><strong>Asignaturas que imparte el docente:</strong></label>
                    <div id="listaAsignaturas" class="asignaturas-list"></div>
                </div>

                <!-- Campos para estudiantes -->
                <div class="form-group" id="campoPrograma" style="display:none;">
                    <label for="programa_agregar"><strong>Seleccione el programa:</strong></label>
                    <select id="programa_agregar" name="programa" onchange="cargarDocentes(this.value)" style="width: 100%;">
                        <option value="">Seleccione un programa</option>
                        <?php foreach ($programasData as $p): ?>
                            <option value="<?php echo $p['ID_Programa']; ?>">
                                <?php echo htmlspecialchars($p['nombrePrograma']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="campoDocente" style="display:none;">
                    <label for="docente_agregar"><strong>Seleccione el docente:</strong></label>
                    <select id="docente_agregar" name="docente" style="width: 100%;">
                        <option value="">Seleccione un docente</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="recurso_agregar">Recurso:</label>
                    <select id="recurso_agregar" name="recurso" required style="width: 100%;">
                        <option value="">Seleccione un recurso</option>
                        <?php foreach ($recursosData as $r): ?>
                            <option value="<?php echo $r['ID_Recurso']; ?>">
                                <?php echo htmlspecialchars($r['nombreRecurso']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="fecha_agregar">Fecha:</label>
                    <input type="date" id="fecha_agregar" name="fecha" required>
                </div>

                <div class="form-group">
                    <label for="hora_inicio_agregar">Hora Inicio:</label>
                    <input type="time" id="hora_inicio_agregar" name="hora_inicio" required>
                </div>

                <div class="form-group">
                    <label for="hora_fin_agregar">Hora Fin:</label>
                    <input type="time" id="hora_fin_agregar" name="hora_fin" required>
                </div>

                <!-- Eliminado el campo de estado y agregado como valor oculto -->
                <input type="hidden" name="estado" value="Confirmada">

                <div class="form-actions">
                    <button type="submit" class="btn-confirmar">Guardar</button>
                    <button type="button" onclick="cerrarModalAgregar()" class="btn-cancelar">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Inicializar Select2 para todos los selects
            $('#usuario_agregar, #programa_agregar, #docente_agregar, #recurso_agregar').select2({
                placeholder: 'Buscar...',
                width: '100%',
                language: {
                    noResults: function() {
                        return "No se encontraron resultados";
                    }
                }
            });
        });
    </script>
</body>

</html>
