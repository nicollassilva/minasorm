<?php

use App\Models\Categories;
use App\Models\User;
use MinasORM\Database;

require('vendor/autoload.php');

$user = Categories::create([
    'id' => 2,
    'description' => 'osiaoi'
]);

dd($user);
