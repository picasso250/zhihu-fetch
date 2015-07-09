<?php

require (__DIR__)."/xiaochi-db/autoload.php";
require (__DIR__)."/odie.php";
require (__DIR__)."/html2dom.php";
require (__DIR__)."/html2data.php";
require (__DIR__)."/data2db.php";
require (__DIR__)."/logic.php";

$config = require './config.php';
$dbc = $config['db'];
$db = new \Xiaochi\DB($dbc['dsn'], $dbc['username'], $dbc['password']);

$username = 'liuniandate';
if (isset($argv[1])) {
    $username = $argv[1];
}
fetch_users_answers($username);
$qid = '31847002';
fetch_question_page($qid);
