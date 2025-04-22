<?php
session_start();
include("../database/Conexion.php");

// Incluye el archivo que contiene la lógica para el control de roles.
// Este archivo define funciones como `checkRole` para verificar si el usuario tiene permisos para acceder a esta página.
include("../Controlador/control_De_Rol.php");

// Llama a la función `checkRole` para verificar si el usuario tiene el rol de 'Docente'.
// Si el usuario no tiene el rol requerido, la función redirige o bloquea el acceso a esta página.
checkRole('Docente');

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

// Obtener fecha actual para establecer mínimo en el campo de fecha
$fechaActual = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Reserva</title>
    <link rel="stylesheet" href="../css/Style.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
</head>

<body class="Registro">

<!-- SIDEBAR -->
<?php include("../Vista/Sidebar_Docente.html"); ?>

<section class="Panel_formulario">
    <img src="Imagen/Logo_Universidad1.png" alt="">
    <section>
        <form id="reservaForm" action="../Controlador/guardar_reserva.php" method="POST" onsubmit="return validarFormulario()">

            <label for="fecha">Fecha:</label><br>
            <input type="date" id="fecha" name="fecha" min="<?php echo $fechaActual; ?>" required>

            <label for="horaInicio">Hora de Inicio:</label>
            <input type="time" id="horaInicio" name="horaInicio" min="07:00" max="21:00" required>

            <label for="horaFin">Hora Final:</label>
            <input type="time" id="horaFin" name="horaFin" min="07:00" max="21:00" required>

            <label for="tipo">Recurso:</label>
            <select id="tipo" name="recurso" required>
                <option value="">Seleccione un recurso</option>
                <?php foreach ($recursos as $recurso): ?>
                    <option value="<?= $recurso['ID_Recurso'] ?>"><?= $recurso['nombreRecurso'] ?></option>
                <?php endforeach; ?>
            </select>

            <label for="asignatura">Asignatura:</label><br>
            <select id="asignatura" name="docente_asignatura" required>
                <option value="">Seleccione una asignatura</option>
                <?php foreach ($asignaturas as $asig): ?>
                    <option value="<?= $asig['ID_DocenteAsignatura'] ?>"><?= $asig['nombreAsignatura'] ?></option>
                <?php endforeach; ?>
            </select>

            <div id="mensajeError" class="error-mensaje" style="color: red; margin-top: 10px; display: none;"></div>

            <button type="submit">Reservar</button>
        </form>
    </section>
</section>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const fechaInput = document.getElementById("fecha");
    const horaInicioInput = document.getElementById("horaInicio");
    const horaFinInput = document.getElementById("horaFin");
    const recursoSelect = document.getElementById("tipo");
    const mensajeError = document.getElementById("mensajeError");

    // Validar tiempo mínimo de reserva (30 minutos)
    const validarDuracionMinima = () => {
        if (horaInicioInput.value && horaFinInput.value) {
            const inicio = new Date(`2000-01-01T${horaInicioInput.value}`);
            const fin = new Date(`2000-01-01T${horaFinInput.value}`);
            
            // Si la hora de fin es menor que la de inicio, mostrar error
            if (fin <= inicio) {
                mensajeError.textContent = "La hora de finalización debe ser posterior a la hora de inicio.";
                mensajeError.style.display = "block";
                return false;
            }
            
            // Calcular diferencia en minutos
            const diffMinutos = (fin - inicio) / (1000 * 60);
            
            if (diffMinutos < 30) {
                mensajeError.textContent = "La reserva debe durar al menos 30 minutos.";
                mensajeError.style.display = "block";
                return false;
            }
            
            if (diffMinutos > 180) {
                mensajeError.textContent = "La reserva no puede exceder las 3 horas.";
                mensajeError.style.display = "block";
                return false;
            }
            
            mensajeError.style.display = "none";
            return true;
        }
        return true; // Si no se han completado ambos campos, no validamos aún
    };

    // Validar que la fecha no sea fin de semana
    const validarFinDeSemana = () => {
        if (fechaInput.value) {
            const fecha = new Date(fechaInput.value);
            const diaSemana = fecha.getDay(); // 0 es domingo, 6 es sábado
            
            if (diaSemana === 0 || diaSemana === 6) {
                mensajeError.textContent = "No se permiten reservas en fin de semana.";
                mensajeError.style.display = "block";
                return false;
            }
            
            mensajeError.style.display = "none";
            return true;
        }
        return true;
    };

    // Función de validación completa del formulario
    window.validarFormulario = () => {
        const validarDuracion = validarDuracionMinima();
        const validarFecha = validarFinDeSemana();
        
        if (!validarDuracion || !validarFecha) {
            return false;
        }
        
        // Si todas las validaciones pasan, permitir el envío
        return true;
    };

    // Validar al cambiar los campos
    horaInicioInput.addEventListener("change", validarDuracionMinima);
    horaFinInput.addEventListener("change", validarDuracionMinima);
    fechaInput.addEventListener("change", validarFinDeSemana);

    // Función para verificar disponibilidad
    const verificarDisponibilidad = async () => {
        const fecha = fechaInput.value;
        const horaInicio = horaInicioInput.value;
        const horaFin = horaFinInput.value;
        const recurso = recursoSelect.value;

        // Limpiar mensajes de error previos
        mensajeError.style.display = "none";

        // Validar horas antes de consultar disponibilidad
        if (!validarDuracionMinima() || !validarFinDeSemana()) {
            return;
        }

        if (fecha && horaInicio && horaFin && recurso) {
            const formData = new FormData();
            formData.append("fecha", fecha);
            formData.append("horaInicio", horaInicio);
            formData.append("horaFin", horaFin);
            formData.append("recurso", recurso);

            try {
                const response = await fetch("../Controlador/Obtener_Recurso.php", {
                    method: "POST",
                    body: formData
                });

                const data = await response.json();

                if (data.disponible === false) {
                    Swal.fire({
                        icon: "warning",
                        title: "❌ Recurso no disponible",
                        text: "Ya hay una reserva en ese horario.",
                        confirmButtonText: "Entendido"
                    });
                } else if (data.disponible === true) {
                    Swal.fire({
                        icon: "success",
                        title: "✅ Recurso disponible",
                        text: "Puedes continuar con la reserva.",
                        timer: 1800,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: data.error || "Algo salió mal consultando la disponibilidad."
                    });
                }
            } catch (error) {
                console.error("Error al verificar disponibilidad:", error);
                Swal.fire({
                    icon: "error",
                    title: "Error de conexión",
                    text: "No se pudo verificar la disponibilidad. Intente nuevamente."
                });
            }
        }
    };

    // Verificar disponibilidad cuando cambien los campos relevantes
    fechaInput.addEventListener("change", verificarDisponibilidad);
    horaInicioInput.addEventListener("change", verificarDisponibilidad);
    horaFinInput.addEventListener("change", verificarDisponibilidad);
    recursoSelect.addEventListener("change", verificarDisponibilidad);
});
</script>

</body>
</html>