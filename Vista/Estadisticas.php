<?php
// Incluye el archivo de conexión a la base de datos.
// Este archivo establece la conexión con la base de datos MySQL utilizando la clase mysqli.
include("../database/conection.php");

// Incluye el archivo que contiene la lógica para el control de roles.
// Este archivo define funciones como `checkRole` para verificar si el usuario tiene permisos para acceder a esta página.
include("../Controlador/control_De_Rol.php");

// Llama a la función `checkRole` para verificar si el usuario tiene el rol de 'Administrador'.
// Si el usuario no tiene el rol requerido, la función redirige o bloquea el acceso a esta página.
checkRole('Administrador');

// Filtros de fecha
$fecha_inicio = isset($_GET['fecha_inicio']) ? 
    filter_var($_GET['fecha_inicio'], FILTER_SANITIZE_STRING) : 
    date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? 
    filter_var($_GET['fecha_fin'], FILTER_SANITIZE_STRING) : 
    date('Y-m-d');

// Validar formato de fecha
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha_inicio) || 
    !preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha_fin)) {
    die("Formato de fecha inválido");
}

// Total de reservas
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM registro WHERE fechaReserva BETWEEN ? AND ?");
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$result = $stmt->get_result();
$totalReservas = $result->fetch_assoc()['total'];

// Reservas por estado
$estados = [];
$stmt = $conn->prepare("SELECT estado, COUNT(*) as cantidad FROM registro 
                       WHERE fechaReserva BETWEEN ? AND ? GROUP BY estado");
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$resEstados = $stmt->get_result();
while ($row = $resEstados->fetch_assoc()) {
    $estados[$row['estado']] = $row['cantidad'];
}

// Reservas por recurso
$recursos = [];
$stmt = $conn->prepare("SELECT rec.nombreRecurso, COUNT(*) as cantidad FROM registro r JOIN recursos rec ON r.ID_Recurso = rec.ID_Recurso 
                       WHERE r.fechaReserva BETWEEN ? AND ? GROUP BY rec.nombreRecurso");
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$resRecursos = $stmt->get_result();
while ($row = $resRecursos->fetch_assoc()) {
    $recursos[$row['nombreRecurso']] = $row['cantidad'];
}

// Reservas por día
$dias = [];
$stmt = $conn->prepare("SELECT fechaReserva, COUNT(*) as cantidad FROM registro WHERE fechaReserva BETWEEN ? AND ? GROUP BY fechaReserva ORDER BY fechaReserva ASC");
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$resDias = $stmt->get_result();
while ($row = $resDias->fetch_assoc()) {
    $dias[$row['fechaReserva']] = $row['cantidad'];
}

// Reservas por rol
$roles = [];
$stmt = $conn->prepare("SELECT rol.nombreRol, COUNT(*) as cantidad FROM registro r JOIN usuario u ON r.ID_Usuario = u.ID_Usuario JOIN rol ON u.ID_Rol = rol.ID_Rol 
                       WHERE r.fechaReserva BETWEEN ? AND ? GROUP BY rol.nombreRol");
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$resRoles = $stmt->get_result();
while ($row = $resRoles->fetch_assoc()) {
    $roles[$row['nombreRol']] = $row['cantidad'];
}

// Reservas por programa
$programas = [];
$stmt = $conn->prepare("
    SELECT COALESCE(p.nombrePrograma, 'Sin programa') AS nombrePrograma, COUNT(*) as cantidad
    FROM registro r
    LEFT JOIN usuario u ON r.ID_Usuario = u.ID_Usuario
    LEFT JOIN programa p ON u.ID_Programa = p.ID_Programa
    WHERE r.fechaReserva BETWEEN ? AND ?
    GROUP BY p.nombrePrograma
    ORDER BY cantidad DESC
");
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$resProgramas = $stmt->get_result();
while ($row = $resProgramas->fetch_assoc()) {
    $programas[$row['nombrePrograma']] = $row['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estadísticas de Reservas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/Style.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
        
</head>
<body class="Registro">
    <?php include("../Vista/Sidebar.php"); ?>
    <section class="Topbard">
        <h1><center>Estadísticas</center></h1>
    </section>
    <div class="estadisticas-container">
        <form class="filtros-form" method="get">
            <label>Desde: <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>"></label>
            <label>Hasta: <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>"></label>
            <button type="submit" class="btn-pdf">Filtrar</button>
        </form>
        <div class="estadisticas-cards">
            <div class="estadistica-card">
                <h3>Total de Reservas</h3>
                <div class="valor"><?php echo $totalReservas; ?></div>
            </div>
            <?php foreach ($estados as $estado => $cantidad): ?>
            <div class="estadistica-card">
                <h3><?php echo htmlspecialchars($estado); ?></h3>
                <div class="valor"><?php echo $cantidad; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="graficas-row">
            <div class="grafica-box">
                <h3>Reservas por Estado</h3>
                <canvas id="graficaEstados"></canvas>
            </div>
            <div class="grafica-box">
                <h3>Reservas por Recurso</h3>
                <canvas id="graficaRecursos"></canvas>
            </div>
        </div>
        <div class="graficas-row">
            <div class="grafica-box">
                <h3>Reservas por Día</h3>
                <canvas id="graficaDias"></canvas>
            </div>
            <div class="grafica-box">
                <h3>Reservas por Rol</h3>
                <canvas id="graficaRoles"></canvas>
            </div>
        </div>
        <div class="graficas-row">
            <div class="grafica-box" style="width:100%;">
                <h3>Reservas por Programa</h3>
                <canvas id="graficaProgramas"></canvas>
            </div>
        </div>
        <h2>Resumen de Reservas por Recurso</h2>
        <table class="tabla-resumen">
            <thead>
                <tr>
                    <th>Recurso</th>
                    <th>Cantidad de Reservas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recursos as $nombre => $cant): ?>
                <tr>
                    <td><?php echo htmlspecialchars($nombre); ?></td>
                    <td><?php echo $cant; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Botón para descargar las estadísticas en PDF -->
    <div >
        <button onclick="descargarEstadisticasPDF()" class="btn-pdf">
             <img src="../Imagen/Iconos/download.svg" alt="" />
            <span class="nav-tooltip">Descargar estadísticas en PDF</span>
            
        </button>



    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="../js/estadisticas.js"></script>
    <script>
        // Datos PHP a JS
        const estados = <?php echo json_encode($estados); ?>;
        const recursos = <?php echo json_encode($recursos); ?>;
        const dias = <?php echo json_encode($dias); ?>;
        const roles = <?php echo json_encode($roles); ?>;
        const programas = <?php echo json_encode($programas); ?>;
    </script>
</body>
</html>
