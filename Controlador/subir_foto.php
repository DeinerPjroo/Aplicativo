<?php
$conexion = new mysqli("localhost", "root", "", "sreservasi");
include("../database/conexion.php");

$id_usuario = $_POST['id_usuario'];
$carpeta_destino = "../Imagen/perfiles/"; // Asegúrate de que la ruta termina con una barra

// Verifica si la carpeta de destino existe, si no, la crea
if (!is_dir($carpeta_destino)) {
    mkdir($carpeta_destino, 0777, true); // Crea la carpeta con permisos de escritura
}

// Verifica si se subió una imagen
if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
    $nombre_archivo = basename($_FILES["foto"]["name"]);
    $ruta_archivo = $carpeta_destino . time() . "_" . $nombre_archivo; // Agrega un prefijo único para evitar conflictos

    // Mueve el archivo al destino
    if (move_uploaded_file($_FILES["foto"]["tmp_name"], $ruta_archivo)) {
        // Guarda la ruta relativa en la base de datos
        $ruta_relativa = str_replace("../", "", $ruta_archivo); // Elimina "../" para guardar la ruta relativa
        $stmt = $conexion->prepare("UPDATE usuario SET fotoPerfil = ? WHERE ID_Usuario = ?");
        $stmt->bind_param("si", $ruta_relativa, $id_usuario);
        $stmt->execute();
        echo "Foto subida correctamente.";
    } else {
        echo "Error al mover la imagen.";
    }
} else {
    echo "No se subió ninguna imagen.";
}
?>
