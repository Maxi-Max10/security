# 🚀 Configuración de Base de Datos en Hostinger

## Paso 1: Crear Base de Datos en Hostinger

1. **Accede a hPanel** (panel.hostinger.com)
2. Ve a **"Bases de datos" → "Bases de datos MySQL"**
3. Haz clic en **"Crear nueva base de datos"**
4. Completa el formulario:
   - **Nombre de la base de datos**: Por ejemplo: `login_system`
   - **Nombre de usuario**: Se creará automáticamente o elige uno
   - **Contraseña**: Usa una contraseña segura
5. Haz clic en **"Crear"**

## Paso 2: Anotar Credenciales

Después de crear la base de datos, **anota estos datos**:

```
Host: localhost (o el que te muestre Hostinger)
Nombre de BD: u123456789_login (ejemplo)
Usuario: u123456789_user (ejemplo)
Contraseña: la que configuraste
Puerto: 3306 (por defecto)
```

**IMPORTANTE**: Hostinger generalmente agrega un prefijo a tus bases de datos y usuarios. Por ejemplo:
- Si tu usuario de hosting es `u123456789`
- Tu base de datos será: `u123456789_login`
- Tu usuario será: `u123456789_user`

## Paso 3: Importar Estructura de Base de Datos

### Opción A: Usando phpMyAdmin (Recomendado)

1. En hPanel, ve a **"Bases de datos" → "phpMyAdmin"**
2. En el menú izquierdo, selecciona tu base de datos
3. Haz clic en la pestaña **"Importar"**
4. Haz clic en **"Seleccionar archivo"**
5. Selecciona el archivo `database/schema.sql` de tu proyecto
6. Haz clic en **"Continuar"** al final de la página
7. Deberías ver un mensaje de éxito

### Opción B: Manualmente (Alternativa)

Si no tienes acceso a phpMyAdmin, copia y pega este SQL:

```sql
-- Crear tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de intentos de login
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0,
    INDEX idx_email_time (email, attempt_time),
    INDEX idx_ip_time (ip_address, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario de prueba (password: Admin123)
INSERT INTO users (username, email, password) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
```

## Paso 4: Configurar Archivo de Conexión

### Opción A: Usando el Administrador de Archivos de Hostinger

1. En hPanel, ve a **"Archivos" → "Administrador de archivos"**
2. Navega a `public_html/config/` (o donde esté tu proyecto)
3. Busca el archivo `database.hostinger.php`
4. Haz clic derecho → **"Editar"**
5. Reemplaza los valores con tus credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'u123456789_user');  // Tu usuario real
define('DB_PASS', 'TuContraseñaReal');  // Tu contraseña real
define('DB_NAME', 'u123456789_login');  // Tu base de datos real
```

6. Guarda el archivo
7. Renombra `database.hostinger.php` a `database.php`

### Opción B: Desde tu Computadora (Recomendado)

1. Abre el archivo `config/database.hostinger.php`
2. Edita las credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario_hostinger');
define('DB_PASS', 'tu_contraseña_hostinger');
define('DB_NAME', 'tu_base_datos_hostinger');
```

3. Renombra el archivo a `database.php`
4. Actualiza en Git:

```bash
git add config/database.php
git commit -m "Configurar base de datos de Hostinger"
git push origin main
```

5. En Hostinger, actualiza el repositorio:
   - hPanel → Git → Pull

## Paso 5: Actualizar .gitignore (Importante para Seguridad)

El archivo `.gitignore` ya está configurado para **NO subir** el archivo `config/database.php` a Git (por seguridad).

Por eso debes:
1. Mantener `database.hostinger.php` como plantilla en Git
2. Crear `database.php` directamente en el servidor de Hostinger
3. **NUNCA** subir `database.php` con credenciales reales a Git

## Paso 6: Verificar la Conexión

### Crear archivo de prueba (temporal)

Crea un archivo `test-db.php` en la raíz de tu proyecto:

```php
<?php
require_once 'config/database.php';

echo "<h1>Prueba de Conexión a Base de Datos</h1>";

try {
    $conn = getDBConnection();
    echo "<p style='color: green;'>✅ Conexión exitosa!</p>";
    
    // Probar consulta
    $result = $conn->query("SELECT COUNT(*) as total FROM users");
    $row = $result->fetch_assoc();
    echo "<p>Total de usuarios en la BD: " . $row['total'] . "</p>";
    
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
```

Accede a: `https://tudominio.com/test-db.php`

**IMPORTANTE**: Elimina este archivo después de verificar la conexión.

## Paso 7: Probar el Sistema Completo

1. Ve a `https://tudominio.com`
2. Haz clic en **"Registrarse"**
3. Crea un nuevo usuario
4. Intenta hacer login

O prueba con el usuario de prueba:
- Email: `admin@example.com`
- Password: `Admin123`

## Solución de Problemas Comunes

### ❌ Error: "Access denied for user"

**Causas comunes:**
- Usuario o contraseña incorrectos
- El usuario no tiene permisos en esa base de datos
- El host es incorrecto

**Solución:**
1. Verifica las credenciales en hPanel → Bases de datos
2. Asegúrate de que el usuario esté asignado a la base de datos
3. Prueba diferentes valores de host:
   - `localhost`
   - `127.0.0.1`
   - El host específico que te muestre Hostinger

### ❌ Error: "Unknown database"

**Causa:** El nombre de la base de datos es incorrecto

**Solución:**
- Verifica el nombre exacto en hPanel
- Recuerda que Hostinger añade un prefijo (ej: `u123456789_`)

### ❌ Error: "Connection refused"

**Causas:**
- El servidor MySQL está caído
- El host es incorrecto
- Firewall bloqueando la conexión

**Solución:**
- Contacta al soporte de Hostinger
- Verifica el host correcto en los detalles de conexión

### ❌ No se muestran datos o caracteres raros

**Causa:** Problema de charset

**Solución:**
El archivo ya incluye `utf8mb4`, pero verifica que las tablas también lo usen.

## Verificar Credenciales en Hostinger

Para ver las credenciales exactas:

1. hPanel → **Bases de datos**
2. Encuentra tu base de datos
3. Haz clic en los **tres puntos** → **Detalles**
4. Copia los datos exactos que te muestre

## Seguridad Adicional

Una vez que todo funcione:

1. **Cambia la contraseña del usuario admin** en phpMyAdmin
2. **Elimina el archivo de prueba** `test-db.php`
3. **Habilita HTTPS**: hPanel → SSL → Activar
4. **Actualiza config.php**:
   ```php
   ini_set('session.cookie_secure', 1); // Habilitar
   ```

## Comandos Git para Actualizar

Cuando hagas cambios locales:

```bash
# Ver archivos modificados
git status

# Agregar cambios
git add .

# Commit
git commit -m "Descripción del cambio"

# Subir a GitHub
git push origin main
```

En Hostinger:
- hPanel → Git → **Pull** para actualizar

## Contacto con Soporte

Si tienes problemas:
- **Soporte de Hostinger**: Disponible 24/7 por chat
- Ellos pueden verificar tus credenciales de base de datos
- Pueden revisar logs de error del servidor

---

¿Necesitas ayuda con algún paso específico? 🚀
