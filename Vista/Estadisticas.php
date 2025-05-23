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
    <style>
        .estadisticas-container {

            margin-left: 200px;
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
            gap: 10px;
            align-items: center;
            
            margin-left: 300px;
        }
        .filtros-form label {
            font-weight: bold;
        }

        .filtros-form input[type="date"] {
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
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
        // Función mejorada para descargar estadísticas en PDF
async function descargarEstadisticasPDF() {
    try {
        // Esperar a que las gráficas se rendericen
        await new Promise(resolve => setTimeout(resolve, 2000));

        // Obtener los canvas una sola vez
        const canvases = document.querySelectorAll('canvas');
        await Promise.all(Array.from(canvases).map(canvas => 
            new Promise(resolve => {
                if (canvas.toBlob) {
                    canvas.toBlob(resolve);
                } else {
                    resolve();
                }
            })
        ));

        // Crear contenedor principal para el PDF
        const contenedor = document.createElement('div');
        contenedor.style.padding = '20px';
        contenedor.style.fontFamily = 'Arial, sans-serif';

        // Agregar encabezado
        const header = document.createElement('div');
        header.innerHTML = `
            <h1 style="color: #258797; text-align: center; margin-bottom: 30px;">Informe de Estadísticas de Reservas</h1>
            <p style="text-align: right; color: #666; font-size: 12px;">
                Fecha de generación: ${new Date().toLocaleDateString('es-ES', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                })}
            </p>
        `;
        contenedor.appendChild(header);

        // Sección de resumen
        const resumen = document.createElement('div');
        resumen.style.marginBottom = '30px';
        resumen.style.pageBreakAfter = 'always';
        resumen.innerHTML = `
            <h2 style="color: #d07c2e; border-bottom: 2px solid #d07c2e; padding-bottom: 5px;">Resumen General</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                ${Object.entries(estados).map(([estado, cantidad]) => `
                    <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; text-align: center;">
                        <h3 style="margin: 0; color: #258797;">${estado}</h3>
                        <p style="font-size: 24px; font-weight: bold; margin: 10px 0; color: #d07c2e;">${cantidad}</p>
                    </div>
                `).join('')}
            </div>
        `;
        contenedor.appendChild(resumen);

        // Obtener las imágenes directamente del canvas ya definido
        const imagenes = Array.from(canvases).map(canvas => {
            const imagen = new Image();
            imagen.src = canvas.toDataURL('image/png');
            imagen.style.maxWidth = '100%';
            imagen.style.height = 'auto';
            return imagen;
        });

        // Crear secciones para las gráficas
        const titulos = ['Reservas por Estado', 'Reservas por Recurso', 'Reservas por Día', 'Reservas por Rol', 'Reservas por Programa'];
        imagenes.forEach((imagen, index) => {
            const seccion = document.createElement('div');
            seccion.style.marginBottom = '30px';
            seccion.style.pageBreakInside = 'avoid';
            seccion.style.textAlign = 'center';
            
            const titulo = document.createElement('h3');
            titulo.textContent = titulos[index];
            titulo.style.color = '#258797';
            titulo.style.marginBottom = '15px';
            
            seccion.appendChild(titulo);
            seccion.appendChild(imagen);
            
            if (index % 2 === 1) {
                seccion.style.pageBreakAfter = 'always';
            }
            
            contenedor.appendChild(seccion);
        });

        // Configuración del PDF
        const opt = {
            margin: [0.5, 0.5, 0.5, 0.5],
            filename: `Estadisticas_Reservas_${new Date().toISOString().slice(0,10)}.pdf`,
            image: { type: 'jpeg', quality: 1 },
            html2canvas: { 
                scale: 2,
                useCORS: true,
                letterRendering: true,
                logging: true,
                windowWidth: 1920,
                windowHeight: 1080
            },
            jsPDF: { 
                unit: 'in', 
                format: 'a4', 
                orientation: 'portrait'
            }
        };

        // Generar el PDF
        await html2pdf().set(opt).from(contenedor).save();

    } catch (error) {
        console.error('Error al generar PDF:', error);
        alert('Hubo un error al generar el PDF. Por favor intente nuevamente.');
    }
}
    </script>
</body>
</html>
