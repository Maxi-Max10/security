# 📥 IMPORTAR BASE DE DATOS EN HOSTINGER

## Tu Base de Datos Creada:
- **Nombre**: `u404968876_security`
- **Usuario**: `u404968876_security`
- **Host**: `localhost`

---

## 🎯 PASOS PARA IMPORTAR (MÉTODO RECOMENDADO)

### 1. Acceder a phpMyAdmin

1. Ve a tu **hPanel de Hostinger**
2. En el menú lateral, busca **"Bases de datos"**
3. Haz clic en **"phpMyAdmin"**
4. Se abrirá phpMyAdmin

### 2. Seleccionar tu Base de Datos

1. En el **panel izquierdo**, busca y haz clic en: **`u404968876_security`**
2. Debería aparecer el mensaje: "No se han encontrado tablas en la base de datos"

### 3. Importar el Archivo SQL

1. Haz clic en la pestaña **"Importar"** (en la parte superior)
2. En "Archivo a importar", haz clic en **"Seleccionar archivo"**
3. Selecciona el archivo: **`database/hostinger-import.sql`** (recomendado)
   - O usa: `database/schema.sql`
4. **NO cambies ninguna otra opción**
5. Desplázate hasta el final de la página
6. Haz clic en **"Continuar"**

### 4. Verificar la Importación

Deberías ver un mensaje verde que dice:
```
Importación finalizada correctamente. X consultas ejecutadas.
```

En el panel izquierdo, ahora deberías ver:
- ✅ `users` (1 registro)
- ✅ `login_attempts` (0 registros)

---

## 📋 MÉTODO ALTERNATIVO: Copiar y Pegar SQL

Si prefieres no subir archivo:

1. Abre el archivo `database/hostinger-import.sql`
2. **Copia TODO el contenido**
3. En phpMyAdmin, ve a la pestaña **"SQL"**
4. **Pega** todo el código
5. Haz clic en **"Continuar"**

---

## ⚙️ CONFIGURAR CONEXIÓN PHP

Después de importar las tablas, configura la conexión:

### Opción A: Usar Administrador de Archivos de Hostinger

1. En hPanel, ve a **"Archivos" → "Administrador de archivos"**
2. Navega a: `public_html/config/` (o donde esté tu proyecto)
3. Encuentra `database.hostinger.php`
4. **Duplica** el archivo (clic derecho → Copiar)
5. **Renombra** la copia a: `database.php`
6. **Edita** `database.php` y cambia las credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'u404968876_security');
define('DB_PASS', 'TU_CONTRASEÑA_AQUI');  // ⚠️ Pon tu contraseña real
define('DB_NAME', 'u404968876_security');
```

7. **Guarda** el archivo

### Opción B: Editar localmente y subir por Git

1. Abre `config/database.hostinger.php` en tu editor
2. Modifica las credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'u404968876_security');
define('DB_PASS', 'tu_contraseña_real');
define('DB_NAME', 'u404968876_security');
```

3. **Guarda como**: `config/database.php`
4. **NO SUBAS A GIT** (está en .gitignore por seguridad)
5. Sube manualmente por FTP o el Administrador de Archivos

---

## 🧪 PROBAR LA CONEXIÓN

1. Sube el archivo `test-db.php` a tu servidor
2. Accede a: `https://lime-fish-310503.hostingersite.com/test-db.php`
3. Deberías ver:
   - ✅ Conexión exitosa
   - ✅ Tablas creadas
   - ✅ Usuario admin existe

⚠️ **IMPORTANTE**: Elimina `test-db.php` después de probar

---

## 🎉 PROBAR EL SISTEMA

Una vez configurado:

1. Ve a: `https://lime-fish-310503.hostingersite.com/login.php`
2. Usa estas credenciales de prueba:
   - **Email**: `admin@example.com`
   - **Password**: `Admin123`
3. También puedes registrar un nuevo usuario

---

## ❌ SOLUCIÓN DE PROBLEMAS

### Error: "Access denied for user"
- Verifica que la contraseña sea correcta
- Asegúrate de usar: `u404968876_security` (no `u404968876_security_security`)

### Error: "Unknown database"
- Verifica el nombre exacto en hPanel → Bases de datos

### Error: "Table doesn't exist"
- Vuelve a importar el archivo SQL
- Asegúrate de seleccionar la base de datos correcta antes de importar

### Las tablas no aparecen
- Refresca phpMyAdmin (F5)
- Verifica que seleccionaste la base de datos antes de importar

---

## 📁 ARCHIVOS DISPONIBLES PARA IMPORTAR

1. **`database/hostinger-import.sql`** ⭐ RECOMENDADO
   - Limpio y optimizado para Hostinger
   - Con instrucciones incluidas
   - Elimina tablas existentes antes de crear

2. **`database/schema.sql`**
   - Versión original
   - También funciona perfectamente

Usa el que prefieras, ambos crean las mismas tablas.

---

## 🔐 CREDENCIALES DE TU BASE DE DATOS

```
Host:     localhost
Usuario:  u404968876_security
Password: [tu contraseña]
BD:       u404968876_security
```

¿Necesitas ayuda con algún paso? 🚀
