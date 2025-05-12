<?php
$host = "localhost";
$user = "root"; // o el usuario que tengas configurado
$pass = "123456";     // si tienes contraseña, escríbela aquí
$db = "SReservasI";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
// echo "Conexión exitosa"; // solo para pruebas


?>
