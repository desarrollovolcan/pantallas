<?php
/**
 * Archivo de protección - Admin
 * Impide el acceso directo al directorio admin
 */
http_response_code(401);
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 - No Autorizado</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .error-container {
            text-align: center;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        .error-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .error-message {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .error-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔒</div>
        <div class="error-code">401</div>
        <div class="error-title">Acceso No Autorizado</div>
        <div class="error-message">
            No tiene permisos para acceder a este directorio.<br>
            Esta área está restringida y requiere autenticación.
        </div>
    </div>
</body>
</html>

