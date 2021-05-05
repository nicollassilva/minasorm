<?php

use App\Models\User;

require('vendor/autoload.php');

$user = User::latest()->limit(1)->offset(1)->get();

dd($user);