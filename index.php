<?php

use App\Models\User;

require('vendor/autoload.php');

$user = User::where([
    ['id', 54]
])->first(['id, password']);

dd($user);