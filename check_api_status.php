<?php
require_once 'config/config.php';

echo "=== VERIFICACIÓN DE ESTADO DE API ===\n\n";

$apiKey = WEATHER_API_KEY;
$url = "https://api.openweathermap.org/data/2.5/weather?lat=40.4168&lon=-3.7038&appid={$apiKey}&units=metric&lang=es";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "HTTP Code: " . $httpCode . "\n";

if ($httpCode === 200) {
    echo "🎉 ¡EXCELENTE! Tu API key ya está activa.\n";
    echo "✅ Los datos reales de clima están disponibles.\n";
    echo "🔄 El sistema ahora usará datos reales de OpenWeatherMap.\n\n";
    
    $data = json_decode($response, true);
    echo "Ejemplo de datos reales:\n";
    echo "- Temperatura: " . $data['main']['temp'] . "°C\n";
    echo "- Descripción: " . $data['weather'][0]['description'] . "\n";
    echo "- Humedad: " . $data['main']['humidity'] . "%\n";
    echo "- Viento: " . round($data['wind']['speed'] * 3.6) . " km/h\n";
    
} elseif ($httpCode === 401) {
    echo "⏳ API key aún no activa (Error 401)\n";
    echo "🕐 Sigue esperando... puede tardar hasta 2 horas.\n";
    echo "📊 Mientras tanto, el sistema usa datos simulados realistas.\n";
} else {
    echo "⚠️ Error inesperado: " . $httpCode . "\n";
    echo "Response: " . $response . "\n";
}

echo "\n=== INFORMACIÓN ===\n";
echo "El sistema está configurado para:\n";
echo "1. Intentar usar la API real primero\n";
echo "2. Si falla, usar datos simulados realistas\n";
echo "3. Verificar automáticamente cuando la API esté lista\n";
?>




