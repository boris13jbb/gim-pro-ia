<?php
date_default_timezone_set('America/Guayaquil');
class Database {
    private $host = 'localhost';
    private $db_name = 'ec_gym_system';
    private $username = 'root'; // Por defecto en Laragon
    private $password = '';     // Por defecto vacío en Laragon
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        return $this->conn;
    }
}