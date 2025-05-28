// js/registro.js
// Funciones JS para la vista de Registro de reservas

/**
 * Valida los datos de un registro antes de enviarlo.
 * @param {string} fecha - Fecha de la reserva.
 * @param {string} horaInicio - Hora de inicio.
 * @param {string} horaFin - Hora de fin.
 * @returns {boolean} - true si es válido, false si no.
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
// Permite filtrar filas según el texto ingresado
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
    // Botones de reporte (debes implementar la lógica de generación de reportes)
    document.getElementById("generarReporte")?.addEventListener("click", () => {/* lógica */});
    document.getElementById("generarReporteSiguiente")?.addEventListener("click", () => {/* lógica */});
    document.getElementById("generarReporteVista")?.addEventListener("click", () => {/* lógica */});

    // Manejador para el formulario de agregar reserva (modal admin)
    const formAgregar = document.getElementById('formAgregarRegistro');
    if (formAgregar) {
        formAgregar.addEventListener('submit', async function(event) {
            event.preventDefault();
            // Obtener valores
            const fecha = formAgregar.fecha.value;
            const horaInicio = formAgregar.horaInicio.value;
            const horaFin = formAgregar.horaFin.value;
            if (!validarRegistro(fecha, horaInicio, horaFin)) return;
            // Enviar por AJAX
            const formData = new FormData(formAgregar);
            // Generar un id_registro si no existe
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
            }
        });
    }

    // Manejador para el formulario de modificar reserva (modal admin)
    const formModificar = document.getElementById('formModificarRegistro');
    if (formModificar) {
        formModificar.addEventListener('submit', async function(event) {
            event.preventDefault();
            const fecha = formModificar.fecha.value;
            const horaInicio = formModificar.horaInicio.value;
            const horaFin = formModificar.horaFin.value;
            if (!validarRegistro(fecha, horaInicio, horaFin)) return;
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
                return;
            }
            if (data && data.status === 'success') {
                showToast(data.message || 'Registro modificado correctamente', 'success');
                cerrarModal();
                const id = formModificar.registro_id.value;
                const fila = document.querySelector(`tr[data-registro-id='${id}']`);
                if (fila) {
                    try {
                        fila.querySelector('.td-fecha').textContent = fecha;
                        fila.querySelector('.td-hora-inicio').textContent = horaInicio;
                        fila.querySelector('.td-hora-fin').textContent = horaFin;
                        fila.querySelector('.td-salon').textContent = formModificar.salon.value;
                        fila.querySelector('.td-semestre').textContent = formModificar.semestre.value;
                    } catch (e) {
                        // Si alguna celda no existe, no mostrar error al usuario
                    }
                }
            } else {
                showToast((data && data.message) || 'Error al modificar la reserva', 'error');
            }
        });
    }
});

/**
 * Muestra u oculta el menú de acciones de una fila.
 * @param {HTMLElement} button - Botón que activa el menú.
 */
function toggleMenu(button) {
    const menu = button.nextElementSibling;
    menu.style.display = menu.style.display === "block" ? "none" : "block";
}

// Funciones para modales de modificar/agregar/eliminar
function mostrarModal(registro) {
    // Abrir el modal
    document.getElementById('modalModificar').style.display = 'block';
    // Rellenar campos básicos
    document.getElementById('registro_id').value = registro.ID_Registro;
    document.getElementById('fecha_modificar').value = registro.fechaReserva;
    document.getElementById('hora_inicio_modificar').value = registro.horaInicio;
    document.getElementById('hora_fin_modificar').value = registro.horaFin;
    document.getElementById('estado').value = registro.estado;
    // Rellenar campos adicionales si están presentes en el objeto registro
    if (registro.correoUsuario) {
        document.getElementById('correo_modificar').value = registro.correoUsuario;
    }
    if (registro.recurso) {
        document.getElementById('recurso_modificar').value = registro.recurso;
        // Disparar el evento para mostrar/ocultar salón
        $('#recurso_modificar').trigger('change');
    }
    if (registro.salon !== undefined) {
        document.getElementById('salon_modificar').value = registro.salon;
    }
    if (registro.programa) {
        document.getElementById('programa_modificar').value = registro.programa;
        $('#programa_modificar').trigger('change');
    }
    if (registro.docente) {
        setTimeout(function() {
            document.getElementById('docente_modificar').value = registro.docente;
            $('#docente_modificar').trigger('change');
        }, 200);
    }
    if (registro.asignatura) {
        setTimeout(function() {
            document.getElementById('asignatura_modificar').value = registro.asignatura;
        }, 400);
    }
    if (registro.semestre) {
        document.getElementById('semestre_modificar').value = registro.semestre;
    }
    if (registro.celular) {
        document.getElementById('celular_modificar').value = registro.celular;
    }
    if (registro.nombre_estudiante) {
        document.getElementById('grupo_nombre_estudiante_modificar').style.display = 'block';
        document.getElementById('nombre_estudiante_modificar').value = registro.nombre_estudiante;
    } else {
        document.getElementById('grupo_nombre_estudiante_modificar').style.display = 'none';
        document.getElementById('nombre_estudiante_modificar').value = '';
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

// Cargar datos de usuario y campos adicionales según el rol
function cargarDatosUsuario(idUsuario) {
    const selectUsuario = document.getElementById('usuario_agregar');
    const selectedOption = selectUsuario.options[selectUsuario.selectedIndex];
    const rol = selectedOption.getAttribute('data-rol');
    document.getElementById('campoAsignaturas').style.display = 'none';
    document.getElementById('campoPrograma').style.display = 'none';
    document.getElementById('campoDocente').style.display = 'none';
    if (rol === '2') {
        // Docente
        document.getElementById('campoAsignaturas').style.display = 'block';
    } else if (rol === '1') {
        // Estudiante
        document.getElementById('campoPrograma').style.display = 'block';
        document.getElementById('campoDocente').style.display = 'block';
    }
}

function cargarDocentes(idPrograma) {
    if (!idPrograma) return;
    fetch(`../Controlador/ControladorObtener.php?tipo=docentes&id_programa=${idPrograma}`)
        .then(res => res.json())
        .then(data => {
            // Lógica para llenar el select de docentes
        });
}

// Manejador de clics fuera de los modales
window.onclick = function(event) {
    const modalAgregar = document.getElementById('modalAgregar');
    const modalModificar = document.getElementById('modalModificar');
    const modalEliminar = document.getElementById('modalEliminar');
    if (event.target === modalAgregar) cerrarModalAgregar();
    if (event.target === modalModificar) cerrarModal();
    if (event.target === modalEliminar) cerrarModalEliminar();
};

// Filtrar usuarios en el select del modal de agregar
const buscarUsuario = document.getElementById('buscarUsuario');
if (buscarUsuario) {
    buscarUsuario.addEventListener('input', function() {
        const filtro = this.value.toLowerCase();
        const opciones = document.querySelectorAll('#usuario_agregar option');
        opciones.forEach(opcion => {
            opcion.style.display = opcion.textContent.toLowerCase().includes(filtro) ? '' : 'none';
        });
    });
}

$(document).ready(function() {
    $('#usuario_agregar').select2({});
});

// Variables y funciones para eliminar registros
let registroAEliminar = null;
function mostrarModalConfirmacion(idRegistro) {
    registroAEliminar = idRegistro;
    document.getElementById('modalConfirmacion').style.display = 'block';
}
function cerrarModalConfirmacion() {
    document.getElementById('modalConfirmacion').style.display = 'none';
    registroAEliminar = null;
}
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
            // Eliminar la fila de la tabla visualmente
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

/**
 * Muestra una notificación tipo toast.
 * @param {string} message - Mensaje a mostrar.
 * @param {string} [type='info'] - Tipo ('info', 'success', 'error').
 */
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
    <div>${message}</div>
    <button class="toast-close" onclick="closeToast(this.parentElement)">&times;</button>`;
    toastContainer.appendChild(toast);
    setTimeout(() => {
        closeToast(toast);
    }, 5000);
}
function closeToast(toast) {
    toast.style.animation = 'fade-out 0.3s forwards';
    setTimeout(() => {
        toast.remove();
    }, 300);
}
