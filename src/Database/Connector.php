<?php

namespace Kachnitel\RideTimeServer\Database;

use PDO;

class Connector
{
    protected $db_options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @TODO: Validate config
     *
     * @param object $config {host, database, user, password}
     */
    public function init($config) {
        $host = $config->host;
        $db   = $config->database;
        $user = $config->user;
        $pass = $config->password;
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        try {
            $this->pdo = new PDO($dsn, $user, $pass, $this->db_options);
            // echo "Connected DB";
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function getUsers() {
        $usersQuery = 'SELECT id, name, email, phone, profile_pic, cover_pic FROM ridetime.`user`;';
        $usersResult = $this->pdo->query($usersQuery);

        $users = [];
        while ($row = $usersResult->fetch()) {
            $users[] = $row;
        }

        return $users;
    }
}
