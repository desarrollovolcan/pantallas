<?php
class AdminModel
{
    public function __construct(private Database $db)
    {
    }

    public function findByUsuario(string $usuario): ?array
    {
        $query = "SELECT * FROM administradores WHERE usuario = :usuario AND activo = 1";
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();

        $admin = $stmt->fetch();
        return $admin ?: null;
    }

    public function resetPassword(string $usuario, string $newHash): bool
    {
        $query = "UPDATE administradores SET contrasena = :contrasena WHERE usuario = :usuario";
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->bindParam(':contrasena', $newHash);
        $stmt->bindParam(':usuario', $usuario);
        return $stmt->execute();
    }

    public function stats(): array
    {
        $queries = [
            'videos' => "SELECT COUNT(*) as total FROM videos_corporativos WHERE activo = 1",
            'ubicaciones' => "SELECT COUNT(*) as total FROM ubicaciones_clima WHERE activo = 1",
            'cumpleanos' => "SELECT COUNT(*) as total FROM cumpleanos WHERE activo = 1",
            'eventos' => "SELECT COUNT(*) as total FROM eventos WHERE activo = 1",
            'campañas' => "SELECT COUNT(*) as total FROM campanas_qr WHERE activo = 1",
        ];

        $stats = [];
        $pdo = $this->db->getConnection();
        foreach ($queries as $key => $sql) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats[$key] = (int) ($stmt->fetch()['total'] ?? 0);
        }

        return $stats;
    }
}
?>
