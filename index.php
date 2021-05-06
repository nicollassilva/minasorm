<?php

use App\Models\Categories;
use App\Models\User;
use MinasORM\Database;

require('vendor/autoload.php');

$user = Categories::delete(3);

dd($user);