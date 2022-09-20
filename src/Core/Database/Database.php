<?php

namespace App\Core\Database;

use PDO;
use InvalidArgumentException;

class Database
{
    /**
     * @var self
     */
    protected static $instance;

    /**
     * @var DatabaseDriverInterface
     */
    protected $driver;

    /**
     * Database constructor
     * 
     * @param array $settings
     */
    protected function __construct(array $settings=[])
    {
        if (!$settings) 
        {
            throw new InvalidArgumentException('Invalid database configuration.');
        }

        $driver_class = __NAMESPACE__ . '\\' . ucfirst(strtolower($settings['driver'])) . 'Database';

        if (!class_exists($driver_class)) 
        {
            throw new InvalidArgumentException("Database driver class '$driver_class' not supported.");
        }

        $this->driver = new $driver_class($settings);
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Get current instance if already initialized
     * 
     * @param array $settings
     * @return self
     */
    public static function getInstance(array $settings=[]) : self
    {
        if (self::$instance == null) {
            self::$instance = new Self($settings);
        }

        return self::$instance;
    }

    /**
     * Get the actual PDO connection
     * 
     * @return PDO
     */
    public static function getConnection() : PDO
    {
        return self::getInstance()->getDriver()->getConnection();
    }

    /**
     * Get the current driver
     * 
     * @return DatabaseDriverInterface
     */
    public function getDriver() : DatabaseDriverInterface
    {
        return $this->driver;
    }

    /**
     * Close database connection
     * 
     * @return void
     */
    public function disconnect() : void
    {
        unset($this->driver);
    }

}