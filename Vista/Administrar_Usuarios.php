<?php
include("../database/conexion.php");
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

</head>

<body class="Registro">

    <?php include("../Vista/Sidebar.php"); ?>

    <section class="Main">
        <section class="Encabezado">
            <div class="barra-superior">
                <div class="busqueda-container">
                    <input type="text" id="busqueda" placeholder="Buscar nombre o correo..." onkeyup="filtrarTabla()">


                    </button>
                </div>
            </div>
        </section>
        <script>
            function filtrarTabla() {
                const input = document.getElementById("busqueda").value.toLowerCase().trim();
                const table = document.getElementById("tablaUsuario"); // Corrected ID to match the table
                const rows = table.getElementsByTagName("tr");
                const mensajeSinResultados = document.getElementById("mensajeSinResultados");
                let hayCoincidencias = false;
                for (let i = 1; i < rows.length; i++) { // Start from 1 to skip the header row
                    const cells = rows[i].getElementsByTagName("td");
                    let match = false;
                    for (let j = 0; j < cells.length; j++) {
                        if (cells[j].textContent.toLowerCase().includes(input)) {
                            match = true;
                            break;
                        }
                    }
                    rows[i].style.display = match ? "" : "none";
                    if (match) hayCoincidencias = true;
                }
                mensajeSinResultados.style.display = hayCoincidencias ? "none" : "block";
            }
        </script>

        <div class="contenedor-usuarios">
            <h2>Lista de Usuarios</h2>

            <button class="btn-agregar" onclick="openModal('agregar')">
                <img src=" ../Imagen/Iconos/Agregar_Usuario.svg" alt="" />
                    
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
                                    <form method="post" action="../Controlador/Eliminar_Usuario.php" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">
                                        <input type="hidden" name="id_usuario" value="<?php echo $row['ID_Usuario']; ?>">
                                        <button type="submit" class="btn btn-eliminar">Eliminar</button>
                                    </form>
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
    </section>

    <!-- Modal para Agregar/Modificar Usuario -->
    <div id="formModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Agregar Usuario</h2>
            <form id="usuarioForm" method="post">
                <input type="hidden" name="id_usuario" id="form-id">
                <label>Código de Usuario:</label>
                <input type="text" name="codigo_u" id="form-codigo_u" placeholder="Ingrese código de identificación..." required oninput="limpiarError()" onblur="verificarCodigoExistente(this.value)">
                <div id="error-message" class="error-message" style="display:none; color: #e74c3c; font-size: 12px; margin-top: -12px; margin-bottom: 8px;"></div>

                <label>Nombre:</label>
                <input type="text" name="nombre" id="form-nombre" placeholder="Ingrese nombre completo..." required>

                <label>Correo:</label>
                <input type="email" name="correo" id="form-correo" placeholder="Ingrese correo electrónico..." required>

                <label>Contraseña:</label>
                <input type="password" name="contraseña" id="form-contraseña" placeholder="Ingrese contraseña...">

                <label>Rol:</label>
                <select name="id_rol" id="form-rol" required>
                    <option value="">Seleccione un rol</option>
                    <option value="1" <?php echo (isset($_POST['id_rol']) && $_POST['id_rol'] == '1') ? 'selected' : ''; ?>>Estudiante</option>
                    <option value="2" <?php echo (isset($_POST['id_rol']) && $_POST['id_rol'] == '2') ? 'selected' : ''; ?>>Docente</option>
                    <option value="3" <?php echo (isset($_POST['id_rol']) && $_POST['id_rol'] == '3') ? 'selected' : ''; ?>>Administrativo</option>
                    <option value="4" <?php echo (isset($_POST['id_rol']) && $_POST['id_rol'] == '4') ? 'selected' : ''; ?>>Administrador</option>
                </select>

                <label>Semestre:</label>
                <select name="semestre" id="form-semestre" required>
                    <option value="">Seleccione un semestre</option>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>

                <label>Programa:</label>
                <select name="id_programa" id="form-programa" required>
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
        function verificarCodigoExistente(codigo) {
            if (codigo.trim() === '') return;

            // Obtener el elemento de input de código de usuario
            const codigoInput = document.getElementById('form-codigo_u');

            // Verificar si estamos en modo edición y si el código no ha cambiado
            const esEdicion = document.getElementById('modalTitle').textContent === 'Modificar Usuario';
            const codigoOriginal = codigoInput.getAttribute('data-original');

            // Si estamos en modo edición y el código no ha cambiado, no hacemos verificación
            if (esEdicion && codigo === codigoOriginal) {
                document.getElementById('error-message').style.display = 'none';
                document.getElementById('submitBtn').disabled = false;
                return;
            }

            // Crear una solicitud AJAX para verificar el código
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../Controlador/Verificar_Codigo.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
                if (this.status === 200) {
                    const response = JSON.parse(this.responseText);
                    if (response.existe) {
                        // Si el código ya existe, mostrar mensaje de error
                        const errorMessage = document.getElementById('error-message');
                        errorMessage.innerText = 'El código de usuario ya existe.';
                        errorMessage.style.display = 'block';
                        document.getElementById('submitBtn').disabled = true;
                    } else {
                        // Si el código no existe, ocultar mensaje de error
                        document.getElementById('error-message').style.display = 'none';
                        document.getElementById('submitBtn').disabled = false;
                    }
                }
            };

            xhr.send('codigo_u=' + encodeURIComponent(codigo));
        }

        function limpiarError() {
            // Ocultar mensaje de error cuando el usuario empieza a escribir
            document.getElementById('error-message').style.display = 'none';
            document.getElementById('submitBtn').disabled = false;
        }

        function openModal(action) {
            const modal = document.getElementById('formModal');
            const title = document.getElementById('modalTitle');
            const form = document.getElementById('usuarioForm');
            const passwordField = document.getElementById('form-contraseña');
            const passwordLabel = passwordField.previousElementSibling; // La etiqueta del campo de contraseña

            if (action === 'agregar') {
                title.textContent = 'Agregar Usuario';
                form.action = '../Controlador/Agregar_Usuario.php';
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

                // Limpiar mensajes de error previos
                limpiarError();
            }

            modal.style.display = 'block';
        }

        function openModificarForm(id, codigo_u, nombre, correo, programa, semestre, id_rol) {
            const modal = document.getElementById('formModal');
            const title = document.getElementById('modalTitle');
            const form = document.getElementById('usuarioForm');
            const passwordField = document.getElementById('form-contraseña');
            const passwordLabel = passwordField.previousElementSibling; // La etiqueta del campo de contraseña

            title.textContent = 'Modificar Usuario';
            form.action = '../Controlador/Modificar_Usuario.php';

            document.getElementById('form-id').value = id;
            document.getElementById('form-codigo_u').value = codigo_u;
            document.getElementById('form-nombre').value = nombre;
            document.getElementById('form-correo').value = correo;
            document.getElementById('form-programa').value = programa;
            document.getElementById('form-semestre').value = semestre;

            // Establecer el valor del rol seleccionado usando el ID del rol
            document.getElementById('form-rol').value = id_rol;

            // Ocultar el campo de contraseña y su etiqueta
            passwordField.style.display = 'none';
            passwordLabel.style.display = 'none';

            // Ocultar mensaje de error si está visible
            limpiarError();

            // Guardamos el código original para comparar en la verificación
            document.getElementById('form-codigo_u').setAttribute('data-original', codigo_u);

            modal.style.display = 'block';
        }


        function closeModal() {
            document.getElementById('formModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('formModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>

</html>

<?php
$conn->close();
?>

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
</style>