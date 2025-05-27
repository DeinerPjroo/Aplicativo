// js/estadisticas.js
// Funciones para renderizar gráficas y descargar PDF en la vista de Estadísticas

/**
 * Renderiza todas las gráficas de estadísticas usando Chart.js
 * Los datos se deben definir en variables globales: estados, recursos, dias, roles, programas
 */
function renderizarGraficas() {
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
}

/**
 * Descarga el área de estadísticas como PDF usando html2pdf.js
 */
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

document.addEventListener('DOMContentLoaded', renderizarGraficas);
