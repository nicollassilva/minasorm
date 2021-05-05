<?php

use App\Models\Categories;
use App\Models\User;
use MinasORM\Database;

require('vendor/autoload.php');

$user = Categories::create([
    'name' => 'Testando',
    'description' => 'Hi',
    'url' => 'test-test-test',
    'categorie_tertiary_id' => 2
]);