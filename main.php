<?php

//  dsn_db="mysql:dbname=zhihu;host=127.0.0.1" user_db="root" password_db="root" php main.php wang-xiao-chi

define('DEPLOY_MODE', getenv('DEPLOY_MODE') ?: 'DEV');

error_reporting(E_ALL);
ini_set('log_errors', 1); // 记录错误
ini_set('error_log', __DIR__.'/php_error.log'); // 错误记录文件的位置

ini_set('display_errors', 1);

require_once __DIR__."/odie.php";
require_once __DIR__."/logic.php";
require_once __DIR__."/lib_mongodb.php";
require_once __DIR__."/DB.php";
require_once __DIR__."/adapter/Mysql.php";
require_once __DIR__."/autoload.php";

use adapter\Mysql;

DB::setAdapter(new Mysql());

if (count($argv) === 2) {
    $username = $argv[1];
    echo 'you say fetch ', $username, "\n";
    fetch_answer($username);
}
include 'fetch_answer.php';
include 'fetch_user.php';
include 'fetch_answer.php';
include 'fetch_user.php';
include 'fetch_answer.php';
include 'fetch_user.php';
include 'fetch_answer.php';
include 'fetch_user.php';
include 'fetch_answer.php';
include 'fetch_user.php';
include 'fetch_answer.php';
include 'fetch_user.php';
include 'fetch_answer.php';
include 'fetch_user.php';
include 'fetch_answer.php';
include 'fetch_user.php';
include 'fetch_answer.php';
