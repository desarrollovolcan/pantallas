<?php
class BirthdayModel
{
    public function __construct(private Database $db)
    {
    }

    public function getUpcoming(int $limit = 3): array
    {
        $query = "SELECT *,
                  DATE_FORMAT(fecha_nacimiento, '%d') as dia,
                  DATE_FORMAT(fecha_nacimiento, '%m') as mes,
                  DATE_FORMAT(fecha_nacimiento, '%Y') as anio,
                  DATE_FORMAT(fecha_nacimiento, '%M') as mes_nombre
            FROM cumpleanos
            WHERE activo = 1";
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute();
        $cumpleanos = $stmt->fetchAll();

        foreach ($cumpleanos as &$cumple) {
            $cumple['dias_para_cumple'] = $this->daysUntil($cumple['fecha_nacimiento']);
        }

        usort($cumpleanos, function ($a, $b) {
            return $a['dias_para_cumple'] <=> $b['dias_para_cumple'];
        });

        return array_slice($cumpleanos, 0, $limit);
    }

    private function daysUntil(string $fecha_nacimiento): int
    {
        $fecha_actual = new DateTime();
        $fecha_nac = new DateTime($fecha_nacimiento);
        $cumpleanos_este_ano = new DateTime($fecha_actual->format('Y') . '-' . $fecha_nac->format('m-d'));

        if ($cumpleanos_este_ano < $fecha_actual) {
            $cumpleanos_este_ano->add(new DateInterval('P1Y'));
        }

        $diferencia = $fecha_actual->diff($cumpleanos_este_ano);
        return (int) $diferencia->days;
    }
}
?>
