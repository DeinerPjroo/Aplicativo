<?php
include("../database/conexion.php");

$codigo_u = $_POST['codigo_u'];
$nombre = $_POST['nombre'];
$correo = $_POST['correo'];
$contraseña = password_hash($_POST['contraseña'], PASSWORD_BCRYPT);
$id_rol = $_POST['id_rol'];
$semestre = $_POST['semestre'];
$id_programa = $_POST['id_programa'];

// Verificar si el Código_U ya existe
$checkQuery = "SELECT * FROM usuario WHERE Codigo_U = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("s", $codigo_u);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<script>
        alert('El código de usuario ya existe. Por favor, use un código diferente.');
        window.history.back();
    </script>";
    exit;
}

// Insertar nuevo usuario
$query = "INSERT INTO usuario (Codigo_U, nombre, correo, contraseña, Id_Rol, semestre, Id_Programa) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssssiii", $codigo_u, $nombre, $correo, $contraseña, $id_rol, $semestre, $id_programa);

if ($stmt->execute()) {
    echo "<script>
        alert('Usuario agregado exitosamente.');
        window.location.href = '../Vista/Administrar_Usuarios.php';
    </script>";
} else {
    echo "<script>
        alert('Error al agregar el usuario.');
        window.history.back();
    </script>";
}

$stmt->close();
$conn->close();
?>
