<?php

namespace App\Core\Database;

use PDO;

class MysqlDatabase extends DatabaseDriver
{

    public function __construct(array $settings=[])
    {
        $host       = $settings['host'];
        $dbname     = $settings['name'];
        $username   = $settings['username'];
        $password   = $settings['password'];
        $charset    = $settings['charset'];

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s',
            $host,
            $dbname,
            $charset);
        $this->pdo = new PDO($dsn, $username, $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    }
    
}