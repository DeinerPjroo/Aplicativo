// js/perfil.js
// Funciones JS para la vista de perfil de usuario

/**
 * Abre el modal para cambiar la contraseña.
 */
function abrirModal() {
    document.getElementById('modalCambiarContraseña').style.display = 'block';
}

/**
 * Cierra el modal para cambiar la contraseña.
 */
function cerrarModal() {
    document.getElementById('modalCambiarContraseña').style.display = 'none';
}

// Cierra el modal si se hace clic fuera de él
window.onclick = function(event) {
    const modal = document.getElementById('modalCambiarContraseña');
    if (event.target === modal) {
        cerrarModal();
    }
};

/**
 * Muestra una alerta flotante en pantalla.
 * @param {string} mensaje - Mensaje a mostrar.
 * @param {string} [tipo='success'] - Tipo de alerta ('success' o 'error').
 */
function mostrarAlerta(mensaje, tipo = 'success') {
    const alerta = document.createElement('div');
    alerta.className = 'alerta-modal ' + tipo;
    alerta.innerHTML = `<div class="alerta-contenido">${mensaje}</div>`;
    document.body.appendChild(alerta);
    setTimeout(() => {
        alerta.classList.add('visible');
    }, 10);
    setTimeout(() => {
        alerta.classList.remove('visible');
        setTimeout(() => alerta.remove(), 300);
    }, 2500);
}

document.addEventListener('DOMContentLoaded', function() {
    // Interceptar el submit del formulario de perfil
    const formPerfil = document.getElementById('formActualizarPerfil');
    if (formPerfil) {
        formPerfil.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(formPerfil);
            fetch(formPerfil.action, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    mostrarAlerta(data.message, 'success');
                } else if (data.errores) {
                    mostrarAlerta(data.errores.join('<br>'), 'error');
                } else {
                    mostrarAlerta(data.message || 'Error al actualizar', 'error');
                }
            })
            .catch(() => mostrarAlerta('Error de conexión', 'error'));
        });
    }
    // Interceptar el submit del formulario de contraseña
    const formPass = document.getElementById('formActualizarContrasena');
    if (formPass) {
        formPass.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(formPass);
            fetch(formPass.action, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(function(data) {
                if (data.status === 'success') {
                    mostrarAlerta(data.message, 'success');
                    cerrarModal();
                    formPass.reset();
                } else if (data.errores) {
                    mostrarAlerta(data.errores.join('<br>'), 'error');
                } else {
                    mostrarAlerta(data.message || 'Error al actualizar', 'error');
                }
            })
            .catch(() => mostrarAlerta('Error de conexión', 'error'));
        });
    }
});
