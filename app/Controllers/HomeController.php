<?php
class HomeController extends Controller
{
    public function __construct(
        private VideoModel $videoModel,
        private WeatherModel $weatherModel,
        private BirthdayModel $birthdayModel,
        private EventModel $eventModel,
        private CampaignModel $campaignModel
    ) {
    }

    public function index(): void
    {
        $videos = $this->videoModel->getActive();
        $ubicaciones_clima = $this->weatherModel->getActiveLocations();
        $cumpleanos = $this->birthdayModel->getUpcoming();
        $eventos = $this->eventModel->getActive();
        $campana_principal = $this->campaignModel->getPrincipal();

        $this->view('home/index', [
            'videos' => $videos,
            'ubicaciones_clima' => $ubicaciones_clima,
            'cumpleanos' => $cumpleanos,
            'eventos' => $eventos,
            'campana_principal' => $campana_principal,
        ]);
    }

    public static function limpiarDescripcion(?string $descripcion): ?string
    {
        if (empty($descripcion)) {
            return $descripcion;
        }
        return preg_replace('/\s*\[main:[^\]]+\]/i', '', $descripcion);
    }

    public static function getWeatherIcon(?string $descripcion): string
    {
        if (empty($descripcion)) {
            return 'cloud-sun';
        }

        $descripcion_lower = strtolower(trim($descripcion));
        $weatherMain = null;
        if (preg_match('/\[main:([^\]]+)\]/', $descripcion_lower, $matches)) {
            $weatherMain = trim($matches[1]);
        }

        if (!empty($weatherMain)) {
            return match ($weatherMain) {
                'clear' => 'sun',
                'clouds' => 'cloud',
                'rain', 'drizzle' => 'cloud-rain',
                'thunderstorm' => 'bolt',
                'snow' => 'snowflake',
                'mist', 'fog', 'haze', 'dust', 'sand' => 'smog',
                default => self::mapDescripcion($descripcion_lower),
            };
        }

        return self::mapDescripcion($descripcion_lower);
    }

    private static function mapDescripcion(string $descripcion_lower): string
    {
        if (str_contains($descripcion_lower, 'despejado') ||
            str_contains($descripcion_lower, 'clear') ||
            str_contains($descripcion_lower, 'soleado') ||
            str_contains($descripcion_lower, 'sunny')) {
            return 'sun';
        }

        if (str_contains($descripcion_lower, 'nube') ||
            str_contains($descripcion_lower, 'cloud') ||
            str_contains($descripcion_lower, 'nublado') ||
            str_contains($descripcion_lower, 'overcast')) {
            return 'cloud';
        }

        if (str_contains($descripcion_lower, 'lluvia') ||
            str_contains($descripcion_lower, 'rain') ||
            str_contains($descripcion_lower, 'llovizna') ||
            str_contains($descripcion_lower, 'drizzle') ||
            str_contains($descripcion_lower, 'chubasco') ||
            str_contains($descripcion_lower, 'shower')) {
            return 'cloud-rain';
        }

        if (str_contains($descripcion_lower, 'tormenta') ||
            str_contains($descripcion_lower, 'thunder') ||
            str_contains($descripcion_lower, 'storm') ||
            str_contains($descripcion_lower, 'rayo') ||
            str_contains($descripcion_lower, 'bolt') ||
            str_contains($descripcion_lower, 'lightning')) {
            return 'bolt';
        }

        if (str_contains($descripcion_lower, 'nieve') ||
            str_contains($descripcion_lower, 'snow') ||
            str_contains($descripcion_lower, 'nevando')) {
            return 'snowflake';
        }

        if (str_contains($descripcion_lower, 'niebla') ||
            str_contains($descripcion_lower, 'mist') ||
            str_contains($descripcion_lower, 'fog') ||
            str_contains($descripcion_lower, 'haze') ||
            str_contains($descripcion_lower, 'bruma') ||
            str_contains($descripcion_lower, 'dust') ||
            str_contains($descripcion_lower, 'sand')) {
            return 'smog';
        }

        return 'cloud-sun';
    }
}
?>
