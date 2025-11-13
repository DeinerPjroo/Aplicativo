// js/registro.js - MEJORADO CON REPORTES FUNCIONALES

/**
 * Valida los datos de un registro antes de enviarlo.
 */
function validarRegistro(fecha, horaInicio, horaFin) {
    const hoy = new Date();
    const fechaSeleccionada = new Date(fecha + 'T00:00');
    if (fechaSeleccionada.setHours(0, 0, 0, 0) < hoy.setHours(0, 0, 0, 0)) {
        showToast('No puedes seleccionar una fecha pasada.', 'error');
        return false;
    }
    const [hInicio, mInicio] = horaInicio.split(':').map(Number);
    const [hFin, mFin] = horaFin.split(':').map(Number);
    if (hInicio < 6 || hFin > 22 || (hFin === 22 && mFin > 0)) {
        showToast('El horario permitido es de 6:00 AM a 10:00 PM.', 'error');
        return false;
    }
    const fechaHoraInicio = new Date(`${fecha}T${horaInicio}`);
    const fechaHoraFin = new Date(`${fecha}T${horaFin}`);
    const ahora = new Date();
    const hoyStr = ahora.toISOString().split('T')[0];
    if (fecha === hoyStr) {
        const diezMinDespues = new Date(ahora.getTime() + 10 * 60000);
        if (fechaHoraInicio < diezMinDespues) {
            showToast('La hora de inicio debe ser al menos 10 minutos después de la actual.', 'error');
            return false;
        }
    }
    if (fechaHoraFin <= fechaHoraInicio) {
        showToast('La hora de fin debe ser posterior a la de inicio.', 'error');
        return false;
    }
    const duracionMin = 30;
    const duracionMax = 240;
    const duracionMs = fechaHoraFin - fechaHoraInicio;
    const duracionMinutos = duracionMs / (1000 * 60);
    if (duracionMinutos < duracionMin) {
        showToast('La duración mínima es de 30 minutos.', 'error');
        return false;
    }
    if (duracionMinutos > duracionMax) {
        showToast('La duración máxima es de 4 horas.', 'error');
        return false;
    }
    return true;
}

// Filtro de búsqueda en la tabla de reservas
function filtrarTablaReservas() {
    const input = document.getElementById("filtroBusqueda");
    const filtro = input.value.toLowerCase();
    const filas = document.querySelectorAll(".tabla-reservas tbody tr");
    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(filtro) ? '' : 'none';
    });
}

document.addEventListener("DOMContentLoaded", () => {
    const input = document.getElementById("filtroBusqueda");
    if (input) {
        input.addEventListener("keyup", filtrarTablaReservas);
    }
    
    // Cerrar menús desplegables al hacer clic fuera de ellos
    document.addEventListener('click', function(event) {
        const menus = document.querySelectorAll('.menu-desplegable');
        const isMenuButton = event.target.closest('.menu-boton');
        
        if (!isMenuButton) {
            menus.forEach(menu => {
                if (menu.style.display === 'block') {
                    menu.style.display = 'none';
                    menu.classList.remove('arriba', 'derecha');
                }
            });
        }
    });
    
    // ===== BOTONES DE REPORTE MEJORADOS =====
    const btnReporteHoy = document.getElementById("generarReporte");
    const btnReporteManana = document.getElementById("generarReporteSiguiente");
    const btnReporteVista = document.getElementById("generarReporteVista");
    
    if (btnReporteHoy) {
        btnReporteHoy.addEventListener("click", function(e) {
            e.preventDefault();
            if (this.disabled) {
                showToast('Los reportes están deshabilitados cuando hay filtros activos', 'info');
                return;
            }
            generarReporte('hoy');
        });
    }
    
    if (btnReporteManana) {
        btnReporteManana.addEventListener("click", function(e) {
            e.preventDefault();
            if (this.disabled) {
                showToast('Los reportes están deshabilitados cuando hay filtros activos', 'info');
                return;
            }
            generarReporte('manana');
        });
    }
    
    if (btnReporteVista) {
        btnReporteVista.addEventListener("click", function(e) {
            e.preventDefault();
            generarReporteVista();
        });
    }

    // Manejador para el formulario de agregar reserva
    const formAgregar = document.getElementById('formAgregarRegistro');
    if (formAgregar) {
        formAgregar.addEventListener('submit', async function(event) {
            event.preventDefault();
            const btn = formAgregar.querySelector('button[type="submit"]');
            if (btn.disabled) return;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Guardando...';
            
            const fecha = formAgregar.fecha.value;
            const horaInicio = formAgregar.horaInicio.value;
            const horaFin = formAgregar.horaFin.value;
            
            if (!validarRegistro(fecha, horaInicio, horaFin)) {
                btn.disabled = false;
                btn.innerHTML = originalText;
                return;
            }
            
            const formData = new FormData(formAgregar);
            if (!formData.get('id_registro')) {
                const hoy = fecha || new Date().toISOString().split('T')[0];
                const random = Math.random().toString(36).substr(2, 4).toUpperCase();
                formData.append('id_registro', hoy.replace(/-/g, '') + '-' + random);
            }
            
            try {
                const response = await fetch('../Controlador/ControladorRegistro.php?accion=agregar', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.status === 'success') {
                    showToast(data.message || 'Reserva agregada correctamente', 'success');
                    cerrarModalAgregar();
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    showToast(data.message || 'Error al agregar la reserva', 'error');
                }
            } catch (error) {
                showToast('Error de conexión al guardar', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }

    // Manejador para el formulario de modificar reserva
    const formModificar = document.getElementById('formModificarRegistro');
    if (formModificar) {
        formModificar.addEventListener('submit', async function(event) {
            event.preventDefault();
            const btn = formModificar.querySelector('button[type="submit"]');
            if (btn.disabled) return;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Guardando...';
            
            const fecha = formModificar.fecha.value;
            const horaInicio = formModificar.horaInicio.value;
            const horaFin = formModificar.horaFin.value;
            
            if (!validarRegistro(fecha, horaInicio, horaFin)) {
                btn.disabled = false;
                btn.innerHTML = originalText;
                return;
            }
            
            const formData = new FormData(formModificar);
            let data;
            try {
                const response = await fetch('../Controlador/ControladorRegistro.php?accion=modificar', {
                    method: 'POST',
                    body: formData
                });
                data = await response.json();
            } catch (error) {
                showToast('Error de conexión al guardar', 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
                return;
            }
            
            if (data && data.status === 'success') {
                showToast(data.message || 'Registro modificado correctamente', 'success');
                cerrarModal();
                setTimeout(() => window.location.reload(), 800);
            } else {
                showToast((data && data.message) || 'Error al modificar la reserva', 'error');
            }
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
});

// ===== FUNCIONES DE REPORTES MEJORADAS =====

/**
 * Genera un reporte para hoy o mañana
 */
function generarReporte(tipo) {
    showToast('Generando reporte...', 'info');
    
    // Abrir el reporte en nueva ventana
    const url = `../Controlador/generar_reporte.php?tipo=${tipo}`;
    const ventana = window.open(url, '_blank');
    
    if (!ventana) {
        showToast('Por favor, permite ventanas emergentes para descargar el reporte', 'error');
    } else {
        setTimeout(() => {
            showToast('Reporte generado. Revisa la nueva ventana', 'success');
        }, 500);
    }
}

/**
 * Genera un reporte de la vista actual con filtros aplicados
 */
function generarReporteVista() {
    showToast('Generando reporte de vista actual...', 'info');
    
    // Obtener los parámetros actuales de la URL
    const params = new URLSearchParams(window.location.search);
    
    let url = '../Controlador/generar_reporte.php?tipo=vista';
    
    // Agregar filtros si existen
    if (params.has('filtro_recurso')) {
        url += '&recurso=' + encodeURIComponent(params.get('filtro_recurso'));
    }
    if (params.has('filtro_fecha')) {
        url += '&fecha=' + encodeURIComponent(params.get('filtro_fecha'));
    }
    if (params.has('hora_desde')) {
        url += '&hora_desde=' + encodeURIComponent(params.get('hora_desde'));
    }
    if (params.has('hora_hasta')) {
        url += '&hora_hasta=' + encodeURIComponent(params.get('hora_hasta'));
    }
    
    const ventana = window.open(url, '_blank');
    
    if (!ventana) {
        showToast('Por favor, permite ventanas emergentes para descargar el reporte', 'error');
    } else {
        setTimeout(() => {
            showToast('Reporte generado. Revisa la nueva ventana', 'success');
        }, 500);
    }
}

// ===== FUNCIONES DE MENÚ Y MODALES =====

function toggleMenu(button) {
    const menu = button.nextElementSibling;
    document.querySelectorAll('.menu-desplegable').forEach(m => {
        if (m !== menu) {
            m.style.display = 'none';
            m.classList.remove('arriba', 'derecha');
        }
    });
    
    if (menu.style.display === 'block') {
        menu.style.display = 'none';
        menu.classList.remove('arriba', 'derecha');
        return;
    }
    
    menu.style.display = 'block';
    
    const buttonRect = button.getBoundingClientRect();
    const menuRect = menu.getBoundingClientRect();
    
    const espacioAbajo = window.innerHeight - buttonRect.bottom;
    const espacioArriba = buttonRect.top;
    
    menu.classList.remove('arriba', 'derecha');
    
    if (espacioAbajo < 120 && espacioArriba > 120) {
        menu.classList.add('arriba');
    }
}

function mostrarModal(registro) {
    document.getElementById('modalModificar').style.display = 'block';
    document.getElementById('registro_id').value = registro.ID_Registro;
    document.getElementById('fecha_modificar').value = registro.fechaReserva;
    document.getElementById('hora_inicio_modificar').value = registro.horaInicio;
    document.getElementById('hora_fin_modificar').value = registro.horaFin;
    document.getElementById('estado').value = registro.estado;
    
    if (registro.correo) {
        document.getElementById('correo_modificar').value = registro.correo;
    }
    if (registro.id_recurso) {
        document.getElementById('recurso_modificar').value = registro.id_recurso;
    }
    if (registro.salon !== undefined) {
        document.getElementById('salon_modificar').value = registro.salon;
    }
    if (registro.id_programa) {
        document.getElementById('programa_modificar').value = registro.id_programa;
    }
    if (registro.semestre) {
        document.getElementById('semestre_modificar').value = registro.semestre;
    }
    if (registro.celular) {
        document.getElementById('celular_modificar').value = registro.celular;
    }
    if (registro.id_usuario) {
        document.getElementById('usuario_modificar').value = registro.id_usuario;
        $('#usuario_modificar').trigger('change');
    }
}

function cerrarModal() {
    document.getElementById('modalModificar').style.display = 'none';
}

function abrirModalAgregar() {
    const modal = document.getElementById('modalAgregar');
    if (modal) modal.style.display = 'block';
}

function cerrarModalAgregar() {
    const modal = document.getElementById('modalAgregar');
    if (modal) modal.style.display = 'none';
}

window.onclick = function(event) {
    const modalAgregar = document.getElementById('modalAgregar');
    const modalModificar = document.getElementById('modalModificar');
    const modalEliminar = document.getElementById('modalEliminar');
    if (event.target === modalAgregar) cerrarModalAgregar();
    if (event.target === modalModificar) cerrarModal();
    if (event.target === modalEliminar) cerrarModalEliminar();
};

$(document).ready(function() {
    $('#usuario_agregar').select2({});
});

// ===== FUNCIONES DE ELIMINACIÓN =====

let idRegistroEliminar = null;

function confirmarEliminar(id) {
    idRegistroEliminar = id;
    const modalEliminar = document.getElementById('modalEliminar');
    modalEliminar.style.display = 'block';
    const btnConfirm = document.getElementById('btnConfirmDelete');
    btnConfirm.onclick = eliminarRegistro;
}

async function eliminarRegistro() {
    if (!idRegistroEliminar) return;
    const btnConfirm = document.getElementById('btnConfirmDelete');
    btnConfirm.disabled = true;
    try {
        const response = await fetch('../Controlador/ControladorRegistro.php?accion=eliminar&id=' + encodeURIComponent(idRegistroEliminar), {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success) {
            showToast('Registro eliminado correctamente', 'success');
            const fila = document.querySelector(`tr[data-registro-id='${idRegistroEliminar}']`);
            if (fila) fila.remove();
            cerrarModalEliminar();
        } else {
            showToast(data.error || 'Error al eliminar el registro', 'error');
        }
    } catch (error) {
        showToast('Error de conexión al eliminar', 'error');
    } finally {
        btnConfirm.disabled = false;
        idRegistroEliminar = null;
    }
}

function cerrarModalEliminar() {
    document.getElementById('modalEliminar').style.display = 'none';
    idRegistroEliminar = null;
}

// ===== FUNCIONES DE NOTIFICACIONES =====

function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        console.log(message);
        alert(message);
        return;
    }
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div>${message}</div>
        <button class="toast-close" onclick="closeToast(this.parentElement)">&times;</button>
    `;
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        closeToast(toast);
    }, 5000);
}

function closeToast(toast) {
    if (!toast) return;
    toast.style.animation = 'fade-out 0.3s forwards';
    setTimeout(() => {
        if (toast && toast.parentNode) {
            toast.remove();
        }
    }, 300);
}