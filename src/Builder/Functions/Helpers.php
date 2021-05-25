<?php

namespace MinasORM\Builder\Functions;

use PDO;

class Helpers {
    protected static $dataTypes = [
        'string' => PDO::PARAM_STR,
        'integer' => PDO::PARAM_INT,
        'null' => PDO::PARAM_NULL,
        'boolean' => PDO::PARAM_BOOL
    ];
    /**
     * Returns the data type parameter accepted by the PDO Connection
     * 
     * @param mixed $data
     * 
     * @return string
     */
    public static function getDataType($data)
    {
        $types = self::$dataTypes;

        $dataType = gettype($data);

        if(!isset($types[$dataType])) {
            return $types['string'];
        }

        return $types[$dataType];
    }
}