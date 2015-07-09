<?php

namespace Action;

function index()
{
    $users = [];
    foreach (glob(__DIR__.'/data/user/*') as $file) {
        $username = basename($file);
        $users[$username] = unserialize(file_get_contents($file));
    }
    \Occam\render(compact('users'));
}

function answer()
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
    $db->upsert('to_be', compact('url'));
    \Occam\echo_json([], '正在努力获取答案');
}

function page404()
{
    $REQUEST_URI = $_SERVER['REQUEST_URI'];
    $ch = curl_init("http://www.zhihu.com$REQUEST_URI");
    curl_exec($ch);
    curl_close($ch);
}