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
    $url = $_POST['url'];
    $db->upsert('to_be', compact('url'));
    \Occam\echo_json(compact('url'));
}
