<?php

define('DEPLOY_MODE', isset($argv[1]) ? $argv[1] : 'DEV');
unset($argv[1]);

require_once ((__DIR__))."/vendor/autoload.php";
require_once (__DIR__)."/odie.php";
require_once (__DIR__)."/logic.php";
require_once (__DIR__)."/lib_mongodb.php";
require_once (__DIR__)."/autoload.php";

include 'fetch_answer.php';
include 'fetch_user.php';
include 'fetch_answer.php';
include 'fetch_user.php';
include 'fetch_answer.php';
include 'fetch_user.php';
include 'fetch_answer.php';
include 'fetch_user.php';
include 'fetch_answer.php';
include 'fetch_user.php';
include 'fetch_answer.php';
include 'fetch_user.php';
include 'fetch_answer.php';
include 'fetch_user.php';
include 'fetch_answer.php';
include 'fetch_user.php';
