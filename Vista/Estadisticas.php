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
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Total de reservas
$totalReservas = $conn->query("SELECT COUNT(*) as total FROM registro WHERE fechaReserva BETWEEN '$fecha_inicio' AND '$fecha_fin'")->fetch_assoc()['total'];

// Reservas por estado
$estados = [];
$resEstados = $conn->query("SELECT estado, COUNT(*) as cantidad FROM registro WHERE fechaReserva BETWEEN '$fecha_inicio' AND '$fecha_fin' GROUP BY estado");
while ($row = $resEstados->fetch_assoc()) {
    $estados[$row['estado']] = $row['cantidad'];
}

// Reservas por recurso
$recursos = [];
$resRecursos = $conn->query("SELECT rec.nombreRecurso, COUNT(*) as cantidad FROM registro r JOIN recursos rec ON r.ID_Recurso = rec.ID_Recurso WHERE r.fechaReserva BETWEEN '$fecha_inicio' AND '$fecha_fin' GROUP BY rec.nombreRecurso");
while ($row = $resRecursos->fetch_assoc()) {
    $recursos[$row['nombreRecurso']] = $row['cantidad'];
}

// Reservas por día
$dias = [];
$resDias = $conn->query("SELECT fechaReserva, COUNT(*) as cantidad FROM registro WHERE fechaReserva BETWEEN '$fecha_inicio' AND '$fecha_fin' GROUP BY fechaReserva ORDER BY fechaReserva ASC");
while ($row = $resDias->fetch_assoc()) {
    $dias[$row['fechaReserva']] = $row['cantidad'];
}

// Reservas por rol
$roles = [];
$resRoles = $conn->query("SELECT rol.nombreRol, COUNT(*) as cantidad FROM registro r JOIN usuario u ON r.ID_Usuario = u.ID_Usuario JOIN rol ON u.ID_Rol = rol.ID_Rol WHERE r.fechaReserva BETWEEN '$fecha_inicio' AND '$fecha_fin' GROUP BY rol.nombreRol");
while ($row = $resRoles->fetch_assoc()) {
    $roles[$row['nombreRol']] = $row['cantidad'];
}

// Reservas por programa
$programas = [];
$resProgramas = $conn->query("
    SELECT COALESCE(p.nombrePrograma, 'Sin programa') AS nombrePrograma, COUNT(*) as cantidad
    FROM registro r
    LEFT JOIN usuario u ON r.ID_Usuario = u.ID_Usuario
    LEFT JOIN programa p ON u.ID_Programa = p.ID_Programa
    WHERE r.fechaReserva BETWEEN '$fecha_inicio' AND '$fecha_fin'
    GROUP BY p.nombrePrograma
    ORDER BY cantidad DESC
");
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
    <style>
        .estadisticas-container {
            margin-left: 110px;
            margin-top: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0 10px #0001;
            padding: 30px 30px 10px 30px;
            max-width: 1200px;
        }
        .estadisticas-cards {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        .estadistica-card {
            flex: 1 1 200px;
            background: #f5f5f5;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            min-width: 180px;
            box-shadow: 0 2px 8px #0001;
        }
        .estadistica-card h3 {
            margin: 0 0 10px 0;
            color: #258797;
        }
        .estadistica-card .valor {
            font-size: 2.2em;
            font-weight: bold;
            color: #d07c2e;
        }
        .graficas-row {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        .grafica-box {
            flex: 1 1 350px;
            background: #fafafa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            min-width: 320px;
        }
        .filtros-form {
            margin-bottom: 25px;
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .filtros-form label {
            font-weight: bold;
        }
        .tabla-resumen {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .tabla-resumen th, .tabla-resumen td {
            padding: 8px 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        .tabla-resumen th {
            background: #d3892e;
            color: #fff;
        }
        @media (max-width: 900px) {
            .estadisticas-cards, .graficas-row { flex-direction: column; }
        }
    </style>
</head>
<body class="Registro">
    <?php include("../Vista/Sidebar.php"); ?>
    <section class="Encabezado">
        <h1><center>Estadísticas de Reservas</center></h1>
    </section>
    <div class="estadisticas-container">
        <form class="filtros-form" method="get">
            <label>Desde: <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>"></label>
            <label>Hasta: <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>"></label>
            <button type="submit" class="btn-confirmar">Filtrar</button>
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
    <div style="text-align:right; margin: 30px 110px 0 0;">
        <button onclick="descargarEstadisticasPDF()" class="btn-confirmar" style="font-size:16px;">
            <span class="material-symbols-outlined" style="vertical-align:middle;">download</span>
            Descargar estadísticas en PDF
        </button>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        // Datos PHP a JS
        const estados = <?php echo json_encode($estados); ?>;
        const recursos = <?php echo json_encode($recursos); ?>;
        const dias = <?php echo json_encode($dias); ?>;
        const roles = <?php echo json_encode($roles); ?>;
        const programas = <?php echo json_encode($programas); ?>;

        // Gráfica de Estados
        new Chart(document.getElementById('graficaEstados'), {
            type: 'pie',
            data: {
                labels: Object.keys(estados),
                datasets: [{
                    data: Object.values(estados),
                    backgroundColor: ['#28a745','#ffc107','#e44655','#2d9eb2','#d07c2e','#888']
                }]
            },
            options: { responsive: true }
        });

        // Gráfica de Recursos
        new Chart(document.getElementById('graficaRecursos'), {
            type: 'bar',
            data: {
                labels: Object.keys(recursos),
                datasets: [{
                    label: 'Reservas',
                    data: Object.values(recursos),
                    backgroundColor: '#258797'
                }]
            },
            options: { responsive: true, indexAxis: 'y' }
        });

        // Gráfica de Días
        new Chart(document.getElementById('graficaDias'), {
            type: 'line',
            data: {
                labels: Object.keys(dias),
                datasets: [{
                    label: 'Reservas por Día',
                    data: Object.values(dias),
                    fill: false,
                    borderColor: '#d07c2e',
                    tension: 0.2
                }]
            },
            options: { responsive: true }
        });

        // Gráfica de Roles
        new Chart(document.getElementById('graficaRoles'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(roles),
                datasets: [{
                    data: Object.values(roles),
                    backgroundColor: ['#2d9eb2','#d07c2e','#28a745','#e44655','#888']
                }]
            },
            options: { responsive: true }
        });

        // Gráfica de Programas
        new Chart(document.getElementById('graficaProgramas'), {
            type: 'bar',
            data: {
                labels: Object.keys(programas),
                datasets: [{
                    label: 'Reservas',
                    data: Object.values(programas),
                    backgroundColor: '#f1a036'
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });

        // Función para descargar el área de estadísticas como PDF
        function descargarEstadisticasPDF() {
            // Selecciona solo el área de estadísticas (puedes ajustar el selector si lo deseas)
            const element = document.querySelector('.estadisticas-container');
            const opt = {
                margin:       0.3,
                filename:     'Estadisticas_Reservas_<?php echo date("Ymd_His"); ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
