<?php
// Test query para verificar que la consulta funciona correctamente
include("database/conection.php");

echo "=== PRUEBA DE CONSULTA SQL CORREGIDA PARA PROGRAMAS ===\n\n";

$sql = "SELECT
    r.ID_Registro,
    r.fechaReserva,
    u.nombre AS nombreUsuario,
    u.ID_Rol,
    COALESCE(asig.nombreAsignatura, 'Sin asignatura') AS asignatura,
    CASE 
        WHEN r.ID_DocenteAsignatura IS NOT NULL THEN pr.nombrePrograma
        ELSE prog_user.nombrePrograma
    END AS programa,
    CASE 
        WHEN r.ID_DocenteAsignatura IS NOT NULL THEN pr.ID_Programa
        ELSE prog_user.ID_Programa
    END AS ID_Programa
FROM registro r
LEFT JOIN usuario u ON r.ID_Usuario = u.ID_Usuario
LEFT JOIN programa prog_user ON u.Id_Programa = prog_user.ID_Programa
LEFT JOIN recursos rc ON r.ID_Recurso = rc.ID_Recurso
LEFT JOIN docente_asignatura da ON r.ID_DocenteAsignatura = da.ID_DocenteAsignatura
LEFT JOIN usuario doc ON da.ID_Usuario = doc.ID_Usuario
LEFT JOIN asignatura asig ON da.ID_Asignatura = asig.ID_Asignatura
LEFT JOIN programa pr ON asig.ID_Programa = pr.ID_Programa
ORDER BY r.fechaReserva DESC
LIMIT 5";

try {
    $result = $conn->query($sql);
    
    if ($result) {
        echo "✅ CONSULTA EJECUTADA EXITOSAMENTE\n\n";
        echo "Resultados (primeros 5 registros):\n";
        echo str_repeat("-", 80) . "\n";
        
        while ($row = $result->fetch_assoc()) {
            echo "ID: {$row['ID_Registro']}\n";
            echo "Usuario: {$row['nombreUsuario']} (Rol ID: {$row['ID_Rol']})\n";
            echo "Asignatura: {$row['asignatura']}\n";
            echo "Programa: " . ($row['programa'] ?? 'NULL') . " (ID: " . ($row['ID_Programa'] ?? 'NULL') . ")\n";
            echo "Fecha: {$row['fechaReserva']}\n";
            echo str_repeat("-", 40) . "\n";
        }
    } else {
        echo "❌ ERROR EN LA CONSULTA: " . $conn->error . "\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
}

$conn->close();
echo "\n=== FIN DE LA PRUEBA ===\n";
?>
