<?php
// Incluye el archivo de conexión a la base de datos
include("../database/conection.php");

// Asegúrate de que los errores no se muestren en la respuesta JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Establece el encabezado para indicar que la respuesta es JSON
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota'); // Establece la zona horaria a Bogotá, Colombia.

try {
    // Verifica si la solicitud es de tipo POST
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Obtiene los parámetros enviados por POST o asigna un valor vacío si no están definidos
        $fecha = $_POST['fecha'] ?? '';
        $horaInicio = $_POST['horaInicio'] ?? '';
        $horaFin = $_POST['horaFin'] ?? '';
        $id_recurso = $_POST['recurso'] ?? '';

        // Verifica que todos los parámetros requeridos estén presentes
        if ($fecha && $horaInicio && $horaFin && $id_recurso) {
            // Consulta SQL para verificar si existe un cruce de horarios en las reservas
            $sql = "SELECT * FROM registro 
                    WHERE ID_Recurso = ?
                    AND fechaReserva = ?
                    AND (
                        (horaInicio < ? AND horaFin > ?) OR
                        (horaInicio < ? AND horaFin > ?) OR
                        (horaInicio >= ? AND horaFin <= ?) OR
                        (? BETWEEN horaInicio AND horaFin) OR
                        (? BETWEEN horaInicio AND horaFin)
                    )
                    AND estado != 'Cancelada'";

            // Prepara la consulta SQL
            $stmt = $conn->prepare($sql);
            
            // CORRECCIÓN: El número de parámetros en bind_param debe coincidir con los marcadores de posición (?)
            $stmt->bind_param("isssssssss", 
                $id_recurso, 
                $fecha,
                $horaFin, $horaInicio,
                $horaInicio, $horaFin,
                $horaInicio, $horaFin,
                $horaInicio, $horaFin
            );
            
            // Ejecuta la consulta
            $stmt->execute();
            // Obtiene el resultado de la consulta
            $result = $stmt->get_result();

            // Devuelve un JSON indicando si el recurso está disponible
            echo json_encode([
                "disponible" => ($result->num_rows === 0)  // Está disponible si no hay resultados
            ]);
        } else {
            // Devuelve un JSON indicando que faltan parámetros
            echo json_encode([
                "error" => "Faltan parámetros"
            ]);
        }
    } else {
        echo json_encode([
            "error" => "Se requiere una solicitud POST"
        ]);
    }
} catch (Exception $e) {
    // Captura cualquier excepción y devuelve un mensaje de error en formato JSON
    echo json_encode([
        "error" => "Error en el servidor: " . $e->getMessage()
    ]);
}
?>