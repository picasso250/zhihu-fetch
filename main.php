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

while (true) {
    if ($rows = $db->queryAll("SELECT*from to_be where fetched=0")) {
        foreach ($rows as $row) {
            $url = $row['url'];
            $path = parse_url($url, PHP_URL_PATH);
            if (preg_match('#^/people/(.+)#', $path, $matches)) {
                fetch_users_answers($matches[1]);
            } elseif (preg_match('#^/question/(\d+)#', $path, $matches)) {
                fetch_question_page($matches[1]);
            } else {
                error_log("$url do not ");
            }
            $db->update('to_be', ['fetched' => 1], ['id' => $row['id']]);
        }
    } else {
        sleep(1);
    }
}
