<?php
include("../database/conection.php");
include("../Controlador/control_De_Rol.php");
checkRole('Administrador'); // Solo administradores pueden acceder


$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT u.ID_Usuario, u.codigo_u, u.nombre, p.nombrePrograma AS programa, u.Id_Programa, u.semestre, u.correo, r.nombreRol AS rol, u.id_rol
             FROM usuario u
             LEFT JOIN programa p ON u.Id_Programa = p.ID_Programa
             LEFT JOIN rol r ON u.id_rol = r.id_rol";


if (!empty($search)) {
    $query .= " WHERE u.codigo_u LIKE '%$search%' 
                OR u.nombre LIKE '%$search%' 
                OR u.correo LIKE '%$search%'";
}
$result = $conn->query($query);

// Obtener los programas desde la base de datos
$programasQuery = "SELECT ID_Programa, nombrePrograma FROM programa";
$programasResult = $conn->query($programasQuery);

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

    <style>
        .contenedor-usuarios {
            width: 90%;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-left: 110px !important;
        }

        .contenedor-usuarios h2 {
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }

        .tabla-usuarios {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .tabla-usuarios th,
        .tabla-usuarios td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .tabla-usuarios th {
            background-color: rgb(45, 158, 178);
            color: white;
            font-weight: 600;
        }

        .tabla-usuarios tr:hover {
            background-color: #f5f5f5;
        }

        .sin-usuarios {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
            font-style: italic;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin: 20px 0;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }

        .btn-agregar {
            background-color: #28a745;
            color: white;
        }

        .btn-modificar {
            background-color: #ffc107;
            color: black;
        }

        .btn-eliminar {
            background-color: #dc3545;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        /* Estilos del modal mejorados */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            /* Cambiado de auto a hidden para evitar el scroll externo */
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 400px;
            max-width: 90%;
            border-radius: 10px;
            max-height: 80vh;
            /* Altura máxima del 80% de la ventana */
            overflow-y: auto;
            /* Añadir scroll vertical solo cuando sea necesario */
            position: relative;
            /* Para posicionamiento de elementos internos */
        }

        /* Estilos para la barra de desplazamiento */
        .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: #d07c2e;
            border-radius: 10px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #b9651f;
        }

        /* Mantén el botón de cerrar siempre visible */
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: sticky;
            top: 0;
            right: 0;
        }

        /* Resto de estilos del modal */
        .modal-content label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        .modal-content input,
        .modal-content select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            /* Asegura que el padding no afecte el ancho total */
        }

        .modal-content button[type="submit"] {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px;
            width: 100%;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
            margin-bottom: 5px;
        }

        .modal-content button[type="submit"]:hover {
            background-color: #218838;
        }

        /* barra de busques estilo / */
        /* hola */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .barra-superior {
            background-color: #d07c2e;
            /* Naranja similar */
            padding: 15px 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .busqueda-container {
            background-color: white;
            padding: 6px 10px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            width: 600px;
            max-width: 90%;
        }

        .busqueda-container input[type="text"] {
            border: none;
            outline: none;
            font-size: 14px;
            flex: 1;
            padding: 8px;
        }

        .busqueda-container button {
            background-color: #d07c2e;
            color: white;
            border: none;
            padding: 8px 12px;
            margin-left: 8px;
            border-radius: 4px;
            cursor: pointer;
        }

        .busqueda-container button:hover {
            background-color: #b9651f;
        }

        #resultado {
            text-align: center;
            margin-top: 30px;
            font-size: 18px;
        }

        /* Estilo para el mensaje de error */
        .error-message {
            color: #e74c3c;
            font-size: 12px;
            margin-top: -12px;
            margin-bottom: 8px;
        }

        /* Estilo para notificaciones */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }

        .toast {
            background-color: #fff;
            color: #333;
            border-radius: 5px;
            padding: 12px 20px;
            margin-bottom: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 250px;
            max-width: 350px;
            animation: slide-in 0.3s ease-out forwards;
        }

        .toast.success {
            border-left: 5px solid #28a745;
        }

        .toast.error {
            border-left: 5px solid #dc3545;
        }

        .toast.info {
            border-left: 5px solid #17a2b8;
        }

        .toast-close {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }

        @keyframes slide-in {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fade-out {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Estilos mejorados para el modal de confirmación */
        .modal-confirm {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            animation: fadeIn 0.3s ease;
            backdrop-filter: blur(3px);
        }

        .modal-confirm-content {
            position: relative;
            background-color: #fff;
            width: 400px;
            margin: 15% auto;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            text-align: center;
            animation: scaleIn 0.4s ease;
            border-top: 5px solid #d07c2e;
        }

        .modal-confirm-icon {
            font-size: 48px;
            color: #d07c2e;
            margin-bottom: 20px;
        }

        .modal-confirm h3 {
            margin-top: 10px;
            color: #333;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 25px;
            line-height: 1.4;
        }

        .modal-confirm-message {
            color: #666;
            font-size: 15px;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .modal-confirm-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .btn-confirmar {
            background-color: #d07c2e;
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 3px 8px rgba(208, 124, 46, 0.3);
        }

        .btn-confirmar:hover {
            background-color: #b9651f;
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(208, 124, 46, 0.4);
        }

        .btn-cancelar {
            background-color: #2d9eb2;
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 3px 8px rgba(45, 158, 178, 0.3);
        }

        .btn-cancelar:hover {
            background-color: #258797;
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(45, 158, 178, 0.4);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes scaleIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Efecto al hacer clic en los botones */
        .btn-confirmar:active,
        .btn-cancelar:active {
            transform: translateY(1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body class="Registro">

    <?php include("../Vista/Sidebar.php"); ?>

    
        <div class="Topbard">
            <input type="text" id="busqueda" placeholder="Buscar por código, nombre o correo..." onkeyup="filtrarTabla()">
        </div>

        <!-- Toast Container for Notifications -->
        <div id="toastContainer" class="toast-container"></div>

        <div class="contenedor-usuarios">
            <h2>Lista de Usuarios</h2>

            <button class="btn btn-agregar" onclick="openModal('agregar')">Agregar Usuario</button>

            <div id="mensajeSinResultados" style="display:none;" class="sin-usuarios">
                <p>No se encontraron usuarios con ese criterio de búsqueda</p>
            </div>

            <?php if ($result->num_rows > 0) : ?>
                <table id="tablaUsuario" class="tabla-usuarios">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
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
                                <td><?php echo htmlspecialchars($row['programa']); ?></td>
                                <td><?php echo htmlspecialchars($row['semestre'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['correo']); ?></td>
                                <td><?php echo htmlspecialchars($row['rol']); ?></td>
                                <td>
                                    <button class="btn btn-modificar"
                                        onclick="openModificarForm(
                                        <?php echo $row['ID_Usuario']; ?>,
                                        '<?php echo htmlspecialchars($row['codigo_u'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($row['nombre'], ENT_QUOTES); ?>',
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
    <div id="formModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Agregar Usuario</h2>
            <form id="usuarioForm" onsubmit="return submitForm(event)">
                <input type="hidden" name="id_usuario" id="form-id">
                <label>Código de Usuario:</label>
                <input type="text" name="codigo_u" id="form-codigo_u" placeholder="Ingrese código de identificación..." required oninput="limpiarError()" onblur="verificarCodigoExistente(this.value)">
                <div id="error-message" class="error-message" style="display:none;"></div>

                <label>Nombre:</label>
                <input type="text" name="nombre" id="form-nombre" placeholder="Ingrese nombre completo..." required>

                <label>Correo:</label>
                <input type="email" name="correo" id="form-correo" placeholder="Ingrese correo electrónico..." required oninput="limpiarError()" onblur="verificarCorreoExistente(this.value)">
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
                    <?php while ($programa = $programasResult->fetch_assoc()) : ?>
                        <option value="<?php echo $programa['ID_Programa']; ?>">
                            <?php echo htmlspecialchars($programa['nombrePrograma']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <button type="submit" id="submitBtn">Guardar</button>
            </form>
        </div>
    </div>

    <script>
        // Toast notification functions
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'toast ${type}';
            toast.innerHTML = `
        <div>${message}</div>
        <button class="toast-close" onclick="closeToast(this.parentElement)">&times;</button>
    `;
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
                document.getElementById('submitBtn').disabled = false;
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
                        document.getElementById('submitBtn').disabled = true;
                    } else {
                        document.getElementById('error-message').style.display = 'none';
                        document.getElementById('submitBtn').disabled = false;
                    }
                }
            };

            xhr.send('codigo_u=' + encodeURIComponent(codigo));
        }

        function limpiarError() {
            document.getElementById('error-message').style.display = 'none';
            document.getElementById('submitBtn').disabled = false;
        }

        function verificarCorreoExistente(correo) {
            if (correo.trim() === '') return;

            const correoInput = document.getElementById('form-correo');
            const esEdicion = document.getElementById('modalTitle').textContent === 'Modificar Usuario';
            const correoOriginal = correoInput.getAttribute('data-original');

            if (esEdicion && correo === correoOriginal) {
                document.getElementById('error-message-correo').style.display = 'none';
                document.getElementById('submitBtn').disabled = false;
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
                        document.getElementById('submitBtn').disabled = true;
                    } else {
                        document.getElementById('error-message-correo').style.display = 'none';
                        document.getElementById('submitBtn').disabled = false;
                    }
                }
            };

            xhr.send('correo=' + encodeURIComponent(correo));
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
                document.getElementById('form-correo').value = '';
                document.getElementById('form-contraseña').required = true;
                document.getElementById('form-contraseña').value = '';
                document.getElementById('form-rol').value = '';
                document.getElementById('form-semestre').value = '';
                document.getElementById('form-programa').value = '';
                passwordField.style.display = 'block';
                passwordLabel.style.display = 'block';

                limpiarError();
            }

            modal.style.display = 'block';

            // Ejecutar para establecer los estados iniciales de los campos
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
            document.getElementById('form-correo').value = correo;
            document.getElementById('form-programa').value = programa;
            document.getElementById('form-semestre').value = semestre;
            document.getElementById('form-rol').value = id_rol;

            passwordField.required = false;
            passwordField.style.display = 'none';
            passwordLabel.style.display = 'none';

            limpiarError();
            document.getElementById('form-codigo_u').setAttribute('data-original', codigo_u);
            document.getElementById('form-correo').setAttribute('data-original', correo);

            modal.style.display = 'block';

            // Ejecutar toggleCamposEstudiante para establecer estados de acuerdo al rol
            setTimeout(toggleCamposEstudiante, 100);
        }

        // Delete user with AJAX
        // Modificación de la función eliminarUsuario en tu JavaScript
        // Coloca este código en el mismo archivo donde tienes la función eliminarUsuario

        // Añadir estos estilos al final de tu sección <style> en el HTML
        /*
        .modal-confirm {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            animation: fadeIn 0.3s;
        }

        .modal-confirm-content {
            position: relative;
            background-color: #fff;
            width: 350px;
            margin: 15% auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: slideIn 0.3s;
        }

        .modal-confirm h3 {
            margin-top: 0;
            color: #333;
            font-size: 18px;
            margin-bottom: 30px;
        }

        .modal-confirm-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }

        .btn-confirmar {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .btn-confirmar:hover {
            background-color: #2980b9;
        }

        .btn-cancelar {
            background-color: #7f8c8d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .btn-cancelar:hover {
            background-color: #6c7a7d;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        */

        // HTML para el modal de confirmación - Añádelo antes del cierre de tu </body>
        /*
        <div id="modalConfirmDelete" class="modal-confirm">
            <div class="modal-confirm-content">
                <h3>¿Estás seguro de que deseas eliminar este usuario?</h3>
                <div class="modal-confirm-buttons">
                    <button id="btnConfirmDelete" class="btn-confirmar">Aceptar</button>
                    <button id="btnCancelDelete" class="btn-cancelar">Cancelar</button>
                </div>
            </div>
        </div>
        */

        // Función modificada para eliminar usuario
        function eliminarUsuario(id) {
            // Mostrar el modal personalizado en lugar del confirm nativo
            const modalConfirm = document.getElementById('modalConfirmDelete');
            const btnConfirm = document.getElementById('btnConfirmDelete');
            const btnCancel = document.getElementById('btnCancelDelete');

            modalConfirm.style.display = 'block';

            // Evento para el botón confirmar
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
                                // Eliminar la fila de la tabla
                                const rows = document.querySelectorAll('#tablaUsuario tbody tr');
                                for (let row of rows) {
                                    const cells = row.getElementsByTagName('td');
                                    if (cells[0].textContent === response.codigo) {
                                        row.remove();
                                        break;
                                    }
                                }
                            } else {
                                showToast(response.message || 'Error al eliminar usuario', 'error');
                            }
                        } catch (e) {
                            showToast('Error en la respuesta del servidor', 'error');
                        }
                    }
                };

                xhr.send('id_usuario=' + encodeURIComponent(id));
            };

            // Evento para el botón cancelar
            btnCancel.onclick = function() {
                modalConfirm.style.display = 'none';
            };

            // Cerrar el modal al hacer clic fuera del contenido
            window.onclick = function(event) {
                if (event.target == modalConfirm) {
                    modalConfirm.style.display = 'none';
                }
            };
        }

        // Submit form with AJAX
        function submitForm(event) {
            event.preventDefault();

            // Obtener el rol actual
            const rol = document.getElementById('form-rol').value;
            const esEstudiante = rol === '1'; // 1 = Estudiante

            const form = document.getElementById('usuarioForm');
            const formData = new FormData(form);

            // Añadir un campo oculto para indicar si es estudiante
            formData.append('es_estudiante', esEstudiante ? '1' : '0');

            // Si no es estudiante, asegurarse de que los campos estén vacíos
            if (!esEstudiante) {
                const semestreField = document.getElementById('form-semestre');
                const programaField = document.getElementById('form-programa');

                // Habilitar temporalmente para poder incluirlos en el FormData
                semestreField.disabled = false;
                programaField.disabled = false;

                // Establecer valores vacíos
                semestreField.value = '';
                programaField.value = '';

                // Actualizar en formData
                formData.set('semestre', '');
                formData.set('id_programa', '');

                // Volver a deshabilitar después de un pequeño delay
                setTimeout(() => {
                    semestreField.disabled = true;
                    programaField.disabled = true;
                }, 10);
            }

            const action = form.getAttribute('data-action');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', action, true);

            xhr.onload = function() {
                // Volver a deshabilitar campos si es necesario
                if (!esEstudiante) {
                    document.getElementById('form-semestre').disabled = true;
                    document.getElementById('form-programa').disabled = true;
                }

                if (this.status === 200) {
                    let response;
                    try {
                        // Intentar analizar la respuesta como JSON
                        response = JSON.parse(this.responseText);

                        if (response.status === 'success') {
                            showToast(response.message, 'success');
                            closeModal();
                            // Recargar la tabla
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showToast(response.message || 'Hubo un error en la operación', 'error');
                        }
                    } catch (e) {
                        // Si hay un error al analizar JSON, mostrar el texto de respuesta para debugging
                        console.error("Error al analizar respuesta JSON:", e);
                        console.log("Respuesta del servidor:", this.responseText);

                        // Verificar si la respuesta contiene HTML (probable error PHP)
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
                // Volver a deshabilitar campos si es necesario
                if (!esEstudiante) {
                    document.getElementById('form-semestre').disabled = true;
                    document.getElementById('form-programa').disabled = true;
                }
            };

            xhr.send(formData);
            return false;
        }

        // Close modal functions
        function closeModal() {
            document.getElementById('formModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('formModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

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
    <!-- HTML mejorado para el modal de confirmación -->
    <div id="modalConfirmDelete" class="modal-confirm">
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

<?php
$conn->close();
?>