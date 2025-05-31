// js/reservas_usuarios.js
// Funciones JS para la vista de Reservas de Usuarios

// Mostrar/ocultar campo salón según recurso seleccionado
const recursoSelect = document.getElementById('recurso_unico');
if (recursoSelect) {
    recursoSelect.addEventListener('change', function() {
        const selected = recursoSelect.options[recursoSelect.selectedIndex];
        const nombre = selected.getAttribute('data-nombre');
        document.getElementById('grupo_salon_unico').style.display = (nombre && nombre.toLowerCase().includes('videobeam')) ? 'block' : 'none';
    });
}

// Cargar docentes según programa seleccionado
const programaSelect = document.getElementById('programa_unico');
const docenteSelect = document.getElementById('docente_unico');
if (programaSelect && docenteSelect) {
    programaSelect.addEventListener('change', function() {
        const programaId = this.value;
        docenteSelect.innerHTML = '<option value="">Cargando...</option>';
        fetch('../Controlador/ControladorObtener.php?tipo=docentes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id_programa=' + encodeURIComponent(programaId)
            })
            .then(response => response.json())
            .then(data => {
                docenteSelect.innerHTML = '<option value="">Seleccione un Docente</option>';
                data.data.forEach(docente => {
                    docenteSelect.innerHTML += `<option value="${docente.ID_Usuario}">${docente.nombre}</option>`;
                });
            });
    });
}

// Cargar asignaturas según docente y programa
const asignaturaSelect = document.getElementById('asignatura_unico');
if (docenteSelect && asignaturaSelect && programaSelect) {
    docenteSelect.addEventListener('change', function() {
        const docenteId = this.value;
        const programaId = programaSelect.value;
        asignaturaSelect.innerHTML = '<option value="">Cargando...</option>';
        fetch('../Controlador/ControladorObtener.php?tipo=asignaturas', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id_docente=' + encodeURIComponent(docenteId) + '&id_programa=' + encodeURIComponent(programaId)
            })
            .then(response => response.json())
            .then(data => {
                asignaturaSelect.innerHTML = '<option value="">Seleccione una Asignatura</option>';
                data.data.forEach(asig => {
                    asignaturaSelect.innerHTML += `<option value="${asig.ID_Asignatura}">${asig.nombreAsignatura}</option>`;
                });
            });
    });
}

/**
 * Valida los datos de la reserva antes de enviarla.
 */
function validarRegistro(fecha, horaInicio, horaFin) {
    const hoy = new Date();
    const fechaSeleccionada = new Date(fecha + 'T00:00');
    if (fechaSeleccionada.setHours(0, 0, 0, 0) < hoy.setHours(0, 0, 0, 0)) {
        throw new Error('No puedes seleccionar una fecha pasada');
    }
    const [hInicio, mInicio] = horaInicio.split(':').map(Number);
    const [hFin, mFin] = horaFin.split(':').map(Number);
    if (hInicio < 6 || hFin > 22 || (hFin === 22 && mFin > 0)) {
        throw new Error('El horario de reserva debe estar entre las 6:00 AM y las 10:00 PM');
    }
    const fechaHoraInicio = new Date(`${fecha}T${horaInicio}`);
    const fechaHoraFin = new Date(`${fecha}T${horaFin}`);
    const ahora = new Date();
    const hoyStr = ahora.toISOString().split('T')[0];
    if (fecha === hoyStr) {
        const margenMinutos = 10;
        const ahoraConMargen = new Date(ahora.getTime() + margenMinutos * 60000);
        if (fechaHoraInicio <= ahoraConMargen) {
            throw new Error('Solo puedes apartar con al menos 10 minutos de anticipación');
        }
    }
    if (fechaHoraFin <= fechaHoraInicio) {
        throw new Error('La hora de finalización debe ser posterior a la hora de inicio');
    }
    const duracionMin = 30;
    const duracionMax = 240;
    const duracionMs = fechaHoraFin - fechaHoraInicio;
    const duracionMinutos = duracionMs / (1000 * 60);
    if (duracionMinutos < duracionMin) {
        throw new Error('La reserva debe durar al menos 30 minutos');
    }
    if (duracionMinutos > duracionMax) {
        throw new Error('La reserva no puede exceder 4 horas');
    }
    return true;
}

/**
 * Verifica la disponibilidad del recurso para la reserva.
 */
async function verificarDisponibilidad(fecha, horaInicio, horaFin, recurso) {
    const formData = new FormData();
    formData.append("fecha", fecha);
    formData.append("hora_inicio", horaInicio);
    formData.append("hora_fin", horaFin);
    formData.append("recurso", recurso);
    try {
        const response = await fetch("../Controlador/ControladorVerificar.php?tipo=disponibilidad", {
            method: "POST",
            body: formData
        });
        const contentType = response.headers.get("content-type");
        const responseBody = contentType && contentType.includes("application/json") ?
            await response.json() :
            await response.text();
        if (typeof responseBody === "object") {
            if (!responseBody.disponible) {
                throw new Error(responseBody.mensaje || responseBody.error || 'El recurso no está disponible en ese horario');
            }
            return true;
        } else {
            throw new Error('Error en la respuesta del servidor. No es un formato JSON válido.');
        }
    } catch (error) {
        throw error;
    }
}

/**
 * Genera un ID de reserva único basado en la fecha y un valor aleatorio.
 */
function generarIdReserva(fecha) {
    if (!fecha) return '';
    const random = Math.random().toString(36).substr(2, 4).toUpperCase();
    return fecha.replace(/-/g, '') + '-' + random;
}

/**
 * Abre el modal para crear una nueva reserva y limpia los campos.
 */
function abrirModalReserva() {
    document.getElementById('modalReservaUnica').style.display = 'block';
    const form = document.getElementById('reservaFormUnica');
    form.recurso.value = '';
    form.horaInicio.value = '';
    form.horaFin.value = '';
    form.programa.value = '';
    form.docente.value = '';
    form.asignatura.value = '';
    form.nombre_alumno.value = '';
    form.salon.value = '';
    form.semestre.value = '';
    form.celular.value = '';
    form.correo.value = '';
    const fechaInput = document.getElementById('fecha_unico');
    const hoy = new Date().toISOString().split('T')[0];
    fechaInput.value = hoy;
    document.getElementById('id_registro').value = generarIdReserva(hoy);
}

/**
 * Cierra el modal de reserva.
 */
function cerrarModalReserva(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Generar ID de reserva al cambiar la fecha
const fechaInput = document.getElementById('fecha_unico');
const idInput = document.getElementById('id_registro');
if (fechaInput && idInput) {
    fechaInput.addEventListener('change', function() {
        idInput.value = generarIdReserva(this.value);
    });
}

/**
 * Envía el formulario de reserva única, validando y verificando disponibilidad.
 */
async function guardarReservaUnica(event) {
    event.preventDefault();
    const form = document.getElementById('reservaFormUnica');
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Reservando...';
    const fecha = form.fecha.value;
    const idInput = form.id_registro;
    if (!idInput.value) {
        idInput.value = generarIdReserva(fecha);
    }
    try {
        validarRegistro(form.fecha.value, form.horaInicio.value, form.horaFin.value);
        await verificarDisponibilidad(form.fecha.value, form.horaInicio.value, form.horaFin.value, form.recurso.value);
        const formData = new FormData(form);
        const response = await fetch('../Controlador/ControladorRegistro.php?accion=agregar', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                text: data.message || 'Reserva realizada correctamente'
            });
            cerrarModalReserva('modalReservaUnica');
            form.reset();
        } else {
            Swal.fire({
                icon: 'error',
                text: data.message || 'Error al realizar la reserva'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            text: error.message
        });
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}
