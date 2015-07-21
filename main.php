<?php

// 完成所有的任务，死循环

require __DIR__."/xiaochi-db/autoload.php";
require __DIR__."/odie.php";
require __DIR__."/lib/html2dom.php";
require __DIR__."/lib/html2data.php";
require __DIR__."/lib/data2db.php";
require __DIR__."/logic.php";

$config = require './config.php';
$dbc = $config['db'];
$db = new \Xiaochi\DB($dbc['dsn'], $dbc['username'], $dbc['password']);
// $db->debug = true;

while (true) {
    if ($rows = $db->queryAll("SELECT*from task where fetched=0")) {
        // do tasks
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
            $db->update('task', ['fetched' => 1], ['id' => $row['id']]);
        }
    } else {
        $file = __DIR__.'/data/'.date('Ymd').'.question_task';
        if (!is_file($file) && $rows = get_live_questions()) {
            // check if questions ok
            echo "check if question live\n";
            foreach ($rows as $row) {
                $id = $row['id'];
                $url = "/question/$id";
                list($code, $_) = zhihu_get($url);
                if ($code === 404) {
                    $db->update('question', ['is404' => 1], compact('id'));
                } elseif ($code === 200) {
                } else {
                    error_log("$url [$code]");
                }
                echo "$url [$code]\n";
            }
            touch($file);
        } else {
            sleep(1);
        }
    }
}
