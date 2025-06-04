<?php
session_start(); // Asegúrate de iniciar la sesión al principio del archivo.

include("../Controlador/control_De_Rol.php");
checkRole(['Docente', 'Estudiante', 'Administrador', 'Administrativo']); // Solo usuarios con rol pueden acceder

$role = getUserRole(); // Asume que esta función devuelve el rol del usuario actual

// Conexión a la base de datos
include("../database/conection.php");

// Obtener el ID del usuario logueado
$usuario_id = $_SESSION['usuario_id'];

// Consulta para obtener el nombre, programa, correo y foto del usuario logueado
// NOTA: Se ha eliminado la referencia a u.telefono ya que no existe en la tabla
$sql = "SELECT 
            u.nombre AS nombreUsuario,
            u.correo AS correoUsuario,
            COALESCE(pr.nombrePrograma, 'Sin programa') AS programa,
            u.telefono AS telefonoUsuario -- Se agrega la columna telefono
        FROM usuario u
        LEFT JOIN programa pr ON u.ID_Programa = pr.ID_Programa -- Relación con programa
        WHERE u.ID_Usuario = ?"; // Filtrar por el usuario logueado

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id); // Vincular el ID del usuario logueado
$stmt->execute();
$result = $stmt->get_result();

// Verificar si se encontró el usuario
if ($row = $result->fetch_assoc()) {
    $nombreUsuario = $row['nombreUsuario'];
    $correoUsuario = $row['correoUsuario'];
    $telefonoUsuario = !empty($row['telefonoUsuario']) ? $row['telefonoUsuario'] : ''; // Se elimina el valor predeterminado "No disponible"
    $programa = $row['programa'];
    $fotoPerfil = '../Imagen/default.jpg'; // Imagen predeterminada definida directamente
} else {
    $nombreUsuario = 'Usuario no identificado';
    $correoUsuario = 'Correo no disponible';
    $telefonoUsuario = 'No disponible';
    $programa = 'Sin programa';
    $fotoPerfil = '../Imagen/default.jpg'; // Imagen predeterminada
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    <title>Perfil de Usuario</title>    <link rel="stylesheet" href="../css/Style.css">
    
</head>

<body>    <!-- Sidebar incluido desde el archivo externo -->
    <?php
    include("../Vista/Sidebar.php");
    ?>
    
    <!-- BOTÓN DE MENÚ MÓVIL -->
    <button class="menu-toggle" id="menuToggle">
        <img src="../Imagen/Iconos/Menu_3lineas.svg" alt="Menú" class="menu-icon">
    </button>
    
    <!-- OVERLAY PARA CERRAR MENÚ -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="container">
        <div class="main-content" style="padding: 0px !important;">
            <div class="Topbard" style="padding: 0px !important;">
                <h1>Perfil de Usuario</h1>
            </div>

            <div class="profile-container">
                <div class="profile-image">
                    <img src="<?php echo htmlspecialchars($fotoPerfil); ?>" alt="Foto de perfil"> <!-- Se mantiene la referencia a la imagen -->
                    <button type="button" class="btn-agregar" onclick="abrirModal()">Cambiar contraseña</button>
                </div>

                <div class="profile-details">
                    <form id="formActualizarPerfil" action="../Controlador/ControladorPerfil.php" method="POST">
                        <input type="hidden" name="accion" value="actualizar_datos">
                        <div class="form-group">
                            <label for="nombreUsuario">Nombre</label>
                            <input type="text" name="nombreUsuario" id="nombreUsuario" value="<?php echo htmlspecialchars($nombreUsuario); ?>">
                        </div>

                        <div class="form-group">
                            <label for="programaUsuario">Programa</label>
                            <input type="text" name="programaUsuario" id="programaUsuario" value="<?php echo htmlspecialchars($programa); ?>" readonly>
                            <small style="color: var(--text-light);">El programa no puede ser editado directamente</small>
                        </div>

                        <div class="form-group">
                            <label for="correoUsuario">Correo</label>
                            <input type="email" name="correoUsuario" id="correoUsuario" value="<?php echo htmlspecialchars($correoUsuario); ?>" required pattern="^[^@]+@[^@]+\.[a-zA-Z]{2,}$" title="Por favor, ingrese un correo válido que contenga '@'.">
                        </div>

                        <div class="form-group">
                            <label for="telefonoUsuario">Teléfono</label>
                            <input type="text" name="telefonoUsuario" id="telefonoUsuario" value="<?php echo htmlspecialchars($telefonoUsuario); ?>">
                        </div>

                        <button type="submit" class="btn-agregar">Guardar cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para cambiar contraseña -->
    <div id="modalCambiarContraseña" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2>Cambiar Contraseña</h2>
            <form id="formActualizarContrasena" action="../Controlador/ControladorPerfil.php" method="POST">
                <input type="hidden" name="accion" value="actualizar_contraseña">
                <div class="form-group">
                    <label for="passwordActual">Contraseña actual</label>
                    <input type="password" name="passwordActual" id="passwordActual" required>
                </div>

                <div class="form-group">
                    <label for="passwordNueva">Nueva contraseña</label>
                    <input type="password" name="passwordNueva" id="passwordNueva" required>
                </div>

                <div class="form-group">
                    <label for="passwordConfirmar">Confirmar nueva contraseña</label>
                    <input type="password" name="passwordConfirmar" id="passwordConfirmar" required>
                </div>

                <button type="submit" class="btn-agregar" style="margin: 0 auto; justify-content: center; width: 60%; text-align: center !important; display: flex;">Guardar contraseña</button>
            </form>
        </div>
    </div>    <script src="../js/sidebar.js"></script>
    <script src="../js/mobile_menu.js"></script>
    <script src="../js/perfil.js"></script>
</body>

</html>