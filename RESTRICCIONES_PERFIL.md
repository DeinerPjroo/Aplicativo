# Restricciones de Perfil por Rol

## Descripción
Este documento describe las restricciones implementadas en el sistema de perfiles de usuario según el rol del usuario autenticado.

## Funcionamiento

### 1. Roles y Permisos

#### **Administrador**
- ✅ **Puede modificar**: Nombre, Correo, Programa, Teléfono
- ✅ **Sin restricciones**: Acceso completo a todos los campos del perfil
- ✅ **Validaciones**: Correo válido, nombre no vacío, verificación de correo único

#### **Docente, Estudiante, Administrativo**
- ❌ **NO puede modificar**: Nombre, Correo, Programa (campos readonly)
- ✅ **SÍ puede modificar**: Solo el campo Teléfono
- ℹ️ **Mensaje informativo**: Se muestra que solo pueden modificar el teléfono

### 2. Implementación Técnica

#### **Frontend** (`Vista/Perfil.php`)
```php
// Campos restringidos para no administradores
<input type="text" name="nombreUsuario" 
       value="<?php echo htmlspecialchars($nombreUsuario); ?>" 
       <?php echo ($role !== 'Administrador') ? 'readonly' : ''; ?>>

<input type="email" name="correoUsuario" 
       value="<?php echo htmlspecialchars($correoUsuario); ?>" 
       <?php echo ($role !== 'Administrador') ? 'readonly' : 'required'; ?>>

<input type="text" name="programaUsuario" 
       value="<?php echo htmlspecialchars($programa); ?>" 
       <?php echo ($role !== 'Administrador') ? 'readonly' : ''; ?>>

// Campo siempre editable
<input type="text" name="telefonoUsuario" 
       value="<?php echo htmlspecialchars($telefonoUsuario); ?>">
```

#### **Backend** (`Controlador/ControladorPerfil.php`)
```php
if ($rol_usuario === 'Administrador') {
    // Administradores pueden actualizar todos los campos
    $sql = "UPDATE usuario SET nombre = ?, correo = ?, programa = ?, telefono = ?";
} else {
    // Otros roles solo pueden actualizar el teléfono
    $sql = "UPDATE usuario SET telefono = ?";
    // Se mantienen los valores actuales de nombre, correo y programa
}
```

### 3. Seguridad

#### **Validación Doble**
1. **Frontend**: Campos readonly previenen edición accidental
2. **Backend**: Validación del rol antes de procesar cambios

#### **Protección de Datos**
- Los campos restringidos mantienen sus valores originales
- Solo se actualiza el teléfono para usuarios no administradores
- Los administradores requieren validaciones adicionales (correo único, formato válido)

### 4. Experiencia de Usuario

#### **Mensajes Informativos**
- **No administradores**: "Solo los administradores pueden modificar este campo"
- **Administradores**: "Campo editable"
- **Teléfono**: "Este es el único campo que puedes modificar" / "Campo editable"

#### **Botón Dinámico**
- **No administradores**: "Actualizar Teléfono"
- **Administradores**: "Guardar cambios"

## Archivos Modificados

1. **Vista/Perfil.php** - Interfaz con campos condicionalmente readonly
2. **Controlador/ControladorPerfil.php** - Lógica de actualización por roles

## Casos de Uso

### Escenario 1: Usuario Estudiante
1. Accede a su perfil
2. Ve nombre, correo y programa como readonly
3. Solo puede modificar el campo teléfono
4. Al guardar, solo se actualiza el teléfono

### Escenario 2: Usuario Administrador
1. Accede a su perfil
2. Puede editar todos los campos
3. Se valida formato de correo y unicidad
4. Al guardar, se actualizan todos los campos modificados

## Fecha de Implementación
Implementado como parte del sistema de gestión de usuarios.

## Nota de Seguridad
Esta implementación asegura que los usuarios no puedan modificar información crítica como nombre, correo o programa sin los permisos adecuados, manteniendo la integridad de los datos del sistema.
