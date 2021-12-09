<?php

declare(strict_types=1);

// report all errors
use Dotenv\Dotenv;

error_reporting(-1);

setlocale(LC_ALL, 'en_US');
date_default_timezone_set('UTC');

// composer
require_once __DIR__ . '/../vendor/autoload.php';

// environment
call_user_func(function (): void {
    $dotenv = Dotenv::create(__DIR__);
    $dotenv->load();
    $dotenv->required('testMssql')->allowedValues(['yes', 'no']);
    $dotenv->required('testSqlsrv')->allowedValues(['yes', 'no']);
    $dotenv->required('testMysqli')->allowedValues(['yes', 'no']);
});
