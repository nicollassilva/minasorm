<?php

namespace MinasORM\Utils;

class Arrays {

    /**
     * Return string length
     * @param Array $array
     * @return int
     */
    public static function clear(Array $array)
    {
        $clearArray = fn($value) => ($value !== null && $value !== false && $value !== '' && $value !== ' ');

        return array_filter($array, $clearArray);
    }
}