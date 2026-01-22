# SECM Usuarios - Sistema Centralizado de Gestión de Usuarios

Sistema centralizado de gestión de usuarios para los proyectos SECM. Permite administrar usuarios de múltiples sistemas desde un único punto.

## Estructura del Proyecto

```
secmusuarios/
├── backend/                    # API REST con Slim Framework
│   ├── config/
│   │   └── db.php             # Conexiones a todas las bases de datos
│   ├── src/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php    # Login, registro, validación
│   │   │   └── UsersController.php   # Listado de usuarios multi-sistema
│   │   ├── Middleware/
│   │   │   └── AuthMiddleware.php    # Validación JWT
│   │   ├── Models/
│   │   │   └── User.php              # Modelo de usuario
│   │   └── Utils/
│   │       └── JwtHandler.php        # Manejo de tokens JWT
│   ├── public/
│   │   └── index.php          # Entry point (Slim)
│   ├── shared/
│   │   └── SecmAuth.php       # Librería para integrar en otros sistemas
│   ├── .env.example
│   └── composer.json
├── frontend/                   # Frontend vanilla JS + Bootstrap
│   ├── css/
│   │   └── styles.css
│   ├── js/
│   │   ├── auth.js            # Funciones de autenticación
│   │   ├── login.js           # Lógica de login
│   │   └── dashboard.js       # Dashboard con usuarios
│   ├── login.html
│   └── dashboard.html
└── db/
    └── usuarios.sql           # Schema de la base de datos
```

## Instalación (Desarrollo - Laragon)

### 1. Backend

```bash
cd C:\laragon\www\secmusuarios\backend
composer install
cp .env.example .env
```

### 2. Configurar .env

```ini
# Database Principal
DB_HOST=localhost
DB_PORT=3306
DB_NAME=usuarios
DB_USER=root
DB_PASS=

# Bases de datos de otros sistemas
DB_SECMALQUILERES=gestion_alquileres
DB_SECMTI=portal_db
DB_SECMAUTOS=secmautos_db
DB_SECMRRHH=secmrrhh
DB_PSITIOS=secure_panel_db
DB_SECMAGENCIAS=sistema_transportes

# JWT - Generar con: php -r "echo base64_encode(random_bytes(32));"
JWT_SECRET=TU_SECRET_AQUI
JWT_ALGORITHM=HS256
JWT_EXPIRES_IN=3600

# App
APP_ENV=development
APP_DEBUG=true
```

### 3. Base de Datos

```bash
mysql -u root -p < db/usuarios.sql
```

### 4. Crear Usuario Admin

```bash
# Generar hash de contraseña
php -r "echo password_hash('admin123', PASSWORD_ARGON2ID);"
```

```sql
INSERT INTO users_master (username, email, password_hash, nombre, apellido, rol, sistema_origen)
VALUES ('admin', 'admin@secm.local', 'HASH_GENERADO', 'Admin', 'Sistema', 'superadmin', 'secmusuarios');
```

## API Endpoints

### Públicos
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/login` | Autenticación |
| POST | `/api/register` | Registro de usuarios |
| POST | `/api/validate` | Validar token JWT |

### Protegidos (requieren token)
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/me` | Datos del usuario actual |
| GET | `/api/users` | Listar usuarios de users_master |
| GET | `/api/users/all` | Listar usuarios de TODOS los sistemas |
| POST | `/api/users` | Crear usuario en sistema maestro y sistemas seleccionados |
| PUT | `/api/users` | Actualizar usuario en sistema maestro y sistemas seleccionados |
| DELETE | `/api/users` | Eliminar usuario de TODOS los sistemas |

## Acceso (Laragon)

- **Frontend**: `http://secmusuarios.test:8081/login.html`
- **Dashboard**: `http://secmusuarios.test:8081/dashboard.html`
- **API Info**: `http://secmusuarios.test:8081/api`

## Credenciales de Prueba

- **Usuario**: `admin`
- **Contraseña**: `admin123`

## Ejemplo de Uso del API

### Login
```bash
curl -X POST http://secmusuarios.test:8081/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

**Respuesta:**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 1,
    "username": "admin",
    "nombre_completo": "Admin Sistema",
    "email": "admin@secm.local",
    "rol": "superadmin",
    "apps": ["secmalquileres", "secmti", "secmautos", "secmrrhh", "Psitios", "secmagencias"]
  }
}
```

### Obtener usuarios de todos los sistemas
```bash
curl http://secmusuarios.test:8081/api/users/all \
  -H "Authorization: Bearer TU_TOKEN"
```

### Crear usuario en múltiples sistemas
```bash
curl -X POST http://secmusuarios.test:8081/api/users \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN" \
  -d '{
    "username": "juan",
    "email": "juan@ejemplo.com",
    "password": "clave123",
    "nombre": "Juan",
    "apellido": "Pérez",
    "rol": "user",
    "systems": ["secmalquileres", "secmti", "secmautos"]
  }'
```

### Actualizar usuario en múltiples sistemas
```bash
curl -X PUT http://secmusuarios.test:8081/api/users \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN" \
  -d '{
    "id": 1,
    "username": "juan",
    "email": "nuevo@email.com",
    "rol": "admin",
    "activo": 1,
    "systems": ["secmalquileres", "secmti", "secmautos"]
  }'
```

### Eliminar usuario de todos los sistemas
```bash
curl -X DELETE http://secmusuarios.test:8081/api/users \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN" \
  -d '{
    "id": 1,
    "username": "juan",
    "systems": ["secmusuarios", "secmalquileres", "secmti", "secmautos", "secmrrhh", "Psitios", "secmagencias"]
  }'
```

## Integración con Otros Sistemas

Usar `backend/shared/SecmAuth.php`:

```php
require_once '/path/to/SecmAuth.php';

$usuario = SecmAuth::validarPara('secmalquileres');
if (!$usuario) {
    http_response_code(403);
    exit(json_encode(['error' => 'Acceso denegado']));
}

echo "Bienvenido " . $usuario['username'];
```

## Sistemas Integrados

| Sistema | Base de Datos | Tabla |
|---------|---------------|-------|
| SECM Usuarios | usuarios | users_master |
| Alquileres | gestion_alquileres | users |
| TI Portal | portal_db | users |
| RRHH | secmrrhh | usuarios |
| Psitios | secure_panel_db | users |
| Agencias | sistema_transportes | usuarios |

## Gestión Multi-Sistema

El sistema permite gestionar usuarios de forma centralizada. Al crear un usuario:

1. **Usuario Maestro**: Se crea automáticamente en la tabla `users_master`
2. **Sistemas Adicionales**: Puedes seleccionar en qué sistemas replicar el usuario mediante checkboxes

### Funcionalidades

- **Crear Usuario**: Formulario único con selección de sistemas mediante checkboxes
- **Editar Usuario**: Modificar datos en sistema maestro y sistemas seleccionados
- **Cambiar Estado**: Activar/deshabilitar usuario rápidamente en todos los sistemas
- **Eliminar Usuario**: Eliminar usuario de TODOS los sistemas (solo superadmin)

### Compatibilidad de Sistemas

Para asegurar compatibilidad completa, ejecuta los scripts de compatibilidad:

```bash
# Para secmalquileres
mysql -u root -p gestion_alquileres < db/compatibilidad_secmalquileres.sql

# Para secmautos
mysql -u root -p secmautos_db < db/compatibilidad_secmautos.sql
```

Ver [db/README_COMPATIBILIDAD.md](db/README_COMPATIBILIDAD.md) para más detalles.

## Licencia

GNU GPL v3 - Copyleft 2026 Sergio Cabrera
