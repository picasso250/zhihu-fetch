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
