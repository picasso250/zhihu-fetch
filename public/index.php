<?php

require '../occam/occam.php';
require '../data2db.php';
require '../action.php';

list($router, $args) = \Occam\get_router();

\Occam\run($router, $args);
