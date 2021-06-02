<?php

namespace MinasORM\Builder;

use Monolog\Logger;
use MinasORM\Connection\Connect;
use Monolog\Handler\StreamHandler;

class LogErrors {
    /** @var null|\Monolog\Logger */
    protected $monolog;

    /**
     * Method responsible for starting Monolog
     * 
     * @return void
     */
    public function initMonolog()
    {
        $this->monolog = new Logger('MinasORM');
        $this->monolog->pushHandler(
                new StreamHandler(Connect::getConfig('pathLog'), Logger::WARNING)
            );
    }

    /**
     * Registers a new log in the defined file and returns null in the application.
     * 
     * @param mixed|string $logMessage
     * @param boolean $pdoError = false
     */
    public static function storeLog($logMessage, Bool $pdoError = false)
    {
        $staticClass = new static();
        $staticClass->initMonolog();

        if($pdoError) {
            $staticClass->monolog->error($logMessage);

            return null;
        }
        
        $staticClass->monolog->warning($logMessage);

        return null;
    }
}