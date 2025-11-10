# Sistema de Login PHP

Sistema de autenticación con PHP y MySQL con las siguientes características:

## Características

- ✅ Registro de usuarios
- ✅ Inicio de sesión
- ✅ Cierre de sesión
- ✅ Protección CSRF
- ✅ Contraseñas hasheadas con bcrypt
- ✅ Validación de datos
- ✅ Registro de intentos de login
- ✅ Panel de usuario (Dashboard)
- ✅ Diseño responsive

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)

## Instalación Local

1. Clona este repositorio
2. Importa el archivo `database/schema.sql` en tu base de datos MySQL
3. Configura las credenciales de la base de datos en `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'tu_usuario');
   define('DB_PASS', 'tu_contraseña');
   define('DB_NAME', 'login_system');
   ```
4. Accede a la aplicación desde tu navegador

## Instalación en Hostinger

### 1. Preparar el repositorio Git

```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin <tu-repositorio-github>
git push -u origin main
```

### 2. Configurar en Hostinger

1. **Accede a hPanel de Hostinger**
2. **Ve a "Sitios web" → Selecciona tu dominio**
3. **Git → Crear repositorio**
   - URL del repositorio: Tu URL de GitHub
   - Branch: main
   - Ruta de destino: public_html (o la carpeta que prefieras)

### 3. Configurar Base de Datos

1. **En hPanel, ve a "Bases de datos MySQL"**
2. **Crea una nueva base de datos**
3. **Crea un usuario y asígnalo a la base de datos**
4. **Importa el archivo `database/schema.sql` usando phpMyAdmin**

### 4. Actualizar configuración

Edita `config/database.php` con los datos de Hostinger:

```php
define('DB_HOST', 'localhost'); // o el host que te proporcione Hostinger
define('DB_USER', 'tu_usuario_hostinger');
define('DB_PASS', 'tu_contraseña_hostinger');
define('DB_NAME', 'tu_base_datos_hostinger');
```

Edita `config/config.php`:

```php
define('SITE_URL', 'https://tudominio.com');
ini_set('session.cookie_secure', 1); // Habilitar para HTTPS
```

### 5. Permisos de carpetas

Asegúrate de que la carpeta `logs/` tenga permisos de escritura (755 o 775).

## Credenciales de Prueba

- Email: `admin@example.com`
- Password: `Admin123`

## Estructura del Proyecto

```
seguridad/
├── assets/
│   └── css/
│       └── style.css
├── config/
│   ├── config.php
│   └── database.php
├── database/
│   └── schema.sql
├── includes/
│   └── functions.php
├── logs/
│   └── error.log
├── index.php
├── login.php
├── register.php
├── dashboard.php
├── logout.php
└── README.md
```

## Seguridad

- Contraseñas hasheadas con `password_hash()`
- Protección CSRF en todos los formularios
- Sanitización de entradas
- Validación de datos
- Sesiones seguras
- Registro de intentos de login

## Comandos Git Útiles

```bash
# Ver cambios
git status

# Agregar archivos modificados
git add .

# Commit
git commit -m "Descripción de cambios"

# Push a Hostinger
git push origin main

# Actualizar desde repositorio
git pull origin main
```

## Soporte

Para problemas o preguntas, revisa la documentación de Hostinger sobre despliegue con Git.
