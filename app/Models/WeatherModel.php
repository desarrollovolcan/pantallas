<?php
class WeatherModel
{
    public function __construct(private Database $db)
    {
    }

    public function getActiveLocations(): array
    {
        $query = "SELECT u.*, d.temperatura, d.descripcion, d.humedad, d.velocidad_viento
                  FROM ubicaciones_clima u
                  LEFT JOIN datos_clima d ON u.id = d.ubicacion_id
                  WHERE u.activo = 1
                  ORDER BY u.orden ASC";
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
