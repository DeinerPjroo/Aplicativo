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

        <!-- Tabla de usuarios dinámica -->
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
            <tbody id="tbodyUsuarios"></tbody>
        </table>
        <script>
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
                    filtrarTabla(); // Para aplicar el filtro si hay texto en búsqueda
                });
        }
        document.addEventListener('DOMContentLoaded', cargarUsuarios);
        </script>
        <!-- Renderizado PHP de la tabla de usuarios eliminado: ahora todo es dinámico por AJAX -->
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
                <input type="text" name="telefono" id="form-telefono" placeholder="Ingrese telefono..." required>

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

<script src="../js/administrar_usuarios.js"></script>

<?php
$conn->close();
?>