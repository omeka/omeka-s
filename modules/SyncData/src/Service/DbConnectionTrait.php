<?php

namespace SyncData\Service;

use PDO;

trait DbConnectionTrait
{
    private function getDbConnection()
    {
        $host = getenv('OMEKA_DB_HOST');
        $dbname = getenv('OMEKA_DB_NAME');
        $user = getenv('OMEKA_DB_USER');
        $pass = getenv('OMEKA_DB_PASSWORD');

        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }
}