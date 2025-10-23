<?php
// Database configuration
class Database {
    private $host = 'localhost';
    private $db_name = 'm3u_streaming';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Erro ao conectar ao banco de dados. Verifique as configurações em config/database.php");
        }

        return $this->conn;
    }
}
?>
