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

**📖 GUÍA COMPLETA**: Lee el archivo `HOSTINGER_DATABASE_SETUP.md` para instrucciones detalladas paso a paso.

### Resumen Rápido:

1. **Crear base de datos en Hostinger**
   - hPanel → Bases de datos MySQL → Crear nueva
   - Anotar: host, usuario, contraseña, nombre de BD

2. **Importar estructura**
   - phpMyAdmin → Importar → `database/schema.sql`

3. **Configurar archivo de conexión**
   ```bash
   # Copiar plantilla
   cp config/database.hostinger.php config/database.php
   
   # Editar config/database.php con tus credenciales reales
   ```

4. **Conectar Git en Hostinger**
   - hPanel → Git → Crear repositorio
   - URL: Tu repo de GitHub
   - Branch: main
   - Destino: public_html

5. **Actualizar configuración del sitio**
   - Edita `config/config.php` con tu dominio real
   - Habilita HTTPS si está disponible

📚 Para más detalles, consulta: `HOSTINGER_DATABASE_SETUP.md`

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
