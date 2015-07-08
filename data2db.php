<?php

function save_answer($path, $html)
{
    $file = __DIR__."/data$path";
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($file, $html);
}
function get_file($path)
{
    $file = __DIR__."/data$path";
    if (is_file($file)) {
        return file_get_contents($file);
    }
    return false;
}

function save_file($path, $html)
{
    $file = __DIR__."/data$path";
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($file, $html);
}
