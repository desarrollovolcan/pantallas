<?php
class VideoModel
{
    public function __construct(private Database $db)
    {
    }

    public function getActive(): array
    {
        $query = "SELECT * FROM videos_corporativos WHERE activo = 1 ORDER BY orden ASC";
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
