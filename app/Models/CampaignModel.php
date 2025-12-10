<?php
class CampaignModel
{
    public function __construct(private Database $db)
    {
    }

    public function getPrincipal(): ?array
    {
        $query = "SELECT * FROM campanas_qr WHERE activo = 1 AND activa_principal = 1 LIMIT 1";
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute();
        $campaign = $stmt->fetch();
        return $campaign ?: null;
    }
}
?>
