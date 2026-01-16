# SECM Usuarios - Sistema Centralizado

Sistema centralizado de gestión de usuarios para los proyectos SECM.

## 📁 Estructura del Proyecto

```
secmusuarios/
├── backend/                    # API REST con Slim Framework
│   ├── config/
│   │   └── db.php             # Configuración de base de datos
│   ├── src/
│   │   ├── Controllers/
│   │   │   └── AuthController.php
│   │   ├── Middleware/
│   │   │   └── AuthMiddleware.php
│   │   ├── Models/
│   │   │   └── User.php
│   │   └── Utils/
│   │       └── JwtHandler.php
│   ├── public/
│   │   ├── .htaccess
│   │   └── index.php          # Entry point
│   ├── .env.example
│   └── composer.json
└── frontend/                   # Frontend vanilla JS + Bootstrap
    ├── css/
    │   └── styles.css
    ├── js/
    │   ├── auth.js
    │   ├── login.js
    │   └── dashboard.js
    ├── login.html
    └── dashboard.html
```

## 🚀 Instalación

### Backend

```bash
cd C:\laragon\www\secmusuarios\backend
composer install
cp .env.example .env
```

Configurar `.env` con las credenciales de la base de datos.

### Base de Datos

La base de datos `usuarios` con la tabla `users_master` ya debería estar creada.

Crear usuario admin (password: `password`):
```sql
INSERT INTO users_master (username, email, password_hash, nombre, apellido, rol, sistema_origen, activo, primer_login)
VALUES ('admin', 'admin@secm.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Sistema', 'superadmin', 'secmusuarios', 1, 1);
```

## 🔐 API Endpoints

### Público
- `POST /api/login` - Autenticación
- `POST /api/register` - Registro de usuarios

### Protegido (requiere token)
- `GET /api/me` - Datos del usuario actual
- `GET /api/users` - Listar todos los usuarios (admin/superadmin)

## 👤 Credenciales de Prueba

- **Usuario**: admin
- **Contraseña**: password

## 🌐 Acceso

- Frontend: `http://localhost/secmusuarios/frontend/login.html`
- Backend API: `http://secmusuarios.test:8083/api/`
- API Info: `http://secmusuarios.test:8083/`
- Panel admin usuarios: `http://localhost:8081/phpmyadmin/index.php?route=/table/structure&db=usuarios&table=users_master`

## 🔌 Integración con otros sistemas

### Uso del API compartido

Para integrar este sistema de autenticación en otros proyectos, puedes usar el archivo compartido `backend/shared/SecmAuth.php`:

```php
require_once __DIR__ . '/../path/to/SecmAuth.php';

// Validar usuario para el sistema actual
$usuario = SecmAuth::validarPara('secmalquileres');
if (!$usuario) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

// Usar datos del usuario
echo "Bienvenido " . $usuario['username'];
echo "Tu rol es: " . $usuario['rol'];
```

### Endpoint de Login (POST /api/login)

```bash
curl -X POST http://secmusuarios.test:8083/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'
```

Respuesta:
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 1,
    "username": "admin",
    "nombre_completo": "Administrador Sistema",
    "email": "admin@secm.local",
    "rol": "superadmin",
    "apps": ["secmalquileres", "secmti", "secmautos", "secmrrhh", "Psitios", "secmagencias"],
    "primer_login": 1
  }
}
```

### Endpoint de Validación (POST /api/validate)

```bash
curl -X POST http://secmusuarios.test:8083/api/validate \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

Respuesta:
```json
{
  "success": true,
  "user_id": 1,
  "username": "admin",
  "rol": "superadmin",
  "apps": ["secmalquileres", "secmti", "secmautos", "secmrrhh", "Psitios", "secmagencias"]
}
```

### Sistemas disponibles

El JWT incluye una lista de apps permitidas:
- `secmalquileres` - Sistema de gestión de alquileres
- `secmti` - Portal de TI
- `secmautos` - Gestión de vehículos
- `secmrrhh` - Recursos humanos
- `Psitios` - Panel de servicios seguros
- `secmagencias` - Sistema de transportes