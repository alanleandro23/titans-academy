<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
Auth::logout();
redirect('login.php');
