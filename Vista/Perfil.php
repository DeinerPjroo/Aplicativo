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
    $programa = $row['programa'];
    $fotoPerfil = !empty($row['fotoPerfil']) && file_exists("../" . $row['fotoPerfil']) ? "../" . $row['fotoPerfil'] : '../Imagen/default.jpg'; // Verifica si la imagen existe
} else {
    $nombreUsuario = 'Usuario no identificado';
    $correoUsuario = 'Correo no disponible';
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
    if ($role === 'Docente') {
        include("../Vista/Sidebar_Docente.html");
    } elseif ($role === 'Administrador') {
        include("../Vista/Sidebar.html");
    } elseif ($role === 'Administrativo') {
        include("../Vista/Sidebar_Administrativo.html");
    } elseif ($role === 'Estudiante') {
        include("../Vista/Sidebar_Estudiante.html");
    }
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
                <form action="../Controlador/subir_foto.php" method="POST" enctype="multipart/form-data">
                    <input type="file" name="foto" id="foto" style="display: none;" onchange="this.form.submit();"> <!-- Oculta el input de archivo -->
                    <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($usuario_id); ?>"> <!-- ID real -->
                    <center><button type="button" onclick="document.getElementById('foto').click();">Cambiar foto de perfil</button></center> <!-- Botón para activar el input -->
                </form>

                <!-- Muestra el nombre, programa y correo del usuario logueado -->
                <label for="Nombre">Nombre</label><br>
                <input type="text" name="nombreUsuario" id="nombreUsuario" value="<?php echo htmlspecialchars($nombreUsuario); ?>" readonly><br>

                <label for="Programa">Programa</label><br>
                <input type="text" name="programaUsuario" id="programaUsuario" value="<?php echo htmlspecialchars($programa); ?>" readonly><br>

                <label for="Correo">Correo</label><br>
                <input type="text" name="correoUsuario" id="correoUsuario" value="<?php echo htmlspecialchars($correoUsuario); ?>" readonly><br>
            </section>
        </section>
    </section>
</body>

</html>