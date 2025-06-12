<?php
include 'database/conection.php';

echo "=== ROLES ===\n";
$roles = $conn->query('SELECT * FROM rol ORDER BY ID_Rol');
while($row = $roles->fetch_assoc()) {
    echo "ID: " . $row['ID_Rol'] . " - Nombre: " . $row['nombreRol'] . "\n";
}

echo "\n=== RECURSOS (primeros 10) ===\n";
$recursos = $conn->query('SELECT * FROM recursos LIMIT 10');
while($row = $recursos->fetch_assoc()) {
    echo "ID: " . $row['ID_Recurso'] . " - Nombre: " . $row['nombreRecurso'] . "\n";
}
?>
