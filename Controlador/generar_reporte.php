<?php
// generar_reporte.php - Genera reportes de reservas en formato texto
ob_start();
header('Content-Type: text/plain; charset=utf-8');

date_default_timezone_set('America/Bogota');
include("../database/conection.php");

if (!$conn) {
    echo "Error: No se pudo conectar a la base de datos";
    exit;
}

$tipo = $_GET['tipo'] ?? 'hoy';
$fechaInicio = '';
$fechaFin = '';
$titulo = '';

// Determinar el rango de fechas según el tipo de reporte
switch ($tipo) {
    case 'hoy':
        $fechaInicio = date('Y-m-d');
        $fechaFin = date('Y-m-d');
        $titulo = 'REPORTE DE RESERVAS DEL DÍA ' . date('d/m/Y');
        break;
    
    case 'manana':
        $fechaInicio = date('Y-m-d', strtotime('+1 day'));
        $fechaFin = date('Y-m-d', strtotime('+1 day'));
        $titulo = 'REPORTE DE RESERVAS DEL DÍA ' . date('d/m/Y', strtotime('+1 day'));
        break;
    
    case 'vista':
        // Obtener parámetros de filtro de la vista actual
        $recursoFiltrado = $_GET['recurso'] ?? '';
        $fechaFiltrada = $_GET['fecha'] ?? '';
        $horaDesde = $_GET['hora_desde'] ?? '';
        $horaHasta = $_GET['hora_hasta'] ?? '';
        
        $titulo = 'REPORTE DE VISTA ACTUAL';
        
        // Si no hay filtros, usar hoy
        if (empty($recursoFiltrado) && empty($fechaFiltrada)) {
            $fechaInicio = date('Y-m-d');
            $fechaFin = date('Y-m-d');
            $titulo .= ' - HOY ' . date('d/m/Y');
        }
        break;
    
    default:
        echo "Error: Tipo de reporte no válido";
        exit;
}

// Construir la consulta SQL
$sql = "SELECT
    r.ID_Registro,
    r.fechaReserva,
    r.horaInicio,
    r.horaFin,
    r.salon,
    rc.nombreRecurso,
    u.nombre AS nombreUsuario,
    u.Codigo_U,
    u.correo AS correoUsuario,
    u.telefono AS telefonoUsuario,
    u.ID_Rol,
    CASE 
        WHEN u.ID_Rol = 2 THEN 'No aplica'
        ELSE COALESCE(doc.nombre, 'Sin docente')
    END AS nombreDocente,
    COALESCE(asig.nombreAsignatura, 'Sin asignatura') AS asignatura,
    CASE 
        WHEN r.ID_DocenteAsignatura IS NOT NULL THEN pr.nombrePrograma
        ELSE prog_user.nombrePrograma
    END AS programa,
    CASE 
        WHEN u.ID_Rol = 3 THEN 'No aplica'
        ELSE COALESCE(r.semestre, 'Sin semestre')
    END AS semestre,
    r.estado
FROM registro r
LEFT JOIN usuario u ON r.ID_Usuario = u.ID_Usuario
LEFT JOIN programa prog_user ON u.ID_Programa = prog_user.ID_Programa
LEFT JOIN recursos rc ON r.ID_Recurso = rc.ID_Recurso
LEFT JOIN docente_asignatura da ON r.ID_DocenteAsignatura = da.ID_DocenteAsignatura
LEFT JOIN usuario doc ON da.ID_Usuario = doc.ID_Usuario
LEFT JOIN asignatura asig ON da.ID_Asignatura = asig.ID_Asignatura
LEFT JOIN programa pr ON asig.ID_Programa = pr.ID_Programa
WHERE 1=1";

// Agregar condiciones según el tipo de reporte
if ($tipo === 'vista') {
    if (!empty($_GET['recurso'])) {
        $recursoEscapado = $conn->real_escape_string($_GET['recurso']);
        $sql .= " AND r.ID_Recurso = '$recursoEscapado'";
    }
    if (!empty($_GET['fecha'])) {
        $fechaEscapada = $conn->real_escape_string($_GET['fecha']);
        $sql .= " AND r.fechaReserva = '$fechaEscapada'";
        $titulo .= ' - FECHA: ' . date('d/m/Y', strtotime($_GET['fecha']));
    }
    if (!empty($_GET['hora_desde'])) {
        $horaDesdeEscapada = $conn->real_escape_string($_GET['hora_desde']);
        $sql .= " AND r.horaInicio >= '$horaDesdeEscapada'";
    }
    if (!empty($_GET['hora_hasta'])) {
        $horaHastaEscapada = $conn->real_escape_string($_GET['hora_hasta']);
        $sql .= " AND r.horaFin <= '$horaHastaEscapada'";
    }
} else {
    $sql .= " AND r.fechaReserva BETWEEN '$fechaInicio' AND '$fechaFin'";
}

$sql .= " AND r.estado = 'Confirmada'";
$sql .= " ORDER BY r.fechaReserva, r.horaInicio";

$result = $conn->query($sql);

// Limpiar buffer antes de generar el reporte
ob_end_clean();

// Generar el reporte en formato texto
echo str_repeat('=', 80) . "\n";
echo str_pad($titulo, 80, ' ', STR_PAD_BOTH) . "\n";
echo "Generado el: " . date('d/m/Y H:i:s') . "\n";
echo str_repeat('=', 80) . "\n\n";

if ($result && $result->num_rows > 0) {
    $contador = 1;
    $fechaAnterior = null;
    
    while ($row = $result->fetch_assoc()) {
        $fechaActual = $row['fechaReserva'];
        
        // Separador de fecha
        if ($fechaActual !== $fechaAnterior) {
            if ($fechaAnterior !== null) {
                echo "\n" . str_repeat('-', 80) . "\n\n";
            }
            
            $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 
                     'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            
            $timestamp = strtotime($fechaActual);
            $dia = $dias[date('w', $timestamp)];
            $diaNum = date('d', $timestamp);
            $mes = $meses[date('n', $timestamp) - 1];
            $anio = date('Y', $timestamp);
            
            echo "📅 $dia $diaNum de $mes de $anio\n";
            echo str_repeat('-', 80) . "\n\n";
            
            $fechaAnterior = $fechaActual;
        }
        
        // Información de la reserva
        echo "RESERVA #$contador - ID: {$row['ID_Registro']}\n";
        echo str_repeat('-', 40) . "\n";
        echo "🏢 Sala/Recurso: {$row['nombreRecurso']}\n";
        echo "⏰ Horario: " . date('h:i A', strtotime($row['horaInicio'])) . 
             " - " . date('h:i A', strtotime($row['horaFin'])) . "\n";
        
        if (!empty($row['salon']) && $row['salon'] !== 'null') {
            echo "🏫 Salón: {$row['salon']}\n";
        }
        
        echo "\n📚 INFORMACIÓN ACADÉMICA:\n";
        echo "   Programa: {$row['programa']}\n";
        
        if ($row['ID_Rol'] == 2 || $row['ID_Rol'] == 3) {
            echo "   Docente/Admin: {$row['nombreUsuario']}\n";
        } else {
            echo "   Docente: {$row['nombreDocente']}\n";
            echo "   Estudiante: {$row['nombreUsuario']}\n";
        }
        
        echo "   Asignatura: {$row['asignatura']}\n";
        echo "   Semestre: {$row['semestre']}\n";
        
        echo "\n👤 INFORMACIÓN DE CONTACTO:\n";
        echo "   Código: {$row['Codigo_U']}\n";
        echo "   Correo: {$row['correoUsuario']}\n";
        
        if (!empty($row['telefonoUsuario'])) {
            echo "   Teléfono: {$row['telefonoUsuario']}\n";
        }
        
        echo "\n" . str_repeat('=', 80) . "\n\n";
        
        $contador++;
    }
    
    echo "\n📊 RESUMEN\n";
    echo str_repeat('=', 80) . "\n";
    echo "Total de reservas: " . ($contador - 1) . "\n";
    echo str_repeat('=', 80) . "\n";
    
} else {
    echo "❌ No se encontraron reservas para los criterios seleccionados.\n\n";
    echo "Verifique que:\n";
    echo "  • Existan reservas confirmadas para la fecha seleccionada\n";
    echo "  • Los filtros aplicados sean correctos\n";
    echo "  • Las reservas no estén canceladas\n";
}

$conn->close();
exit;
?>