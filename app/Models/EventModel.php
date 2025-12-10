<?php
class EventModel
{
    public function __construct(private Database $db)
    {
    }

    public function getActive(int $limit = 5): array
    {
        $query = "SELECT *,
                  DATE_FORMAT(fecha_evento, '%d/%m/%Y %H:%i') as fecha_formateada,
                  DATEDIFF(fecha_evento, NOW()) as dias_restantes
                  FROM eventos
                  WHERE activo = 1
                  ORDER BY fecha_creacion DESC
                  LIMIT :limit";
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
