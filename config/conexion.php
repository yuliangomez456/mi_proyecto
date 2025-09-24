<?php
// config/conexion.php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'usuarios_db');
define('DB_USER', 'root');  // Cambia por tu usuario de MySQL
define('DB_PASS', '');      // Cambia por tu contraseña de MySQL

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }

        return $this->conn;
    }
}

// Función para obtener conexión rápida
function getDB() {
    $database = new Database();
    return $database->getConnection();
}
?>