<?php

require '../xiaochi-db/autoload.php';
require '../occam/occam.php';
require '../data2db.php';
require '../action.php';

list($router, $args) = \Occam\get_router();

$config = require '../config.php';
$dbc = $config['db'];
$db = new \Xiaochi\DB($dbc['dsn'], $dbc['username'], $dbc['password']);

\Occam\run($router, $args);
