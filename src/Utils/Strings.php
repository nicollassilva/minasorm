<?php

namespace MinasORM\Utils;

class Strings {

    /**
     * Return string length
     * @param string $string
     * @return int
     */
    public static function length(String $string)
    {
        return mb_strlen($string, 'utf-8');
    }

    /**
     * Clear a string with 
     * @param mixed $needle
     * @param string $string
     * @param bool $ignoreCaseInsensitive
     * @return string
     */
    public static function clear($needle, String $string, $ignoreCaseInsensitive = false)
    {
        if(!$ignoreCaseInsensitive) {
            return str_replace($needle, '', $string);
        }

        return str_ireplace($needle, '', $string);
    }

    /**
     * Convert the given string to lower-case.
     * @param string $string
     * @return string
     */
    public static function lower(String $string)
    {
        return mb_strtolower($string, 'UTF-8');
    }

    /**
     * Removes a character at the end of the text
     * @param string $character
     * @param string $string
     * @return string
     */
    public static function clearEnd(String $character, String $string)
    {
        return rtrim($string, $character);
    }
}