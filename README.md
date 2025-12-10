# pantallas

Nuevo ejemplo MVC (PHP + SQLite) basado en la estructura existente. Incluye instalador automático para generar la base de datos y un usuario administrador al abrir el proyecto por primera vez.

## Requerimientos
- PHP 8.1+ con extensión `pdo_sqlite` disponible.

## Cómo ejecutar
1. Instala dependencias del sistema si aún no las tienes (solo PHP con SQLite es necesario).
2. Inicia el servidor embebido de PHP desde la raíz del proyecto (el `index.php` principal reenvía al nuevo MVC):
   ```bash
   php -S localhost:8000 -t .
   ```
3. Abre `http://localhost:8000` en tu navegador. El instalador se ejecuta automáticamente al entrar a `/`.

La primera carga ejecuta el instalador, crea `mvc_app/storage/database.sqlite`, genera las tablas `users` y `screens` y registra el usuario administrador:
- Usuario: `admin@local`
- Contraseña: `admin123`

Puedes consultar el estado del proyecto en `/status` para verificar que la instalación se completó correctamente.

## Solución de problemas
- Si ves un error 500 al cargar la app, revisa los mensajes que muestre la página para identificar el problema específico.
