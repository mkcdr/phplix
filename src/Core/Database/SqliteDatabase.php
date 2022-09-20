<?php

namespace App\Core\Database;

use PDO;

class SqliteDatabase extends DatabaseDriver
{

    public function __construct(array $settings=[])
    {
        $path = $settings['path'];
        
        $this->pdo = new PDO('sqlite:' . $path);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true); // false
        //$this->pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
        // activate the foreign keys in sqlite database
        $this->pdo->exec('PRAGMA foreign_keys=ON;');
    }

}