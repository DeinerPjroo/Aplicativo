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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario</title>
    <link rel="stylesheet" href="../css/Style.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        /* Estilos adicionales para la página de perfil */
       

       

        .container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            /* Ajusta según el ancho de tu sidebar */
        }



        .profile-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .profile-image {
            flex: 0 0 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-image img {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .profile-details {
            flex: 1;
            min-width: 300px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--text-dark);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }



        /* Estilos para el modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            overflow: auto;
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 8px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
        }

        .close-modal:hover {
            color: var(--text-dark);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }

            .profile-container {
                padding: 20px;
            }

            .profile-image,
            .profile-details {
                flex: 0 0 100%;
            }

            .profile-image {
                margin-bottom: 20px;
            }
        }

        .alerta-modal {
            position: fixed;
            top: 30px;
            left: 50%;
            transform: translateX(-50%) scale(0.95);
            background: #fff;
            color: #222;
            border-radius: 8px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.18);
            padding: 18px 32px;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s, transform 0.3s;
            font-size: 1.1rem;
            min-width: 220px;
            text-align: center;
        }
        .alerta-modal.visible {
            opacity: 1;
            pointer-events: auto;
            transform: translateX(-50%) scale(1);
        }
        .alerta-modal.success {
            border-left: 6px solid #4caf50;
        }
        .alerta-modal.error {
            border-left: 6px solid #e53935;
        }
    </style>
</head>

<body>
    <!-- Sidebar incluido desde el archivo externo -->
    <?php
    include("../Vista/Sidebar.php");
    ?>

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
            <span class="close-modal" onclick="cerrarModal()">&times;</span>
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
    </div>

    <script src="../js/perfil.js"></script>
</body>

</html>