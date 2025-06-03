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
        console.log('🚀 Iniciando generación de PDF...');
        
        // Mostrar indicador de carga
        const loadingMsg = document.createElement('div');
        loadingMsg.innerHTML = 'Generando PDF... Por favor espere.';
        loadingMsg.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:20px;border:2px solid #007bff;border-radius:8px;z-index:9999;font-weight:bold;box-shadow:0 4px 12px rgba(0,0,0,0.3);';
        document.body.appendChild(loadingMsg);

        // Verificar que html2pdf esté disponible
        if (typeof html2pdf === 'undefined') {
            throw new Error('html2pdf no está cargado. Verifique que la librería esté incluida.');
        }
        console.log('✅ html2pdf está disponible');

        // Esperar a que las gráficas se rendericen completamente
        console.log('⏳ Esperando que las gráficas se rendericen...');
        await esperarGraficasRenderizadas();
        console.log('✅ Gráficas renderizadas');        
        // Obtener el elemento principal de estadísticas
        const original = document.querySelector('.estadisticas-container');
        if (!original) {
            throw new Error('No se encontró el contenedor de estadísticas');
        }
        console.log('✅ Contenedor de estadísticas encontrado');

        // Enfoque simplificado: usar directamente el contenedor original
        // pero con mejor preparación para PDF
        
        // Crear un clon del contenedor original
        const contenedorPDF = original.cloneNode(true);
        console.log('✅ Contenedor clonado');        // Preparar el contenedor para PDF - VISIBLE TEMPORALMENTE CON DIMENSIONES OPTIMIZADAS
        contenedorPDF.style.cssText = `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 210mm !important;
            min-height: 297mm !important;
            background: white !important;
            color: #000000 !important;
            font-family: Arial, sans-serif !important;
            padding: 15mm !important;
            box-sizing: border-box !important;
            z-index: 99999 !important;
            overflow: visible !important;
            transform: none !important;
            opacity: 1 !important;
            visibility: visible !important;
            page-break-inside: avoid !important;
            line-height: 1.4 !important;
        `;

        // Limpiar elementos no deseados
        contenedorPDF.querySelectorAll('.btn-pdf, button, .nav-tooltip, script').forEach(el => {
            el.remove();
        });
        console.log('✅ Elementos no deseados removidos');

        // Procesar los canvas - convertir a imágenes
        const canvasOriginales = original.querySelectorAll('canvas');
        const canvasClonados = contenedorPDF.querySelectorAll('canvas');
        
        console.log(`🎨 Procesando ${canvasOriginales.length} gráficas...`);
        
        canvasOriginales.forEach((canvasOriginal, index) => {
            if (canvasClonados[index]) {
                try {
                    // Crear imagen del canvas original
                    const img = document.createElement('img');
                    const dataURL = canvasOriginal.toDataURL('image/png', 1.0);
                    console.log(`📊 Canvas ${index + 1}: ${dataURL.length} caracteres`);
                    
                    img.src = dataURL;
                    img.style.cssText = `
                        max-width: 100% !important;
                        height: auto !important;
                        display: block !important;
                        margin: 0 auto !important;
                        background: white !important;
                    `;
                    
                    // Reemplazar el canvas clonado con la imagen
                    canvasClonados[index].parentNode.replaceChild(img, canvasClonados[index]);
                    console.log(`✅ Canvas ${index + 1} convertido a imagen`);
                } catch (error) {
                    console.error(`❌ Error procesando canvas ${index + 1}:`, error);
                }
            }
        });        // Aplicar estilos específicos para PDF - SIMPLIFICADOS
        contenedorPDF.querySelectorAll('*').forEach(elemento => {
            // Forzar visibilidad básica
            elemento.style.color = '#000000';
            elemento.style.fontSize = '14px';
            elemento.style.fontFamily = 'Arial, sans-serif';
            elemento.style.visibility = 'visible';
            elemento.style.opacity = '1';
            elemento.style.display = elemento.style.display === 'none' ? 'block' : elemento.style.display;
        });

        // Aplicar estilos a las tarjetas - SIMPLIFICADOS
        contenedorPDF.querySelectorAll('.estadistica-card').forEach(tarjeta => {
            tarjeta.style.background = '#f8f9fa';
            tarjeta.style.border = '2px solid #000000';
            tarjeta.style.padding = '15px';
            tarjeta.style.margin = '10px';
            tarjeta.style.color = '#000000';
            tarjeta.style.display = 'inline-block';
            tarjeta.style.textAlign = 'center';
            tarjeta.style.fontSize = '14px';
            tarjeta.style.fontWeight = 'bold';
        });

        // Aplicar estilos a títulos - SIMPLIFICADOS
        contenedorPDF.querySelectorAll('h1, h2, h3, h4, h5, h6').forEach(titulo => {
            titulo.style.color = '#000000';
            titulo.style.fontWeight = 'bold';
            titulo.style.fontSize = '18px';
            titulo.style.textAlign = 'center';
            titulo.style.background = '#f0f0f0';
            titulo.style.padding = '10px';
            titulo.style.border = '1px solid #000000';
            titulo.style.margin = '15px 0';
        });

        // Aplicar estilos a la tabla - SIMPLIFICADOS
        contenedorPDF.querySelectorAll('table').forEach(tabla => {
            tabla.style.border = '2px solid #000000';
            tabla.style.borderCollapse = 'collapse';
            tabla.style.width = '100%';
            tabla.style.margin = '20px 0';
            tabla.style.background = 'white';
        });
        
        contenedorPDF.querySelectorAll('th, td').forEach(celda => {
            celda.style.border = '1px solid #000000';
            celda.style.padding = '10px';
            celda.style.color = '#000000';
            celda.style.background = 'white';
            celda.style.fontSize = '14px';
        });

        // Agregar un título principal visible
        const tituloPrincipal = document.createElement('h1');
        tituloPrincipal.innerHTML = 'ESTADÍSTICAS DE RESERVAS';
        tituloPrincipal.style.color = '#000000';
        tituloPrincipal.style.textAlign = 'center';
        tituloPrincipal.style.fontSize = '24px';
        tituloPrincipal.style.fontWeight = 'bold';
        tituloPrincipal.style.margin = '20px 0';
        tituloPrincipal.style.padding = '15px';
        tituloPrincipal.style.background = '#f8f9fa';
        tituloPrincipal.style.border = '2px solid #000000';
        contenedorPDF.insertBefore(tituloPrincipal, contenedorPDF.firstChild);

        // Agregar al DOM temporalmente
        document.body.appendChild(contenedorPDF);
        console.log('✅ Contenedor agregado al DOM');        // Esperar más tiempo para que se apliquen todos los estilos
        await new Promise(resolve => setTimeout(resolve, 2000));
        console.log('✅ Estilos aplicados, iniciando captura...');

        // Verificar que el contenedor tiene contenido visible
        console.log('🔍 Verificando contenido del contenedor:', {
            altura: contenedorPDF.scrollHeight,
            ancho: contenedorPDF.scrollWidth,
            textoVisible: contenedorPDF.textContent.length,
            elementosHijos: contenedorPDF.children.length
        });        // Configurar opciones de html2pdf optimizadas para capturar texto
        const opciones = {
            margin: 0.3,
            filename: `Estadisticas_Reservas_${new Date().toISOString().slice(0,10)}.pdf`,
            image: { 
                type: 'jpeg', 
                quality: 0.95 
            },
            html2canvas: {
                scale: 1,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                logging: false,
                letterRendering: true,
                foreignObjectRendering: false,
                removeContainer: true,
                imageTimeout: 15000,
                windowWidth: 800,
                windowHeight: contenedorPDF.scrollHeight + 200,
                width: 800,
                height: contenedorPDF.scrollHeight + 200,
                scrollX: 0,
                scrollY: 0,
                x: 0,
                y: 0,
                onclone: function(clonedDoc) {
                    // Forzar estilos en el documento clonado
                    const style = clonedDoc.createElement('style');
                    style.textContent = `
                        * { 
                            color: #000000 !important; 
                            background-color: transparent !important;
                            font-family: Arial, sans-serif !important;
                            font-size: 14px !important;
                            visibility: visible !important;
                            opacity: 1 !important;
                        }
                        .estadistica-card {
                            background: #f8f9fa !important;
                            border: 2px solid #000000 !important;
                            padding: 15px !important;
                            margin: 10px !important;
                            display: inline-block !important;
                            text-align: center !important;
                        }
                        h1, h2, h3, h4, h5, h6 {
                            color: #000000 !important;
                            font-weight: bold !important;
                            background: #f0f0f0 !important;
                            padding: 10px !important;
                            border: 1px solid #000000 !important;
                        }
                        table, th, td {
                            border: 1px solid #000000 !important;
                            padding: 8px !important;
                            color: #000000 !important;
                            background: white !important;
                        }
                    `;
                    clonedDoc.head.appendChild(style);
                }
            },
            jsPDF: { 
                unit: 'in', 
                format: 'a4', 
                orientation: 'portrait' 
            }
        };        console.log('📄 Generando PDF con opciones:', opciones);

        // Generar el PDF (el contenedor será visible durante la captura)
        await html2pdf().set(opciones).from(contenedorPDF).save();
        console.log('✅ PDF generado exitosamente');

        // Limpiar
        document.body.removeChild(contenedorPDF);
        document.body.removeChild(loadingMsg);

        // Mostrar mensaje de éxito
        const successMsg = document.createElement('div');
        successMsg.innerHTML = '¡PDF generado exitosamente!';
        successMsg.style.cssText = 'position:fixed;top:20px;right:20px;background:#28a745;color:white;padding:15px;border-radius:8px;z-index:9999;font-weight:bold;box-shadow:0 4px 12px rgba(0,0,0,0.3);';
        document.body.appendChild(successMsg);
        setTimeout(() => {
            if (document.body.contains(successMsg)) {
                document.body.removeChild(successMsg);
            }
        }, 3000);

    } catch (error) {
        console.error('❌ Error al generar PDF:', error);
        
        // Remover indicador de carga si existe
        const loadingMsg = document.querySelector('[style*="Generando PDF"]');
        if (loadingMsg && document.body.contains(loadingMsg)) {
            document.body.removeChild(loadingMsg);
        }
        
        alert('Hubo un error al generar el PDF. Por favor intente nuevamente.\n\nDetalle del error: ' + error.message + '\n\nRevise la consola del navegador para más detalles.');
    }
}

/**
 * Función auxiliar para esperar a que las gráficas se rendericen completamente
 */
async function esperarGraficasRenderizadas() {
    return new Promise((resolve) => {
        console.log('🔍 Verificando gráficas...');
        
        let intentos = 0;
        const maxIntentos = 30; // Aumentado para más tiempo
        
        const verificar = () => {
            const canvases = document.querySelectorAll('canvas');
            console.log(`🎨 Encontrados ${canvases.length} canvas`);
            
            if (canvases.length === 0) {
                console.log('⚠️ No se encontraron canvas, continuando...');
                resolve();
                return;
            }
            
            let todasRenderizadas = true;
            let graficasConContenido = 0;
            
            canvases.forEach((canvas, index) => {
                try {
                    const ctx = canvas.getContext('2d');
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const data = imageData.data;
                    
                    // Verificar si el canvas tiene contenido
                    let pixelesNoTransparentes = 0;
                    for (let i = 3; i < data.length; i += 4) {
                        if (data[i] > 0) { // Canal alpha > 0
                            pixelesNoTransparentes++;
                            if (pixelesNoTransparentes > 100) break; // Suficiente contenido
                        }
                    }
                    
                    if (pixelesNoTransparentes > 100) {
                        graficasConContenido++;
                        console.log(`✅ Canvas ${index + 1}: ${pixelesNoTransparentes} píxeles con contenido`);
                    } else {
                        console.log(`⏳ Canvas ${index + 1}: Solo ${pixelesNoTransparentes} píxeles con contenido`);
                        todasRenderizadas = false;
                    }
                } catch (error) {
                    console.log(`❌ Error verificando canvas ${index + 1}:`, error);
                    todasRenderizadas = false;
                }
            });
            
            console.log(`📊 Gráficas renderizadas: ${graficasConContenido}/${canvases.length}`);
            
            if (todasRenderizadas || intentos >= maxIntentos) {
                if (intentos >= maxIntentos) {
                    console.log('⚠️ Tiempo máximo de espera alcanzado, continuando...');
                } else {
                    console.log('✅ Todas las gráficas están renderizadas');
                }
                resolve();
            } else {
                intentos++;
                console.log(`🔄 Intento ${intentos}/${maxIntentos}, esperando...`);
                setTimeout(verificar, 300);
            }
        };
        
        verificar();
    });
}

document.addEventListener('DOMContentLoaded', renderizarGraficas);
