# Guía de Despliegue en Hostinger

## Pasos para subir tu sistema a Hostinger

### 1. Crear Repositorio en GitHub

1. Ve a [GitHub](https://github.com) y crea una cuenta si no tienes
2. Crea un nuevo repositorio (puede ser público o privado)
3. Copia la URL del repositorio (ej: `https://github.com/tu-usuario/login-system.git`)

### 2. Subir el código a GitHub

Ejecuta estos comandos en tu terminal (Git Bash o PowerShell):

```bash
cd c:\seguridad
git remote add origin https://github.com/tu-usuario/tu-repositorio.git
git branch -M main
git push -u origin main
```

### 3. Configurar Hostinger

#### A. Crear Base de Datos

1. Accede a tu **hPanel de Hostinger**
2. Ve a **"Bases de datos" → "Bases de datos MySQL"**
3. Clic en **"Crear nueva base de datos"**
4. Anota estos datos:
   - Nombre de la base de datos
   - Nombre de usuario
   - Contraseña
   - Servidor (generalmente `localhost`)

#### B. Importar Tablas

1. En hPanel, ve a **"phpMyAdmin"**
2. Selecciona tu base de datos
3. Ve a la pestaña **"Importar"**
4. Selecciona el archivo `database/schema.sql` de tu proyecto
5. Clic en **"Continuar"**

#### C. Conectar con Git

1. En hPanel, ve a **"Sitios web" → Selecciona tu dominio**
2. Busca la sección **"Git"** en el menú lateral
3. Clic en **"Crear repositorio"**
4. Completa el formulario:
   - **URL del repositorio**: Tu URL de GitHub
   - **Branch**: `main`
   - **Ruta de destino**: `public_html` (o la carpeta que prefieras)
5. Si es privado, necesitarás agregar la clave SSH de Hostinger a GitHub

#### D. Configurar el Archivo de Base de Datos

1. En hPanel, ve a **"Archivos" → "Administrador de archivos"**
2. Navega a la carpeta donde se desplegó el proyecto
3. Ve a `config/` y duplica `database.example.php` como `database.php`
4. Edita `database.php` con los datos de tu base de datos de Hostinger:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario_hostinger');
define('DB_PASS', 'tu_contraseña_hostinger');
define('DB_NAME', 'tu_base_datos_hostinger');
```

#### E. Actualizar Configuración del Sitio

Edita `config/config.php`:

```php
define('SITE_URL', 'https://tudominio.com'); // Tu dominio real
ini_set('session.cookie_secure', 1); // Habilitar para HTTPS
```

### 4. Verificar Permisos

1. Asegúrate de que la carpeta `logs/` tenga permisos de escritura
2. En el administrador de archivos, clic derecho en `logs/` → Permisos → 755

### 5. Probar el Sistema

1. Accede a tu dominio: `https://tudominio.com`
2. Prueba el registro de un nuevo usuario
3. Prueba el login con:
   - Email: `admin@example.com`
   - Password: `Admin123`

### 6. Actualizar el Sitio

Cuando hagas cambios en tu código local:

```bash
# En tu computadora local
git add .
git commit -m "Descripción de cambios"
git push origin main
```

Luego en Hostinger:

1. Ve a **Git** en hPanel
2. Clic en **"Pull"** para actualizar con los últimos cambios

## Alternativa: Despliegue Manual (FTP)

Si prefieres no usar Git:

1. Conecta por **FTP** usando FileZilla u otro cliente
   - Host: Tu dominio o IP de Hostinger
   - Usuario: Tu usuario de FTP (en hPanel)
   - Puerto: 21
2. Sube todos los archivos a `public_html/`
3. Sigue los pasos 3D, 3E y 4 de arriba

## Solución de Problemas

### Error de conexión a base de datos
- Verifica que los datos en `config/database.php` sean correctos
- Asegúrate de haber importado `schema.sql`

### Errores 500
- Revisa los logs de error en hPanel → "Archivos" → "logs"
- Verifica permisos de carpetas

### Sesión no funciona
- Asegúrate de que `session.cookie_secure` esté en 1 si usas HTTPS
- Verifica que la carpeta `logs/` tenga permisos de escritura

## Comandos Git Útiles

```bash
# Ver estado de archivos
git status

# Ver cambios
git diff

# Agregar archivos específicos
git add archivo.php

# Ver historial
git log --oneline

# Deshacer cambios locales
git checkout -- archivo.php

# Crear nueva rama
git checkout -b nueva-funcionalidad

# Cambiar de rama
git checkout main
```

## Seguridad Adicional

Una vez en producción:

1. Cambia la contraseña del usuario admin
2. Elimina o comenta las líneas de usuario de prueba en `schema.sql`
3. Configura `display_errors` a 0 en `config/config.php`
4. Habilita HTTPS con certificado SSL (Hostinger lo ofrece gratis)
5. Haz backups regulares de tu base de datos

## Soporte

- [Documentación de Hostinger sobre Git](https://support.hostinger.com/es/articles/4423247-como-usar-git-en-hostinger)
- [Documentación de Hostinger sobre MySQL](https://support.hostinger.com/es/collections/2689695-bases-de-datos)
