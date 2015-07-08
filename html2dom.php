<?php

function loadHTML($html)
{
    libxml_use_internal_errors(true);
    $dom = new DOMDocument;
    $dom->loadHTML($html);
    return $dom;
}
