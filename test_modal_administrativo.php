<?php
// Script de prueba para verificar que el modal de modificar carga correctamente el docente para administrativos
require_once 'database/conection.php';

echo "=== PRUEBA: Modal de Modificar - Campo Docente para Administrativos ===\n\n";

// Consulta para obtener un registro de administrativo
$sql = "SELECT 
    r.ID_Registro,
    u.nombre AS nombreUsuario,
    u.ID_Usuario as id_usuario,
    u.ID_Rol,
    rol.nombreRol,
    CASE 
        WHEN r.ID_DocenteAsignatura IS NOT NULL THEN pr.nombrePrograma
        ELSE prog_user.nombrePrograma
    END AS programa,
    CASE 
        WHEN r.ID_DocenteAsignatura IS NOT NULL THEN pr.ID_Programa
        ELSE prog_user.ID_Programa
    END AS ID_Programa,
    doc.ID_Usuario AS id_docente,
    doc.nombre AS nombreDocente
FROM registro r
LEFT JOIN usuario u ON r.ID_Usuario = u.ID_Usuario
LEFT JOIN rol ON u.ID_Rol = rol.ID_Rol
LEFT JOIN programa prog_user ON u.Id_Programa = prog_user.ID_Programa
LEFT JOIN docente_asignatura da ON r.ID_DocenteAsignatura = da.ID_DocenteAsignatura
LEFT JOIN usuario doc ON da.ID_Usuario = doc.ID_Usuario
LEFT JOIN asignatura asig ON da.ID_Asignatura = asig.ID_Asignatura
LEFT JOIN programa pr ON asig.ID_Programa = pr.ID_Programa
WHERE rol.nombreRol = 'Administrativo'
LIMIT 3";

$result = $conn->query($sql);

if ($result === false) {
    echo "❌ Error en la consulta: " . $conn->error . "\n";
} else {
    if ($result->num_rows == 0) {
        echo "No se encontraron registros de administrativos.\n";
    } else {
        echo "Análisis de registros de administrativos:\n";
        echo str_repeat("-", 80) . "\n";
        
        while ($fila = $result->fetch_assoc()) {
            echo "ID Registro: {$fila['ID_Registro']}\n";
            echo "Usuario: {$fila['nombreUsuario']} (ID: {$fila['id_usuario']})\n";
            echo "Rol: {$fila['nombreRol']} (ID: {$fila['ID_Rol']})\n";
            echo "Programa: {$fila['programa']}\n";
            echo "ID_Docente original: " . ($fila['id_docente'] ?? 'NULL') . "\n";
            echo "Nombre Docente original: " . ($fila['nombreDocente'] ?? 'NULL') . "\n";
            
            // Simular la lógica del modal corregido
            $id_docente_correcto = ($fila['ID_Rol'] == 3) ? $fila['id_usuario'] : $fila['id_docente'];
            echo "ID_Docente CORREGIDO: {$id_docente_correcto}\n";
            echo "✅ RESULTADO: " . ($id_docente_correcto ? "El administrativo será auto-seleccionado" : "❌ Problema persistente") . "\n";
            echo str_repeat("-", 40) . "\n";
        }
    }
    
    echo "\n🔧 EXPLICACIÓN DE LA CORRECCIÓN:\n";
    echo "- Para administrativos (ID_Rol = 3): id_docente = id_usuario\n";
    echo "- Para otros roles: id_docente = valor original\n";
    echo "- Esto permite que el administrativo se auto-seleccione en el campo 'Docente/Administrativo'\n";
}

$conn->close();
?>
