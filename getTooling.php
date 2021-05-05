<?php

$file = @file_get_contents('http://localhost/tooling.php');
$file = str_replace(['[', ']'], '\'', $file);
$file = str_replace('(', '[', $file);
$file = str_replace('Array', '', $file);
$file = str_replace(')', '],', $file);

echo $file;