<?php
include("../database/conection.php");

// para actualizar la tabla de usuarios sin necesidad de recargar la página

$query = "SELECT u.ID_Usuario, u.codigo_u, u.nombre, u.telefono, p.nombrePrograma AS programa, u.Id_Programa, u.semestre, u.correo, r.nombreRol AS rol, u.id_rol
             FROM usuario u
             LEFT JOIN programa p ON u.Id_Programa = p.ID_Programa
             LEFT JOIN rol r ON u.id_rol = r.id_rol";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) : ?>
<tr>
    <td><?php echo htmlspecialchars($row['codigo_u']); ?></td>
    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
    <td><?php echo htmlspecialchars($row['telefono']); ?></td>
    <td><?php echo !empty($row['programa']) ? htmlspecialchars($row['programa']) : 'No aplica'; ?></td>
    <td><?php echo htmlspecialchars($row['semestre'] ?? 'N/A'); ?></td>
    <td><?php echo htmlspecialchars($row['correo']); ?></td>
    <td><?php echo htmlspecialchars($row['rol']); ?></td>
    <td>
        <button class="btn btn-modificar"
            onclick="openModificarForm(
            <?php echo $row['ID_Usuario']; ?>,
            '<?php echo htmlspecialchars($row['codigo_u'], ENT_QUOTES); ?>',
            '<?php echo htmlspecialchars($row['nombre'], ENT_QUOTES); ?>',
            '<?php echo htmlspecialchars($row['telefono'], ENT_QUOTES); ?>',
            '<?php echo htmlspecialchars($row['correo'], ENT_QUOTES); ?>',
            '<?php echo htmlspecialchars($row['Id_Programa'], ENT_QUOTES); ?>',
            '<?php echo htmlspecialchars($row['semestre'], ENT_QUOTES); ?>',
            <?php echo $row['id_rol']; ?>
        )">
            Modificar
        </button>
        <button class="btn btn-eliminar" onclick="eliminarUsuario(<?php echo $row['ID_Usuario']; ?>)">Eliminar</button>
    </td>
</tr>
<?php endwhile;
$conn->close();
?>