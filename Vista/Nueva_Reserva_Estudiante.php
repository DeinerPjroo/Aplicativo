<?php
session_start();
include("../database/Conexion.php");

// Incluye el archivo que contiene la lógica para el control de roles.
// Este archivo define funciones como `checkRole` para verificar si el usuario tiene permisos para acceder a esta página.
include("../Controlador/control_De_Rol.php");

// Llama a la función `checkRole` para verificar si el usuario tiene el rol de 'Administrador'.
// Si el usuario no tiene el rol requerido, la función redirige o bloquea el acceso a esta página.
checkRole('Estudiante');

$id_usuario = $_SESSION['usuario_id'] ?? null;

if (!$id_usuario) {
    header("Location: Login.php");
    exit();
}

// Obtener asignaturas del docente
$asignaturas = [];
$sql = "SELECT da.ID_DocenteAsignatura, a.nombreAsignatura 
        FROM docente_asignatura da
        JOIN asignatura a ON da.ID_Asignatura = a.ID_Asignatura
        WHERE da.ID_Usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $asignaturas[] = $row;
}

// Obtener recursos
$recursos = [];
$sql = "SELECT ID_Recurso, nombreRecurso FROM recursos";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $recursos[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="../css/Style.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>

<body class="Registro">

<!-- SIDEBAR -->
<?php include("../Vista/Sidebar_Estudiante.html"); ?>

<section class="Panel_formulario">
    <img src="Imagen/Logo_Universidad1.png" alt="">
    <section>
        <form id="reservaform" action="guardar_reserva.php" method="POST">  

            <label for="fecha">Fecha:</label><br>
            <input type="date" id="fecha" name="fecha" required>

            <label for="horaInicio">Hora de Inicio:</label>
            <input type="time" id="horaInicio" name="horaInicio" required>

            <label for="horaFin">Hora Final:</label>
            <input type="time" id="horaFin" name="horaFin" required>

            <label for="tipo">Recurso:</label>
            <select id="tipo" name="recurso" required>
                <option value="">Seleccione un recurso</option>
                <?php foreach ($recursos as $recurso): ?>
                    <option value="<?= $recurso['ID_Recurso'] ?>"><?= $recurso['nombreRecurso'] ?></option>
                <?php endforeach; ?>
            </select>

            <label for="tipo">Docente:</label>
            <select id="tipo" name="Docente" required>
                <option value="">Seleccione un Docente</option>
                <?php foreach ($Docente as $Docente): ?>
                    <option value="<?= $docente_asignatura['ID_DocenteAsignatura'] ?>"><?= $docente_asignatura['nombreRecurso'] ?></option>
                <?php endforeach; ?>
            </select>

            <label for="asignatura">Asignatura:</label><br>
            <select id="asignatura" name="docente_asignatura" required>
                <option value="">Seleccione una asignatura</option>
                <?php foreach ($asignaturas as $asig): ?>
                    <option value="<?= $asig['ID_DocenteAsignatura'] ?>"><?= $asig['nombreAsignatura'] ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Reservar</button>
        </form>
    </section>
</section>

</body>
</html>
