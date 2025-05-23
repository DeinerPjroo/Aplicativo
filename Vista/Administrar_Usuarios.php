<?php
include("../database/conection.php");
include("../Controlador/control_De_Rol.php");
//checkRole('Administrador'); // Solo administradores pueden acceder



$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT u.ID_Usuario, u.codigo_u, u.nombre, u.telefono, p.nombrePrograma AS programa, u.Id_Programa, u.semestre, u.correo, r.nombreRol AS rol, u.id_rol
             FROM usuario u
             LEFT JOIN programa p ON u.Id_Programa = p.ID_Programa
             LEFT JOIN rol r ON u.id_rol = r.id_rol";

if (!empty($search)) {
    $query .= " WHERE u.codigo_u LIKE ? OR u.nombre LIKE ? OR u.correo LIKE ?";
    $stmt = $conn->prepare($query);
    $likeSearch = "%$search%";
    $stmt->bind_param("sss", $likeSearch, $likeSearch, $likeSearch);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}
// ...existing code...

// Obtener los programas desde la base de datos
$programasQuery = "SELECT ID_Programa, nombrePrograma FROM programa";
$programasResult = $conn->query($programasQuery);

$programas = [];
while ($row = $programasResult->fetch_assoc()) {
    $programas[] = $row;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Administrar Usuarios</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

    
</head>

<body class="Registro">

    <?php include("../Vista/Sidebar.php"); ?>


    <div class="Topbard" style="padding-bottom: 10px;">
        <input type="text" id="busqueda" placeholder="Buscar por código, nombre o correo..." onkeyup="filtrarTabla()">
    </div>

    <!-- Toast Container for Notifications -->
    <div id="toastContainer" class="toast-container"></div>





    <div class="contenedor-usuarios">
        <h2>Lista de Usuarios</h2>


        <button class="btn-agregar" onclick="openModal('agregar')" title="Agregar nuevo usuario">
            <img src="../Imagen/Iconos/Agregar_Usuario.svg" alt="Agregar usuario" />
            <span class="btn-text">Agregar</span>
        </button>



        <div id="mensajeSinResultados" style="display:none;" class="sin-usuarios">
            <p>No se encontraron usuarios con ese criterio de búsqueda</p>
        </div>

        <?php if ($result->num_rows > 0) : ?>
            <table id="tablaUsuario" class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>telefono</th>
                        <th>Programa</th>
                        <th>Semestre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['codigo_u']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                            <td><?php echo !empty($row['programa']) ? htmlspecialchars($row['programa']) : 'No aplica'; ?></td>
                            <td><?php echo htmlspecialchars($row['semestre'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['correo']); ?></td>
                            <td><?php echo htmlspecialchars($row['rol']); ?></td>
                            <td style="display: flex;"">
                                <button class="btn btn-modificar"
                                    onclick="openModificarForm(
                                        <?php echo $row['ID_Usuario']; ?>,
                                        '<?php echo htmlspecialchars($row['codigo_u'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($row['nombre'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($row['telefono'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($row['correo'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($row['Id_Programa'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($row['semestre'], ENT_QUOTES); ?>',
                                        <?php echo $row['id_rol']; ?> /* Cambiamos la variable a id_rol para pasar el número */
                                    )">
                                    Modificar
                                </button>
                                <button class="btn btn-eliminar" onclick="eliminarUsuario(<?php echo $row['ID_Usuario']; ?>)">Eliminar</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="sin-usuarios">
                <p>No hay usuarios registrados en este momento</p>
            </div>
        <?php endif; ?>
    </div>


    <!-- Modal para Agregar/Modificar Usuario -->
    <div id="formModal" class="modal" aria-modal="true" role="dialog">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Agregar Usuario</h2>
            <form id="usuarioForm" onsubmit="return submitForm(event)">
                <input type="hidden" name="id_usuario" id="form-id">
                <label>Código de Usuario:</label>
                <input type="text" name="codigo_u" id="form-codigo_u" placeholder="Ingrese código de identificación..." required oninput="limpiarErrorCodigo()" onblur="verificarCodigoExistente(this.value)">
                <div id="error-message" class="error-message" style="display:none;"></div>

                <label>Nombre:</label>
                <input type="text" name="nombre" id="form-nombre" placeholder="Ingrese nombre completo..." required>

                <label>Telefono:</label>
                <input type="text" name="nombre" id="form-telefono" placeholder="Ingrese telefono..." required>

                <label>Correo:</label>
                <input type="email" name="correo" id="form-correo" placeholder="Ingrese correo electrónico..." required oninput="limpiarErrorCorreo()" onblur="verificarCorreoExistente(this.value)">
                <div id="error-message-correo" class="error-message" style="display:none;"></div>

                <label>Contraseña:</label>
                <input type="password" name="contraseña" id="form-contraseña" placeholder="Ingrese contraseña...">

                <label>Rol:</label>
                <select name="id_rol" id="form-rol" required onchange="toggleCamposEstudiante()">
                    <option value="">Seleccione un rol</option>
                    <option value="1" <?php echo (isset($_POST['id_rol']) && $_POST['id_rol'] == '1') ? 'selected' : ''; ?>>Estudiante</option>
                    <option value="2" <?php echo (isset($_POST['id_rol']) && $_POST['id_rol'] == '2') ? 'selected' : ''; ?>>Docente</option>
                    <option value="3" <?php echo (isset($_POST['id_rol']) && $_POST['id_rol'] == '3') ? 'selected' : ''; ?>>Administrativo</option>
                    <option value="4" <?php echo (isset($_POST['id_rol']) && $_POST['id_rol'] == '4') ? 'selected' : ''; ?>>Administrador</option>
                </select>

                <label>Semestre:</label>
                <select name="semestre" id="form-semestre" disabled>
                    <option value="">Seleccione un semestre</option>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>

                <label>Programa:</label>
                <select name="id_programa" id="form-programa" disabled>
                    <option value="">Seleccione un programa</option>
                    <?php foreach ($programas as $programa) : ?>
                        <option value="<?php echo $programa['ID_Programa']; ?>">
                            <?php echo htmlspecialchars($programa['nombrePrograma']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" id="submitBtn">
                    <span id="submitBtnText">Guardar</span>
                    <span id="submitBtnLoader" style="display:none; margin-left:8px;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>
        </div>
    </div>

    <!-- HTML mejorado para el modal de confirmación -->
    <div id="modalConfirmDelete" class="modal-confirm" aria-modal="true" role="dialog">
        <div class="modal-confirm-content">
            <div class="modal-confirm-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>¿Estás seguro de eliminar este usuario?</h3>
            <div class="modal-confirm-message">
                Esta acción no se puede deshacer y eliminará todos los datos asociados al usuario.
            </div>
            <div class="modal-confirm-buttons">
                <button id="btnCancelDelete" class="btn-cancelar">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button id="btnConfirmDelete" class="btn-confirmar">
                    <i class="fas fa-check"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</body>

</html>

<script>
    // recargar la tabla y no la pagina completa
    function recargarTablaUsuarios() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '../Controlador/Tabla_Usuarios.php', true); // Debes crear este archivo PHP
        xhr.onload = function() {
            if (this.status === 200) {
                document.querySelector('#tablaUsuario tbody').innerHTML = this.responseText;
            } else {
                showToast('No se pudo actualizar la tabla de usuarios', 'error');
            }
        };
        xhr.send();
    }





    // Toast notification functions
    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
        <div>${message}</div>
        <button class="toast-close" onclick="closeToast(this.parentElement)">&times;</button>`;
        toastContainer.appendChild(toast);

        // Auto-close after 5 seconds
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

    // Filter table based on search input


    function filtrarTabla() {
        const input = document.getElementById("busqueda");
        const filtro = input.value.toLowerCase().trim();
        const tabla = document.getElementById("tablaUsuario");
        const filas = tabla.getElementsByTagName("tr");
        const mensajeSinResultados = document.getElementById("mensajeSinResultados");
        let hayCoincidencias = false;

        for (let i = 1; i < filas.length; i++) { // Empezamos en 1 para saltar el encabezado
            const fila = filas[i];
            const codigo = fila.cells[0]?.textContent || ''; // Código estudiantil
            const nombre = fila.cells[1]?.textContent || ''; // Nombre
            const correo = fila.cells[4]?.textContent || ''; // Correo

            // Buscar coincidencia en código, nombre o correo
            const coincide = codigo.toLowerCase().includes(filtro) ||
                nombre.toLowerCase().includes(filtro) ||
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


    // Verify if code already exists
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
        xhr.open('POST', '../Controlador/Verificar_Codigo.php', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
            if (this.status === 200) {
                const response = JSON.parse(this.responseText);
                if (response.existe) {
                    const errorMessage = document.getElementById('error-message');
                    errorMessage.innerText = 'El código de usuario ya existe.';
                    errorMessage.style.display = 'block';
                    codigoInput.dataset.error = 'true';
                    codigoInput.classList.add('input-error'); // <-- Añade esto
                } else {
                    document.getElementById('error-message').style.display = 'none';
                    codigoInput.dataset.error = 'false';
                    codigoInput.classList.remove('input-error'); // <-- Añade esto
                }
                actualizarEstadoBoton();
            }
        };

        xhr.send('codigo_u=' + encodeURIComponent(codigo));
    }

    function limpiarErrorCorreo() {
        document.getElementById('error-message-correo').style.display = 'none';
        document.getElementById('form-correo').dataset.error = 'false';
        actualizarEstadoBoton();
    }


    function limpiarErrorCodigo() {
        document.getElementById('error-message').style.display = 'none';
        const codigoInput = document.getElementById('form-codigo_u');
        codigoInput.dataset.error = 'false';
        codigoInput.classList.remove('input-error'); // <-- Añade esto
        actualizarEstadoBoton();
    }

    function limpiarErrores() {
        document.getElementById('error-message-correo').style.display = 'none';
        const correoInput = document.getElementById('form-correo');
        correoInput.dataset.error = 'false';
        correoInput.classList.remove('input-error'); // <-- Añade esto
        actualizarEstadoBoton();
    }

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
        xhr.open('POST', '../Controlador/verificar_correo.php', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
            if (this.status === 200) {
                const response = JSON.parse(this.responseText);
                if (response.existe) {
                    const errorMessage = document.getElementById('error-message-correo');
                    errorMessage.innerText = 'El correo electrónico ya está registrado.';
                    errorMessage.style.display = 'block';
                    correoInput.dataset.error = 'true';
                    correoInput.classList.add('input-error'); // <-- Añade esto
                } else {
                    document.getElementById('error-message-correo').style.display = 'none';
                    correoInput.dataset.error = 'false';
                    correoInput.classList.remove('input-error'); // <-- Añade esto
                }
                actualizarEstadoBoton();
            }
        };

        xhr.send('correo=' + encodeURIComponent(correo));
    }

    function actualizarEstadoBoton() {
        const codigoInput = document.getElementById('form-codigo_u');
        const correoInput = document.getElementById('form-correo');
        const submitBtn = document.getElementById('submitBtn');

        // Check for errors in code and email
        const codigoError = codigoInput.dataset.error === 'true';
        const correoError = correoInput.dataset.error === 'true';

        // Disable button if there are errors
        submitBtn.disabled = codigoError || correoError;

        // Add visual feedback when button is disabled
        if (submitBtn.disabled) {
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';
        } else {
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        }
    }
    // Open modal for adding or editing
    function openModal(action) {
        const modal = document.getElementById('formModal');
        const title = document.getElementById('modalTitle');
        const form = document.getElementById('usuarioForm');
        const passwordField = document.getElementById('form-contraseña');
        const passwordLabel = passwordField.previousElementSibling;

        if (action === 'agregar') {
            title.textContent = 'Agregar Usuario';
            form.setAttribute('data-action', '../Controlador/Agregar_Usuario.php');
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
        }

        modal.style.display = 'block';
        setTimeout(() => {
            document.getElementById('form-codigo_u').focus();
        }, 200);
        setTimeout(toggleCamposEstudiante, 100);
    }


    // Función modificada para abrir el formulario de modificación
    function openModificarForm(id, codigo_u, nombre, correo, programa, semestre, id_rol) {
        const modal = document.getElementById('formModal');
        const title = document.getElementById('modalTitle');
        const form = document.getElementById('usuarioForm');
        const passwordField = document.getElementById('form-contraseña');
        const passwordLabel = passwordField.previousElementSibling;

        title.textContent = 'Modificar Usuario';
        form.setAttribute('data-action', '../Controlador/Modificar_Usuario.php');

        document.getElementById('form-id').value = id;
        document.getElementById('form-codigo_u').value = codigo_u;
        document.getElementById('form-nombre').value = nombre;
        document.getElementById('form-telefono').value = telefono;
        document.getElementById('form-correo').value = correo;
        document.getElementById('form-programa').value = programa;
        document.getElementById('form-semestre').value = semestre;
        document.getElementById('form-rol').value = id_rol;

        passwordField.required = false;
        passwordField.style.display = 'none';
        passwordLabel.style.display = 'none';

        limpiarErrores();
        document.getElementById('form-codigo_u').setAttribute('data-original', codigo_u);
        document.getElementById('form-correo').setAttribute('data-original', correo);

        modal.style.display = 'block';

        // Ejecutar toggleCamposEstudiante para establecer estados de acuerdo al rol
        setTimeout(toggleCamposEstudiante, 100);
    }


    let usuarioAEliminar = null;

    function eliminarUsuario(id) {
        usuarioAEliminar = id;
        document.getElementById('modalConfirmDelete').style.display = 'block';
    }

    // Asigna los listeners solo una vez
    document.addEventListener('DOMContentLoaded', function() {
        const btnConfirm = document.getElementById('btnConfirmDelete');
        const btnCancel = document.getElementById('btnCancelDelete');
        const modalConfirm = document.getElementById('modalConfirmDelete');

        btnConfirm.onclick = function() {
            modalConfirm.style.display = 'none';

            // Realizar la eliminación mediante AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../Controlador/Eliminar_Usuario.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        if (response.status === 'success') {
                            showToast(response.message, 'success');
                            setTimeout(() => {
                                recargarTablaUsuarios();
                            }, 500);
                        } else {
                            showToast(response.message || 'Error al eliminar usuario', 'error');
                        }
                    } catch (e) {
                        showToast('Error en la respuesta del servidor', 'error');
                    }
                }
            };

            xhr.send('id_usuario=' + encodeURIComponent(usuarioAEliminar));
        };

        btnCancel.onclick = function() {
            modalConfirm.style.display = 'none';
        };
    });

    // Submit form with AJAX
    function submitForm(event) {
        event.preventDefault();

        // Validación adicional en frontend
        const rol = document.getElementById('form-rol').value;
        const esEstudiante = rol === '1';
        const codigo = document.getElementById('form-codigo_u').value.trim();
        const nombre = document.getElementById('form-nombre').value.trim();
        const telefono = document.getElementById('form-telefono').value.trim();
        const correo = document.getElementById('form-correo').value.trim();
        const semestre = document.getElementById('form-semestre').value.trim();
        const programa = document.getElementById('form-programa').value.trim();

        // Validar campos obligatorios
        if (!codigo || !nombre || !correo || !rol) {
            showToast('Por favor, complete todos los campos obligatorios.', 'error');
            return false;
        }

        // Validar correo electrónico (formato simple)
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(correo)) {
            showToast('Ingrese un correo electrónico válido.', 'error');
            return false;
        }

        // Si es estudiante, validar semestre y programa
        if (esEstudiante && (!semestre || !programa)) {
            showToast('Por favor, seleccione el semestre y el programa.', 'error');
            return false;
        }

        // --- Tu código AJAX y loader a partir de aquí ---
        const form = document.getElementById('usuarioForm');
        const formData = new FormData(form);

        // Loader en el botón
        const submitBtn = document.getElementById('submitBtn');
        const submitBtnText = document.getElementById('submitBtnText');
        const submitBtnLoader = document.getElementById('submitBtnLoader');
        submitBtn.disabled = true;
        submitBtnText.style.display = 'none';
        submitBtnLoader.style.display = 'inline-block';

        // Añadir un campo oculto para indicar si es estudiante
        formData.append('es_estudiante', esEstudiante ? '1' : '0');

        // Si no es estudiante, asegurarse de que los campos estén vacíos
        if (!esEstudiante) {
            const semestreField = document.getElementById('form-semestre');
            const programaField = document.getElementById('form-programa');
            semestreField.disabled = false;
            programaField.disabled = false;
            semestreField.value = '';
            programaField.value = '';
            formData.set('semestre', '');
            formData.set('id_programa', '');
            setTimeout(() => {
                semestreField.disabled = true;
                programaField.disabled = true;
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

            if (this.status === 200) {
                let response;
                try {
                    response = JSON.parse(this.responseText);
                    if (response.status === 'success') {
                        showToast(response.message, 'success');
                        closeModal();
                        setTimeout(() => {
                            recargarTablaUsuarios();
                        }, 500);
                    } else {
                        showToast(response.message || 'Hubo un error en la operación', 'error');
                    }
                } catch (e) {
                    console.error("Error al analizar respuesta JSON:", e);
                    console.log("Respuesta del servidor:", this.responseText);
                    if (this.responseText.includes("<br />") || this.responseText.includes("<!DOCTYPE")) {
                        showToast('Error del servidor. Revisa la consola para más detalles.', 'error');
                    } else {
                        showToast('Error en la respuesta del servidor: ' + this.responseText, 'error');
                    }
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

    // Close modal functions
    function closeModal() {
        document.getElementById('formModal').style.display = 'none';
        document.getElementById('usuarioForm').reset();
        limpiarErrores();
    }

    window.onclick = function(event) {
        const modalForm = document.getElementById('formModal');
    const modalConfirm = document.getElementById('modalConfirmDelete');
    // Cierra el modal de formulario si el click es fuera de su contenido
    if (event.target === modalForm) {
        closeModal(); // <-- Cambia esto
    }
    // Cierra el modal de confirmación si el click es fuera de su contenido
    if (event.target === modalConfirm) {
        modalConfirm.style.display = "none";
    }
    };

    function toggleCamposEstudiante() {
        const rol = document.getElementById('form-rol').value;
        const esEstudiante = rol === '1'; // 1 = Estudiante

        const semestreField = document.getElementById('form-semestre');
        const programaField = document.getElementById('form-programa');

        // Si es estudiante, habilitar y hacer obligatorios
        if (esEstudiante) {
            semestreField.disabled = false;
            programaField.disabled = false;
            semestreField.required = true;
            programaField.required = true;
        } else {
            // Si no es estudiante, deshabilitar y quitar obligatoriedad
            semestreField.disabled = true;
            programaField.disabled = true;
            semestreField.required = false;
            programaField.required = false;
            // Limpiar valores
            semestreField.value = '';
            programaField.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Configurar el evento onchange en el selector de rol
        const rolSelect = document.getElementById('form-rol');
        rolSelect.addEventListener('change', toggleCamposEstudiante);

        // También ejecutar al abrir el modal para establecer el estado inicial
        const addBtn = document.querySelector('.btn-agregar');
        if (addBtn) {
            addBtn.addEventListener('click', function() {
                // Dar tiempo para que el modal se abra y luego ejecutar
                setTimeout(toggleCamposEstudiante, 100);
            });
        }
    });
</script>

<?php
$conn->close();
?>