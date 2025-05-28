// js/estadisticas.js
// Funciones para renderizar gráficas y descargar PDF en la vista de Estadísticas

/**
 * Renderiza todas las gráficas de estadísticas usando Chart.js
 * Los datos se deben definir en variables globales: estados, recursos, dias, roles, programas
 */
function renderizarGraficas() {    // Gráfica de Estados - Colores ajustados dinámicamente
    const coloresEstados = {
        'Confirmada': '#28a745',
        'Cancelada': '#e44655',
        'Pendiente': '#ffc107',
        'Rechazada': '#6c757d',
        'En Proceso': '#17a2b8',
        'Completada': '#20c997'
    };
    
    const coloresAsignados = Object.keys(estados).map(estado => 
        coloresEstados[estado] || '#888888'
    );

    new Chart(document.getElementById('graficaEstados'), {
        type: 'pie',
        data: {
            labels: Object.keys(estados),
            datasets: [{
                data: Object.values(estados),
                backgroundColor: coloresAsignados,
                borderWidth: 3,
                borderColor: '#ffffff',                hoverOffset: 15
            }]
        },
        options: { 
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.2,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#ffffff',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const porcentaje = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${context.parsed} (${porcentaje}%)`;
                        }
                    }
                }
            },
            elements: {
                arc: {
                    borderWidth: 2
                }
            }
        }
    });    // Gráfica de Recursos - Mejoras visuales
    new Chart(document.getElementById('graficaRecursos'), {
        type: 'bar',
        data: {
            labels: Object.keys(recursos),
            datasets: [{
                label: 'Reservas',
                data: Object.values(recursos),
                backgroundColor: '#258797',
                borderColor: '#1a6b75',
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },        options: { 
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.5,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(37, 135, 151, 0.9)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#ffffff',
                    borderWidth: 1,
                    cornerRadius: 8
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11,
                            weight: '500'
                        }
                    }
                }
            }
        }
    });    // Gráfica de Días - Mejoras visuales
    new Chart(document.getElementById('graficaDias'), {
        type: 'line',
        data: {
            labels: Object.keys(dias),
            datasets: [{
                label: 'Reservas por Día',
                data: Object.values(dias),
                fill: true,
                backgroundColor: 'rgba(208, 124, 46, 0.1)',
                borderColor: '#d07c2e',
                borderWidth: 3,
                tension: 0.3,
                pointBackgroundColor: '#d07c2e',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: '#b8681a',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 3
            }]
        },        options: { 
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(208, 124, 46, 0.9)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#ffffff',
                    borderWidth: 1,
                    cornerRadius: 8
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        font: {
                            size: 10
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });    // Gráfica de Roles - Mejoras visuales
    new Chart(document.getElementById('graficaRoles'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(roles),
            datasets: [{
                data: Object.values(roles),
                backgroundColor: ['#2d9eb2','#d07c2e','#28a745','#e44655','#6c757d','#17a2b8'],
                borderWidth: 3,
                borderColor: '#ffffff',
                hoverOffset: 15,
                cutout: '60%'
            }]
        },        options: { 
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.2,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#ffffff',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const porcentaje = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${context.parsed} (${porcentaje}%)`;
                        }
                    }
                }
            },
            elements: {
                arc: {
                    borderWidth: 2
                }
            }
        }
    });    // Gráfica de Programas - Mejoras visuales
    new Chart(document.getElementById('graficaProgramas'), {
        type: 'bar',
        data: {
            labels: Object.keys(programas),
            datasets: [{
                label: 'Reservas',
                data: Object.values(programas),
                backgroundColor: '#f1a036',
                borderColor: '#d4891e',
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.5,
            indexAxis: 'y',
            plugins: {
                legend: { 
                    display: false 
                },
                tooltip: {
                    backgroundColor: 'rgba(241, 160, 54, 0.9)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#ffffff',
                    borderWidth: 1,
                    cornerRadius: 8
                }
            },
            scales: {
                x: { 
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11,
                            weight: '500'
                        }
                    }
                }
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
