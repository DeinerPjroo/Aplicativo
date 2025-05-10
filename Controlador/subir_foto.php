<?php
session_start();
include("../database/conexion.php");

$id_usuario = $_POST['id_usuario'];

// Consulta para obtener la foto actual del usuario
$sql = "SELECT fotoPerfil FROM usuario WHERE ID_Usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $fotoActual = $row['fotoPerfil'];

    // Eliminar la foto anterior si no es la predeterminada
    if (!empty($fotoActual) && $fotoActual !== 'Imagen/default.jpg' && file_exists("../" . $fotoActual)) {
        unlink("../" . $fotoActual); // Elimina el archivo
    }
}

// Procesar la nueva foto
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $directorio = "../Imagen/Perfiles/";
    $nombreArchivo = uniqid() . "_" . basename($_FILES['foto']['name']);
    $rutaArchivo = $directorio . $nombreArchivo;

    // Mover el archivo subido al directorio de destino
    if (move_uploaded_file($_FILES['foto']['tmp_name'], $rutaArchivo)) {
        // Actualizar la base de datos con la nueva ruta de la foto
        $sql = "UPDATE usuario SET fotoPerfil = ? WHERE ID_Usuario = ?";
        $stmt = $conn->prepare($sql);
        $rutaRelativa = "Imagen/Perfiles/" . $nombreArchivo; // Ruta relativa para guardar en la base de datos
        $stmt->bind_param("si", $rutaRelativa, $id_usuario);
        $stmt->execute();

        // Redirigir al perfil después de subir la foto
        header("Location: ../Vista/Perfil.php");
        exit();
    } else {
        echo "Error al subir la foto.";
    }
} else {
    echo "No se seleccionó ninguna foto o hubo un error al subirla.";
}
?>