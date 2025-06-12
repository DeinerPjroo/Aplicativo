# 🚫 Restricciones de Recursos para Estudiantes

## 📋 Resumen de Cambios

Se ha implementado una restricción para que los **estudiantes solo puedan reservar videobeams** y no puedan acceder a salas de reuniones/aulas.

---

## ✅ Características Implementadas

### 🎯 Restricción de Frontend
- **Archivo**: `Vista/Reservas_Usuarios.php`
- **Funcionalidad**: Filtrado automático de recursos según el rol del usuario
- **Comportamiento**:
  - **Estudiantes**: Solo ven videobeams en el dropdown de recursos
  - **Otros roles**: Ven todos los recursos disponibles
- **Indicador visual**: Nota informativa para estudiantes

### 🔒 Validación de Backend
- **Archivo**: `Controlador/ControladorRegistro.php`
- **Funcionalidad**: Validación server-side para prevenir bypassing
- **Casos cubiertos**:
  - ✅ Creación de nuevas reservas (`case 'agregar'`)
  - ✅ Modificación de reservas existentes (`case 'modificar'`)
- **Mensaje de error**: "Los estudiantes solo pueden reservar videobeams. Recurso seleccionado: [nombre]"

---

## 📊 Configuración Actual

### Roles en el Sistema
| ID | Rol | Restricción |
|----|-----|-------------|
| 1 | Estudiante | ❌ Solo videobeams |
| 2 | Docente | ✅ Todos los recursos |
| 3 | Administrativo | ✅ Todos los recursos |
| 4 | Administrador | ✅ Todos los recursos |

### Recursos Disponibles
| ID | Recurso | Estudiantes | Otros Roles |
|----|---------|-------------|-------------|
| 1-5 | Salas 1-5 | ❌ Prohibido | ✅ Permitido |
| 6 | Videobeam | ✅ Permitido | ✅ Permitido |

---

## 🔧 Detalles Técnicos

### Lógica de Filtrado (Frontend)
```php
if ($role === 'Estudiante') {
    // Solo recursos que contengan 'videobeam'
    $recursos = $conn->query("SELECT ID_Recurso, nombreRecurso FROM recursos 
                             WHERE nombreRecurso LIKE '%videobeam%' 
                             OR nombreRecurso LIKE '%Videobeam%'");
} else {
    // Todos los recursos
    $recursos = $conn->query("SELECT ID_Recurso, nombreRecurso FROM recursos");
}
```

### Validación de Seguridad (Backend)
```php
if ($usuarioLogueado['nombreRol'] === 'Estudiante') {
    // Verificar que el recurso seleccionado sea un videobeam
    if (stripos($nombreRecurso, 'videobeam') === false) {
        throw new Exception('Los estudiantes solo pueden reservar videobeams...');
    }
}
```

---

## 🧪 Testing

### Casos de Prueba
1. **✅ Estudiante intenta reservar videobeam**: Permitido
2. **❌ Estudiante intenta reservar sala**: Bloqueado (frontend + backend)
3. **✅ Docente reserva cualquier recurso**: Permitido
4. **✅ Administrativo reserva cualquier recurso**: Permitido

### Validación Manual
- Verificar dropdown de recursos como estudiante
- Intentar bypass con herramientas de desarrollador
- Confirmar mensaje de error en validación backend

---

## 🚀 Casos de Uso

### Escenario 1: Estudiante Normal
1. Estudiante inicia sesión
2. Va a "Mis Reservas" > "Crear Nueva Reserva"
3. Solo ve "Videobeam" en lista de recursos
4. Puede completar reserva normalmente

### Escenario 2: Intento de Bypass
1. Estudiante modifica HTML del frontend
2. Intenta enviar reserva de sala
3. Backend rechaza la solicitud
4. Recibe mensaje de error específico

### Escenario 3: Otros Roles
1. Docente/Administrativo inicia sesión
2. Ve todos los recursos disponibles
3. Puede reservar cualquier recurso sin restricciones

---

## 🛡️ Seguridad

### Medidas Implementadas
- **Doble validación**: Frontend (UX) + Backend (Seguridad)
- **Validación en creación Y modificación**
- **Mensajes de error informativos**
- **Sin dependencia solo del frontend**

### Consideraciones
- La restricción se basa en el contenido del nombre del recurso
- Nuevos videobeams deben contener "videobeam" en el nombre
- La validación es case-insensitive (`stripos`)

---

## 📝 Mantenimiento

### Agregar Nuevos Videobeams
1. Insertar en tabla `recursos`
2. Asegurar que `nombreRecurso` contenga "videobeam"
3. La restricción se aplicará automáticamente

### Modificar Restricciones
- **Frontend**: `Vista/Reservas_Usuarios.php` líneas ~238-248
- **Backend**: `Controlador/ControladorRegistro.php` líneas ~287-304 y ~607-625

---

**Estado**: ✅ Implementación completa y funcional  
**Fecha**: 11 de junio de 2025  
**Versión**: 1.0
