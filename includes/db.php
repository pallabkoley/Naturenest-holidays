<?php

require_once __DIR__ . '/config.php';

class Database
{
    private $conn;

    public function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        $this->conn = new mysqli(
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME
        );

        if ($this->conn->connect_error) {
            die('Connection failed: ' . $this->conn->connect_error);
        }

        $this->conn->set_charset('utf8mb4');
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function escapeString($string)
    {
        return $this->conn->real_escape_string($string);
    }

    public function query($sql)
    {
        return $this->conn->query($sql);
    }

    public function insertId()
    {
        return $this->conn->insert_id;
    }

    public function affectedRows()
    {
        return $this->conn->affected_rows;
    }
}

$db = new Database();
?>