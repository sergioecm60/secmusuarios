# Scripts de Compatibilidad para Sistemas Integrados

Este directorio contiene scripts SQL para agregar columnas de compatibilidad a las bases de datos de los sistemas integrados.

## Scripts Disponibles

### 1. compatibilidad_secmalquileres.sql
Agrega columnas de compatibilidad a la base de datos `gestion_alquileres`.

**Columnas agregadas:**
- `updated_at` - Timestamp de última actualización
- `sistema_origen` - Origen del usuario (default: 'secmalquileres')
- `primer_login` - Indica si es el primer login
- `ultimo_login` - Timestamp del último login
- `intentos_fallidos` - Contador de intentos fallidos
- `bloqueado_hasta` - Timestamp hasta cuando está bloqueado
- `metadata` - Campo JSON para datos adicionales

**Índices agregados:**
- `idx_activo` - Índice en columna activo
- `idx_rol` - Índice en columna rol
- `idx_fecha_registro` - Índice en columna fecha_registro

### 2. compatibilidad_secmautos.sql
Agrega columnas de compatibilidad a la base de datos `secmautos_db`.

**Columnas agregadas:**
- `updated_at` - Timestamp de última actualización
- `sistema_origen` - Origen del usuario (default: 'secmautos')
- `primer_login` - Indica si es el primer login
- `ultimo_login` - Timestamp del último login
- `intentos_fallidos` - Contador de intentos fallidos
- `bloqueado_hasta` - Timestamp hasta cuando está bloqueado
- `metadata` - Campo JSON para datos adicionales
- `email` - Columna de email (si no existe)

**Índices agregados:**
- `idx_activo` - Índice en columna activo
- `idx_rol` - Índice en columna rol
- `idx_email` - Índice en columna email

## Cómo Ejecutar los Scripts

### Usando línea de comandos MySQL:

```bash
# Para secmalquileres
mysql -u root -p gestion_alquileres < db/compatibilidad_secmalquileres.sql

# Para secmautos
mysql -u root -p secmautos_db < db/compatibilidad_secmautos.sql
```

### Usando phpMyAdmin:
1. Abrir phpMyAdmin
2. Seleccionar la base de datos correspondiente
3. Ir a la pestaña "SQL"
4. Copiar y pegar el contenido del script
5. Hacer clic en "Ejecutar"

## Notas Importantes

- Los scripts usan `ADD COLUMN IF NOT EXISTS`, por lo que son seguros de ejecutar múltiples veces
- Los índices también se agregan solo si no existen
- Los scripts incluyen `USE nombre_base_datos;` para seleccionar la base de datos correcta
- Los cambios no afectan a los datos existentes
- Los nuevos campos tienen valores por defecto apropiados

## Compatibilidad con Otros Sistemas

Si necesitas agregar compatibilidad para otros sistemas (secmti, secmrrhh, Psitios, secmagencias), puedes usar estos scripts como referencia adaptándolos a la estructura de tablas correspondiente.

## Verificación

Después de ejecutar los scripts, puedes verificar la estructura de las tablas con:

```sql
-- Ver estructura de tabla en secmalquileres
DESCRIBE gestion_alquileres.users;

-- Ver estructura de tabla en secmautos
DESCRIBE secmautos_db.usuarios;
```
