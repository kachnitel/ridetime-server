<?php
namespace Kachnitel\RideTimeServer\Database;

use PDO;

/**
 * Generic database connector
 *
 */
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
    public function init($config)
    {
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

    /**
     * Runs a query or a prepared statement if $params are supplied
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    public function query($query, $params = []): array
    {
        if (empty($params)) {
            $result = $this->pdo->query($query);
        } else {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $result = $stmt;
        }

        $data = [];
        while ($row = $result->fetch()) {
            $data[] = $row;
        }

        return $data;
    }
}
