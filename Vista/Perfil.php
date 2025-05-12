<?php
session_start(); // Asegúrate de iniciar la sesión al principio del archivo.

include("../Controlador/control_De_Rol.php");
checkRole(['Docente', 'Estudiante', 'Administrador', 'Administrativo']); // Solo usuarios con rol pueden acceder

$role = getUserRole(); // Asume que esta función devuelve el rol del usuario actual

// Conexión a la base de datos
include("../database/conexion.php");

// Obtener el ID del usuario logueado
$usuario_id = $_SESSION['usuario_id'];

// Consulta para obtener el nombre, programa, correo y foto del usuario logueado
// NOTA: Se ha eliminado la referencia a u.telefono ya que no existe en la tabla
$sql = "SELECT 
            u.nombre AS nombreUsuario,
            u.correo AS correoUsuario,
            COALESCE(pr.nombrePrograma, 'Sin programa') AS programa,
            u.fotoPerfil AS fotoPerfil
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
    $telefonoUsuario = 'No disponible'; // Valor por defecto ya que no existe la columna
    $programa = $row['programa'];
    $fotoPerfil = !empty($row['fotoPerfil']) && file_exists("../" . $row['fotoPerfil']) ? "../" . $row['fotoPerfil'] : '../Imagen/default.jpg'; // Verifica si la imagen existe
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="../css/Style.css">
    <!--Link de google font (iconos)-->

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">


</head>

<body>

    <!------------------------------------------------------------------------------------->
    <!--SIDEBAR-->
    <?php
    include("../Vista/Sidebar.php");
    ?>
    <!------------------------------------------------------------------------------------->

    <section class="Encabezado">
        <h1>
            <center>Perfil</center>
        </h1>
    </section>
    <section class="Panel-Perfil">

        <section class="Perfil">
        <img src="<?php echo htmlspecialchars($fotoPerfil); ?>" alt="Foto de perfil">
        <br>
        <br>

            <section>
                <form action="../Controlador/actualizar_usuario.php" method="POST">
                    <!-- Muestra el nombre, programa, correo y teléfono del usuario logueado -->
                    <label for="Nombre">Nombre</label><br>
                    <input type="text" name="nombreUsuario" id="nombreUsuario" value="<?php echo htmlspecialchars($nombreUsuario); ?>"><br>

                    <label for="Programa">Programa</label><br>
                    <input type="text" name="programaUsuario" id="programaUsuario" value="<?php echo htmlspecialchars($programa); ?>"><br>

                    <label for="Correo">Correo</label><br>
                    <input type="email" name="correoUsuario" id="correoUsuario" value="<?php echo htmlspecialchars($correoUsuario); ?>"><br>

                    <label for="Telefono">Teléfono</label><br>
                    <input type="text" name="telefonoUsuario" id="telefonoUsuario" value="<?php echo htmlspecialchars($telefonoUsuario); ?>"><br>

                    <!-- Botón para abrir el modal de cambiar contraseña -->
                    <center><button type="button" onclick="abrirModal()">Cambiar contraseña</button></center>
                    <br>

                    <!-- Agregar un botón para guardar los cambios -->
                    <center><button type="submit">Guardar cambios</button></center>
                </form>
            </section>
        </section>
    </section>

    <!-- Modal para cambiar contraseña -->
    <div id="modalCambiarContraseña" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal" onclick="cerrarModal()">&times;</span>
            <h2>Cambiar Contraseña</h2>
            <form action="../Controlador/actualizar_contraseña.php" method="POST">
                <label for="passwordActual">Contraseña actual</label><br>
                <input type="password" name="passwordActual" id="passwordActual" required><br>

                <label for="passwordNueva">Nueva contraseña</label><br>
                <input type="password" name="passwordNueva" id="passwordNueva" required><br>

                <label for="passwordConfirmar">Confirmar nueva contraseña</label><br>
                <input type="password" name="passwordConfirmar" id="passwordConfirmar" required><br>

                <center><button type="submit">Guardar contraseña</button></center>
            </form>
        </div>
    </div>

    <script>
        function abrirModal() {
            document.getElementById('modalCambiarContraseña').style.display = 'block';
        }

        function cerrarModal() {
            document.getElementById('modalCambiarContraseña').style.display = 'none';
        }

        // Cerrar el modal si se hace clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('modalCambiarContraseña');
            if (event.target === modal) {
                cerrarModal();
            }
        };
    </script>
</body>

</html>