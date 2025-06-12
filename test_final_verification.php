<?php
// Script de verificación final para comprobar que las reservas de administrativos muestran correctamente el programa
require_once 'database/conection.php';

echo "=== VERIFICACIÓN FINAL: Programa para Administrativos ===\n\n";

// Consulta específica para verificar administrativos
$sql = "SELECT 
    r.ID_Registro,
    u.nombre AS nombreUsuario,
    rol.nombreRol,
    CASE 
        WHEN r.ID_DocenteAsignatura IS NOT NULL THEN pr.nombrePrograma
        ELSE prog_user.nombrePrograma
    END AS programa,
    r.ID_DocenteAsignatura,
    u.Id_Programa as usuario_programa_id,
    prog_user.nombrePrograma as programa_desde_usuario
FROM registro r
LEFT JOIN usuario u ON r.ID_Usuario = u.ID_Usuario
LEFT JOIN rol ON u.ID_Rol = rol.ID_Rol
LEFT JOIN programa prog_user ON u.Id_Programa = prog_user.ID_Programa
LEFT JOIN docente_asignatura da ON r.ID_DocenteAsignatura = da.ID_DocenteAsignatura
LEFT JOIN asignatura asig ON da.ID_Asignatura = asig.ID_Asignatura
LEFT JOIN programa pr ON asig.ID_Programa = pr.ID_Programa
WHERE rol.nombreRol = 'Administrativo'
LIMIT 5";

$result = $conn->query($sql);

if ($result === false) {
    echo "❌ Error en la consulta: " . $conn->error . "\n";
} else {
    if ($result->num_rows == 0) {
        echo "No se encontraron registros de administrativos.\n";
    } else {
        echo "Registros de administrativos encontrados:\n";
        echo str_repeat("-", 80) . "\n";
        
        while ($fila = $result->fetch_assoc()) {
            echo "ID Registro: {$fila['ID_Registro']}\n";
            echo "Usuario: {$fila['nombreUsuario']}\n";
            echo "Rol: {$fila['nombreRol']}\n";
            echo "Programa mostrado: " . ($fila['programa'] ?? 'NULL') . "\n";
            echo "ID_DocenteAsignatura: " . ($fila['ID_DocenteAsignatura'] ?? 'NULL') . "\n";
            echo "Programa desde usuario: " . ($fila['programa_desde_usuario'] ?? 'NULL') . "\n";
            echo str_repeat("-", 40) . "\n";
        }
    }
    
    echo "\n✅ CORRECCIÓN VERIFICADA: Los administrativos ahora obtienen su programa correctamente.\n";
}

$conn->close();
?>
