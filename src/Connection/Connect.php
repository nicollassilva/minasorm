<?php

namespace MinasORM\Connection;

use PDO;

/**
 * Connect Class for MinasORM
 * 
 * Copy the .env.example file to your application's
 * root directory and configure it according to your
 * development environment.
 * 
 * @package MinasORM/MinasORM
 */
abstract class Connect {
    /** @var object|null */
    protected static $connection;

    /** @var array */
    protected static $systemConfig;

    /** @var array */
    protected static $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    protected static $ds = DIRECTORY_SEPARATOR;

    /**
     * Restores the .ENV configuration and makes a new connection if it does not exist
     * @return \PDO|null
     */
    public static function makeConnection() {
        if(self::$connection instanceof \PDO) {
            return;
        }

        self::loadEnvVariables();
        self::pdoConnection();

        return self::$connection;
    }

    /**
     * Take the connections configs and if it doesn't exist, it creates it.
     */
    public static function getConfig($configName)
    {
        if(empty(self::$systemConfig)) {
            self::loadEnvVariables();
        }

        return isset(self::$systemConfig[$configName])
            ?  self::$systemConfig[$configName]
            :  null;
    }

    /**
     * Load the ENV array from the root directory and fill in the $systemConfig property
     */
    public static function loadEnvVariables()
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->load();

        self::setConfigSystem();
    }

    /**
     * Set the config system based in .env file
     * @return void
     */
    public static function setConfigSystem()
    {
        self::$systemConfig = [
            'driver' => $_ENV['DB_CONNECTION'],
            'hostname' => $_ENV['DB_HOST'],
            'charset' => $_ENV['DB_CHARSET'],
            'port' => $_ENV['DB_PORT'],
            'database' => $_ENV['DB_DATABASE'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
            'timezone' => $_ENV['DB_TIMEZONE'],
            'pathLog' => self::getRootDirectory() . self::$ds . $_ENV['PATH_LOG'] . self::$ds . 'Logs.log',
            'options' => self::$options
        ];

        return;
    }

    /**
     * Make a PDO Connection and save in $connection property
     */
    public static function pdoConnection()
    {
        date_default_timezone_set(self::$systemConfig['timezone']);
        
        if(empty(self::$connection)) {
            try {
                self::$connection = new PDO(
                    self::$systemConfig['driver'] . ":host=" .
                    self::$systemConfig['hostname'] . ";charset=" .
                    self::$systemConfig['charset'] . ";port=" .
                    self::$systemConfig['port'] . ";dbname=" .
                    self::$systemConfig['database'],
                    self::$systemConfig['username'],
                    self::$systemConfig['password'],
                    self::$systemConfig['options']
                );
            } catch(PDOException $e) {
                return false;
            }
        }
    }

    /**
     * Get project root directory
     * @return string
     */
    public static function getRootDirectory()
    {
        $rootDirectory = dirname(__DIR__, 2);
        $completeLogDirectory = $rootDirectory . self::$ds . $_ENV['PATH_LOG'] . self::$ds;

        if(!file_exists($completeLogDirectory . 'Logs.log')) {
            try {
                fopen($completeLogDirectory . 'Logs.log', 'a');
            } catch(Exception $e) {
                return false;
            }
        }

        return $rootDirectory;
    }

    public static function getInstance()
    {
        return self::$connection;
    }
}