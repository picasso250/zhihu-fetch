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
