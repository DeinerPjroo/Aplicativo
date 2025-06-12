// Script extraído de Administrar_Usuarios.php
// Incluye todas las funciones JS para la gestión de usuarios

/**
 * Muestra una notificación tipo toast en pantalla.
 * @param {string} message - Mensaje a mostrar.
 * @param {string} [type='info'] - Tipo de mensaje ('info', 'success', 'error').
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

/**
 * Cierra una notificación toast con animación.
 * @param {HTMLElement} toast - Elemento toast a cerrar.
 */
function closeToast(toast) {
    toast.style.animation = 'fade-out 0.3s forwards';
    setTimeout(() => {
        toast.remove();
    }, 300);
}

/**
 * Filtra la tabla de usuarios según el texto ingresado en el campo de búsqueda.
 */
function filtrarTabla() {
    const input = document.getElementById("busqueda");
    const filtro = input.value.toLowerCase().trim();
    const tabla = document.getElementById("tablaUsuario");
    const filas = tabla.getElementsByTagName("tr");
    const mensajeSinResultados = document.getElementById("mensajeSinResultados");
    let hayCoincidencias = false;

    for (let i = 1; i < filas.length; i++) {        const fila = filas[i];
        const codigo = fila.cells[0]?.textContent || '';
        const nombre = fila.cells[1]?.textContent || '';
        const telefono = fila.cells[2]?.textContent || '';
        const correo = fila.cells[5]?.textContent || '';
        const coincide = codigo.toLowerCase().includes(filtro) ||
            nombre.toLowerCase().includes(filtro) ||
            telefono.toLowerCase().includes(filtro) ||
            correo.toLowerCase().includes(filtro);
        if (coincide) {
            fila.style.display = "";
            hayCoincidencias = true;
        } else {
            fila.style.display = "none";
        }
    }
    mensajeSinResultados.style.display = hayCoincidencias ? "none" : "block";
}

/**
 * Verifica si el código de usuario ya existe en la base de datos mediante AJAX.
 * @param {string} codigo - Código de usuario a verificar.
 */
function verificarCodigoExistente(codigo) {
    if (codigo.trim() === '') return;
    const codigoInput = document.getElementById('form-codigo_u');
    const esEdicion = document.getElementById('modalTitle').textContent === 'Modificar Usuario';
    const codigoOriginal = codigoInput.getAttribute('data-original');
    if (esEdicion && codigo === codigoOriginal) {
        document.getElementById('error-message').style.display = 'none';
        actualizarEstadoBoton();
        return;
    }
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../Controlador/ControladorVerificar.php?tipo=codigo', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            if (response.existe) {
                codigoInput.classList.add('input-error');
            } else {
                codigoInput.classList.remove('input-error');
            }
            actualizarEstadoBoton();
        }
    };
    xhr.send('codigo_u=' + encodeURIComponent(codigo));
}

/**
 * Limpia el mensaje de error del correo electrónico.
 */
function limpiarErrorCorreo() {
    document.getElementById('error-message-correo').style.display = 'none';
    document.getElementById('form-correo').dataset.error = 'false';
    actualizarEstadoBoton();
}

/**
 * Limpia el mensaje de error del código de usuario.
 */
function limpiarErrorCodigo() {
    document.getElementById('error-message').style.display = 'none';
    const codigoInput = document.getElementById('form-codigo_u');
    codigoInput.dataset.error = 'false';
    codigoInput.classList.remove('input-error');
    actualizarEstadoBoton();
}

/**
 * Limpia todos los mensajes de error del formulario.
 */
function limpiarErrores() {
    document.getElementById('error-message-correo').style.display = 'none';
    const correoInput = document.getElementById('form-correo');
    correoInput.dataset.error = 'false';
    correoInput.classList.remove('input-error');
    actualizarEstadoBoton();
}

/**
 * Verifica si el correo electrónico ya existe en la base de datos mediante AJAX.
 * @param {string} correo - Correo electrónico a verificar.
 */
function verificarCorreoExistente(correo) {
    if (correo.trim() === '') return;
    const correoInput = document.getElementById('form-correo');
    const esEdicion = document.getElementById('modalTitle').textContent === 'Modificar Usuario';
    const correoOriginal = correoInput.getAttribute('data-original');
    if (esEdicion && correo === correoOriginal) {
        document.getElementById('error-message-correo').style.display = 'none';
        actualizarEstadoBoton();
        return;
    }
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../Controlador/ControladorVerificar.php?tipo=correo', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            if (response.existe) {
                correoInput.classList.add('input-error');
            } else {
                correoInput.classList.remove('input-error');
            }
            actualizarEstadoBoton();
        }
    };
    xhr.send('correo=' + encodeURIComponent(correo));
}

/**
 * Habilita o deshabilita el botón de envío según el estado de los campos de error.
 */
function actualizarEstadoBoton() {
    const codigoInput = document.getElementById('form-codigo_u');
    const correoInput = document.getElementById('form-correo');
    const submitBtn = document.getElementById('submitBtn');
    const codigoError = codigoInput.dataset.error === 'true';
    const correoError = correoInput.dataset.error === 'true';
    submitBtn.disabled = codigoError || correoError;
    if (submitBtn.disabled) {
        submitBtn.style.opacity = '0.6';
        submitBtn.style.cursor = 'not-allowed';
    } else {
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
    }
}

/**
 * Abre el modal para agregar o modificar un usuario.
 * @param {string} action - Acción a realizar ('agregar' o 'modificar').
 */
function openModal(action) {
    const modal = document.getElementById('formModal');
    const title = document.getElementById('modalTitle');
    const form = document.getElementById('usuarioForm');
    const passwordField = document.getElementById('form-contraseña');
    const passwordLabel = passwordField.previousElementSibling;
    if (action === 'agregar') {
        title.textContent = 'Agregar Usuario';
        form.setAttribute('data-action', '../Controlador/ControladorUsuario.php?accion=agregar');
        document.getElementById('form-id').value = '';
        document.getElementById('form-codigo_u').value = '';
        document.getElementById('form-nombre').value = '';
        document.getElementById('form-telefono').value = '';
        document.getElementById('form-correo').value = '';
        document.getElementById('form-contraseña').required = true;
        document.getElementById('form-contraseña').value = '';
        document.getElementById('form-rol').value = '';
        document.getElementById('form-semestre').value = '';
        document.getElementById('form-programa').value = '';
        passwordField.style.display = 'block';
        passwordLabel.style.display = 'block';
        limpiarErrores();
    }    modal.style.display = 'block';
    setTimeout(() => {
        document.getElementById('form-codigo_u').focus();
    }, 200);
    setTimeout(configurarCamposPorRol, 100);
}

/**
 * Abre el formulario de modificación de usuario con los datos precargados.
 * @param {number} id - ID del usuario.
 * @param {string} codigo_u - Código del usuario.
 * @param {string} nombre - Nombre del usuario.
 * @param {string} telefono - Teléfono del usuario.
 * @param {string} correo - Correo del usuario.
 * @param {string} programa - Programa del usuario.
 * @param {string} semestre - Semestre del usuario.
 * @param {number} id_rol - ID del rol del usuario.
 */
function openModificarForm(id, codigo_u, nombre, telefono, correo, programa, semestre, id_rol) {
    const modal = document.getElementById('formModal');
    const title = document.getElementById('modalTitle');
    const form = document.getElementById('usuarioForm');
    const passwordField = document.getElementById('form-contraseña');
    const passwordLabel = passwordField.previousElementSibling;
    title.textContent = 'Modificar Usuario';
    form.setAttribute('data-action', '../Controlador/ControladorUsuario.php?accion=modificar');
    document.getElementById('form-id').value = id;
    document.getElementById('form-codigo_u').value = codigo_u;
    document.getElementById('form-nombre').value = nombre;
    document.getElementById('form-telefono').value = telefono;
    document.getElementById('form-correo').value = correo;
    // Habilitar el select de programa antes de setear el valor
    const programaField = document.getElementById('form-programa');
    programaField.disabled = false;
    programaField.value = programa;
    document.getElementById('form-semestre').value = semestre;
    document.getElementById('form-rol').value = id_rol;
    passwordField.required = false;
    passwordField.style.display = 'none';
    passwordLabel.style.display = 'none';
    limpiarErrores();
    document.getElementById('form-codigo_u').setAttribute('data-original', codigo_u);
    document.getElementById('form-correo').setAttribute('data-original', correo);
    modal.style.display = 'block';
    setTimeout(configurarCamposPorRol, 100);
}

/**
 * Variable global para almacenar el ID del usuario a eliminar.
 * @type {number|null}
 */
let usuarioAEliminar = null;

/**
 * Muestra el modal de confirmación para eliminar un usuario.
 * @param {number} id - ID del usuario a eliminar.
 */
function eliminarUsuario(id) {
    usuarioAEliminar = id;
    document.getElementById('modalConfirmDelete').style.display = 'block';
}
document.addEventListener('DOMContentLoaded', function() {
    const btnConfirm = document.getElementById('btnConfirmDelete');
    const btnCancel = document.getElementById('btnCancelDelete');
    const modalConfirm = document.getElementById('modalConfirmDelete');
    btnConfirm.onclick = function() {
        modalConfirm.style.display = 'none';
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../Controlador/ControladorUsuario.php?accion=eliminar', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (this.status === 200) {
                // Aquí puedes recargar la tabla o mostrar un mensaje
                cargarUsuarios();
                showToast('Usuario eliminado correctamente', 'success');
            }
        };
        xhr.send('id_usuario=' + encodeURIComponent(usuarioAEliminar));
    };
    btnCancel.onclick = function() {
        modalConfirm.style.display = 'none';
    };
});

/**
 * Envía el formulario de usuario por AJAX para agregar o modificar un usuario.
 * Realiza validaciones antes de enviar.
 * @param {Event} event - Evento submit del formulario.
 * @returns {boolean} - false para evitar el envío tradicional.
 */
function submitForm(event) {
    event.preventDefault();
    const rol = document.getElementById('form-rol').value;
    const esEstudiante = rol === '1';
    const codigo = document.getElementById('form-codigo_u').value.trim();
    const nombre = document.getElementById('form-nombre').value.trim();
    const telefono = document.getElementById('form-telefono').value.trim();
    const correo = document.getElementById('form-correo').value.trim();
    const semestre = document.getElementById('form-semestre').value.trim();
    const programa = document.getElementById('form-programa').value.trim();    if (!codigo || !nombre || !correo || !rol) {
        showToast('Por favor, complete todos los campos obligatorios.', 'error');
        return false;
    }
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(correo)) {
        showToast('Ingrese un correo electrónico válido.', 'error');
        return false;
    }
    if (esEstudiante && (!semestre || !programa)) {
        showToast('Por favor, seleccione el semestre y el programa.', 'error');
        return false;
    }
    // Habilitar el select de programa antes de enviar para que se incluya en el FormData
    const programaField = document.getElementById('form-programa');
    const estabaDeshabilitado = programaField.disabled;
    programaField.disabled = false;
    const form = document.getElementById('usuarioForm');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnLoader = document.getElementById('submitBtnLoader');
    submitBtn.disabled = true;
    submitBtnText.style.display = 'none';
    submitBtnLoader.style.display = 'inline-block';
    formData.append('es_estudiante', esEstudiante ? '1' : '0');
    if (!esEstudiante) {
        // Solo limpiar semestre, pero NO borrar el programa seleccionado
        const semestreField = document.getElementById('form-semestre');
        semestreField.disabled = false;
        semestreField.value = '';
        formData.set('semestre', '');
        setTimeout(() => {
            semestreField.disabled = true;
        }, 10);
    }
    const action = form.getAttribute('data-action');
    const xhr = new XMLHttpRequest();
    xhr.open('POST', action, true);
    xhr.onload = function() {
        if (!esEstudiante) {
            document.getElementById('form-semestre').disabled = true;
            document.getElementById('form-programa').disabled = true;
        }
        submitBtn.disabled = false;
        submitBtnText.style.display = 'inline';
        submitBtnLoader.style.display = 'none';
        // Restaurar el estado de habilitación del select de programa
        if (estabaDeshabilitado) {
            programaField.disabled = true;
        }
        if (this.status === 200) {
            let response;
            try {
                response = JSON.parse(this.responseText);
                if (response.status === 'success') {
                    showToast('Usuario guardado correctamente', 'success');
                    closeModal();
                    cargarUsuarios();
                } else {
                    showToast(response.message || 'Error al guardar usuario', 'error');
                }
            } catch (e) {
                showToast('Respuesta inesperada del servidor', 'error');
            }
        } else {
            showToast('Error en la comunicación con el servidor: ' + this.status, 'error');
        }
    };
    xhr.onerror = function() {
        showToast('Error de conexión al servidor', 'error');
        if (!esEstudiante) {
            document.getElementById('form-semestre').disabled = true;
            document.getElementById('form-programa').disabled = true;
        }
        submitBtn.disabled = false;
        submitBtnText.style.display = 'inline';
        submitBtnLoader.style.display = 'none';
    };
    xhr.send(formData);
    return false;
}

/**
 * Cierra el modal de usuario y limpia el formulario.
 */
function closeModal() {
    document.getElementById('formModal').style.display = 'none';
    document.getElementById('usuarioForm').reset();
    limpiarErrores();
}

window.onclick = function(event) {
    // Cierra los modales si se hace clic fuera de ellos
    const modalForm = document.getElementById('formModal');
    const modalConfirm = document.getElementById('modalConfirmDelete');
    if (event.target === modalForm) {
        closeModal();
    }
    if (event.target === modalConfirm) {
        modalConfirm.style.display = "none";
    }
};

/**
 * Configura los campos de programa y semestre según el rol seleccionado.
 */
function configurarCamposPorRol() {
    const rol = document.getElementById('form-rol').value;
    const semestreField = document.getElementById('form-semestre');
    const programaField = document.getElementById('form-programa');
    const labelPrograma = document.getElementById('labelPrograma');
    const ayudaPrograma = document.getElementById('ayudaPrograma');
    
    // EL PROGRAMA SIEMPRE ESTÁ HABILITADO
    programaField.disabled = false;
    
    // Limpiar ayuda anterior
    ayudaPrograma.style.display = 'none';
    ayudaPrograma.textContent = '';
    
    switch(rol) {
        case '1': // Estudiante
            semestreField.disabled = false;
            semestreField.required = true;
            programaField.required = true;
            
            labelPrograma.textContent = 'Programa de Estudios:';
            ayudaPrograma.textContent = 'Programa académico en el que está inscrito el estudiante.';
            ayudaPrograma.style.display = 'block';
            break;
            
        case '2': // Docente
            semestreField.disabled = true;
            semestreField.required = false;
            semestreField.value = '';
            programaField.required = false;
            
            labelPrograma.textContent = 'Programa Principal:';
            ayudaPrograma.textContent = 'Programa principal del docente. Los programas adicionales se asignan mediante las asignaturas.';
            ayudaPrograma.style.display = 'block';
            break;
            
        case '3': // Administrativo
            semestreField.disabled = true;
            semestreField.required = false;
            semestreField.value = '';
            programaField.required = false;
            
            labelPrograma.textContent = 'Dependencia:';
            ayudaPrograma.textContent = 'Dependencia o área administrativa a la que pertenece.';
            ayudaPrograma.style.display = 'block';
            break;
            
        case '4': // Administrador
        default:
            semestreField.disabled = true;
            semestreField.required = false;
            semestreField.value = '';
            programaField.required = false;
            
            labelPrograma.textContent = 'Programa:';
            ayudaPrograma.textContent = 'Programa opcional para organización administrativa.';
            ayudaPrograma.style.display = 'block';
            break;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const rolSelect = document.getElementById('form-rol');
    rolSelect.addEventListener('change', configurarCamposPorRol);
    const addBtn = document.querySelector('.btn-agregar');
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            setTimeout(configurarCamposPorRol, 100);
        });
    }
});

/**
 * Carga la lista de usuarios mediante AJAX y la muestra en la tabla.
 */
function cargarUsuarios() {
    fetch('../Controlador/ControladorUsuario.php?accion=listar')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('tbodyUsuarios');
            tbody.innerHTML = '';
            if (data.status === 'success') {
                data.data.forEach(row => {
                    tbody.innerHTML += `
                    <tr class="usuario-row">
                        <td class="td-codigo">${row.codigo_u}</td>
                        <td class="td-nombre">${row.nombre}</td>
                        <td class="td-telefono">${row.telefono ?? ''}</td>
                        <td class="td-programa">${row.programa ? row.programa : 'No aplica'}</td>
                        <td class="td-semestre">${row.semestre ?? 'N/A'}</td>
                        <td class="td-correo">${row.correo}</td>
                        <td class="td-rol">${row.rol}</td>
                        <td class="td-acciones" style="display: flex;">
                            <button class="btn btn-modificar" onclick="openModificarForm(
                                ${row.ID_Usuario},
                                '${row.codigo_u.replace(/'/g, "\\'")}',
                                '${row.nombre.replace(/'/g, "\\'")}',
                                '${(row.telefono ?? '').replace(/'/g, "\\'")}',
                                '${row.correo.replace(/'/g, "\\'")}',
                                '${row.Id_Programa ?? ''}',
                                '${row.semestre ?? ''}',
                                ${row.id_rol}
                            )">Modificar</button>
                            <button class="btn btn-eliminar" onclick="eliminarUsuario(${row.ID_Usuario})">Eliminar</button>
                        </td>
                    </tr>`;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="8">No se pudieron cargar los usuarios.</td></tr>';
            }
            filtrarTabla();
        });
}
document.addEventListener('DOMContentLoaded', cargarUsuarios);
