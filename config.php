<?php

require 'vendor/autoload.php';

$con = mysqli_init();
if (!$con) {
    die("mysqli_init failed");
}

//mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

mysqli_options($con, MYSQLI_OPT_CONNECT_TIMEOUT, 10);
mysqli_real_connect($con, "mariadb", "memamali_public", "Sh@[8Pd2z]O$", "memamali_elections", 3306);
mysqli_set_charset($con, "utf8");
date_default_timezone_set("Asia/Bahrain");

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

// load variables from environment or use .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required('WS_URL');
$dotenv->required('INTERNAL_WS_URL');

// define variables
define('IS_LOCALHOST', str_contains($_SERVER['HTTP_HOST'], 'localhost'));
define('WS_URL', $_ENV['WS_URL']);
define('INTERNAL_WS_URL', $_ENV['INTERNAL_WS_URL']);
// check if local environment then use local domain for cookies
define('INTERNAL_WS_COOKIE_DOMAIN', IS_LOCALHOST ? '' : (empty($_ENV['INTERNAL_WS_COOKIE_DOMAIN']) ? '' : $_ENV['INTERNAL_WS_COOKIE_DOMAIN']));
define('COOKIE_DOMAIN', IS_LOCALHOST ? '' : (empty($_ENV['COOKIE_DOMAIN']) ? '' : $_ENV['COOKIE_DOMAIN']));