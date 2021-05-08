<?php

namespace MinasORM\Builder;

use Monolog\Logger;
use MinasORM\Connection\Connect;
use Monolog\Handler\StreamHandler;

class LogErrors {
    protected $monolog;

    public function initMonolog()
    {
        $this->monolog = new Logger('MinasORM');
        $this->monolog->pushHandler(
                new StreamHandler(Connect::getConfig('pathLog'), Logger::WARNING)
            );
    }

    public static function storeLog($logMessage, $pdoError = false)
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