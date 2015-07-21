<?php

namespace Action;

function index()
{
    global $db;
    $questions = $db->queryAll("SELECT * from question order by is404 desc");
    \Occam\render(compact('questions'));
}

function view()
{
    if (empty($_GET['url'])) {
        die("no url");
    }
    $url = $_GET['url'];
    readfile(__DIR__."/data$url");
    include __DIR__.'/view/image.html';
}

function image()
{
    if (empty($_GET['url'])) {
        die('no image');
    }
    $url = $_GET['url'];
    $file = "/image/".urlencode($url);
    if (!exists_data($file)) {
        save_file($file, file_get_contents($url));
    }
    readfile(__DIR__."/data/$file");
}

function save()
{
    if (empty($_POST['url'])) {
        \Occam\echo_json(1, 'no url');
        return;
    }
    global $db;
    $url = $_POST['url'];
    $data = compact('url');
    $db->upsert('task', $data);
    $task = $db->get_task_by_url($url);
    \Occam\echo_json($task, '正在努力获取答案');
}

function check_task($id)
{
    global $db;
    $task = $db->get_task_by_id($id);
    if ($task['fetched']) {
        \Occam\echo_json(0, 'ok');
    } else {
        \Occam\echo_json(1, 'wating');
    }
}

function page404()
{
    $REQUEST_URI = $_SERVER['REQUEST_URI'];
    header("Location:http://www.zhihu.com$REQUEST_URI");
}
