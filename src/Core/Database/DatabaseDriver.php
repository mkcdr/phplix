<?php

namespace App\Core\Database;

use PDO;

abstract class DatabaseDriver implements DatabaseDriverInterface
{
    /**
     * @var PDO
     */
    protected $pdo;

    public function __destruct()
    {
        $this->pdo = null;
    }

    /**
     * Get PDO connection
     * 
     * @return PDO
     */
    public function getConnection() : PDO
    {
        return $this->pdo;
    }

    /**
     * Get PDO driver name
     * 
     * @return string
     */
    public function name() : string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Get current driver version
     * 
     * @return string
     */
    public function version() : string
    {
        return $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }
}