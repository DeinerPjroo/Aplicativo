<?php
include 'database/conection.php';

echo "=== PRUEBA DE RESTRICCIONES PARA ESTUDIANTES ===\n";

// Verificar roles
echo "\n1. ROLES DISPONIBLES:\n";
$roles = $conn->query('SELECT * FROM rol ORDER BY ID_Rol');
while($row = $roles->fetch_assoc()) {
    echo "   ID: {$row['ID_Rol']} - Nombre: {$row['nombreRol']}\n";
}

// Verificar recursos
echo "\n2. RECURSOS DISPONIBLES:\n";
$recursos = $conn->query('SELECT * FROM recursos ORDER BY ID_Recurso');
while($row = $recursos->fetch_assoc()) {
    echo "   ID: {$row['ID_Recurso']} - Nombre: {$row['nombreRecurso']}\n";
}

// Contar usuarios estudiantes
echo "\n3. USUARIOS ESTUDIANTES:\n";
$estudiantes = $conn->query('SELECT u.*, p.nombrePrograma FROM usuario u LEFT JOIN programa p ON u.Id_Programa = p.ID_Programa WHERE u.ID_Rol = 1 LIMIT 5');
while($row = $estudiantes->fetch_assoc()) {
    echo "   ID: {$row['ID_Usuario']} - Nombre: {$row['nombre']} - Programa: " . ($row['nombrePrograma'] ?? 'Sin programa') . "\n";
}

// Simular filtrado de recursos para estudiantes
echo "\n4. FILTRADO DE RECURSOS PARA ESTUDIANTES:\n";
echo "Recursos que los estudiantes pueden reservar (contienen 'videobeam'):\n";
$recursosVideobeam = $conn->query("SELECT * FROM recursos WHERE nombreRecurso LIKE '%videobeam%' OR nombreRecurso LIKE '%Videobeam%'");
if($recursosVideobeam->num_rows > 0) {
    while($row = $recursosVideobeam->fetch_assoc()) {
        echo "   ✓ ID: {$row['ID_Recurso']} - Nombre: {$row['nombreRecurso']}\n";
    }
} else {
    echo "   ❌ No se encontraron videobeams\n";
}

echo "\nRecursos que los estudiantes NO pueden reservar (no contienen 'videobeam'):\n";
$recursosNoVideobeam = $conn->query("SELECT * FROM recursos WHERE nombreRecurso NOT LIKE '%videobeam%' AND nombreRecurso NOT LIKE '%Videobeam%'");
if($recursosNoVideobeam->num_rows > 0) {
    while($row = $recursosNoVideobeam->fetch_assoc()) {
        echo "   ❌ ID: {$row['ID_Recurso']} - Nombre: {$row['nombreRecurso']}\n";
    }
} else {
    echo "   ✓ Todos los recursos son videobeams\n";
}

echo "\n=== PRUEBA COMPLETADA ===\n";
?>
