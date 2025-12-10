<?php
/**
 * Configuración de la base de datos
 */
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    
    public function __construct() {
        // Configuración para Docker
        // Desde el contenedor web, usar el nombre del servicio de la base de datos
        $this->host = 'localhost'; // Nombre del contenedor de MySQL
        $this->db_name = 'adlinksc1_impa';
        $this->username = 'adlinksc1_impa';
        $this->password = 'vorqyw-Mygkis-0cuqja';
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
               "mysql:host={$this->host};port=3306;dbname={$this->db_name};charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>
