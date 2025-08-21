<?php

require 'vendor/autoload.php';

// load variables from environment or use .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required('WS_URL');
$dotenv->required('INTERNAL_WS_URL');
$dotenv->required('DB_HOST');
$dotenv->required('DB_USER');
$dotenv->required('DB_PASS');
$dotenv->required('DB_NAME');
$dotenv->ifPresent('ACCEPT_NEW_VOTERS')->isBoolean();
if (filter_var($_ENV['ACCEPT_NEW_VOTERS'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true) {
    $dotenv->required('MIN_VOTING_AGE')->isInteger()->assert(static function (string $value) {
        return (int)$value > 0;
    }, 'Minimum voting age must be greater than 0');
}

// define variables
define('IS_LOCALHOST', str_contains($_SERVER['HTTP_HOST'], 'localhost'));
define('WS_URL', $_ENV['WS_URL']);
define('INTERNAL_WS_URL', $_ENV['INTERNAL_WS_URL']);
// check if local environment then use local domain for cookies
define('INTERNAL_WS_COOKIE_DOMAIN', IS_LOCALHOST ? '' : (empty($_ENV['INTERNAL_WS_COOKIE_DOMAIN']) ? '' : $_ENV['INTERNAL_WS_COOKIE_DOMAIN']));
define('COOKIE_DOMAIN', IS_LOCALHOST ? '' : (empty($_ENV['COOKIE_DOMAIN']) ? '' : $_ENV['COOKIE_DOMAIN']));
define('DB_HOST', $_ENV['DB_HOST']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);
define('DB_NAME', $_ENV['DB_NAME']);
define('DB_PORT', $_ENV['DB_PORT']);
define('ACCEPT_NEW_VOTERS', filter_var($_ENV['ACCEPT_NEW_VOTERS'], FILTER_VALIDATE_BOOLEAN));
define('VOTER_FORM_FIELDS', $_ENV['VOTER_FORM_FIELDS'] ? explode(',', $_ENV['VOTER_FORM_FIELDS']) : ['name', 'mobile', 'fromwhere']);
define('VOTER_REQUIRED_FIELDS', $_ENV['VOTER_REQUIRED_FIELDS'] ? explode(',', $_ENV['VOTER_REQUIRED_FIELDS']) : ['cpr', 'screen', 'fromwhere']);
define('MIN_VOTING_AGE', filter_var($_ENV['MIN_VOTING_AGE'], FILTER_VALIDATE_INT));


$con = mysqli_init();
if (!$con) {
    die("mysqli_init failed");
}

//mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

mysqli_options($con, MYSQLI_OPT_CONNECT_TIMEOUT, 10);
mysqli_real_connect($con, DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
mysqli_set_charset($con, "utf8");
//date_default_timezone_set("Asia/Bahrain");

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}