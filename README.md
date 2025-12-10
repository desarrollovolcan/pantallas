# pantallas

Nuevo ejemplo MVC (PHP + MySQL) basado en la estructura existente. Incluye instalador automático para generar la base de datos
y un usuario administrador al abrir el proyecto por primera vez.

## Requerimientos
- PHP 8.1+ con extensión `pdo_mysql` disponible.
- MySQL accesible en `127.0.0.1` con base de datos `pantalla` y usuario `root` sin contraseña.

## Cómo ejecutar
1. Inicia el servidor embebido de PHP desde la raíz del proyecto (el `index.php` principal reenvía al nuevo MVC):
   ```bash
   php -S localhost:8000 -t .
   ```
2. Abre `http://localhost:8000` en tu navegador. El instalador se ejecuta automáticamente al entrar a `/`.

La primera carga ejecuta el instalador, genera las tablas `users` y `screens` en la base de datos `pantalla` y registra el
usuario administrador:
- Usuario: `admin@local`
- Contraseña: `admin123`

Puedes consultar el estado del proyecto en `/status` para verificar que la instalación se completó correctamente.

## Solución de problemas
- Si ves un error 500 al cargar la app, revisa los mensajes que muestre la página para identificar el problema específico.
