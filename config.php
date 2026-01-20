<?php
// ===============================
// config.php - Configuration de la base de données
// ===============================
class Database {
    private $host = "127.0.0.1";   // أفضل من localhost
    private $db_name = "gestion_visiteurs";
    private $username = "root";
    private $password = "";        // ✅ فارغة
    public $conn;

    
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            die("Erreur de connexion: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
?>
