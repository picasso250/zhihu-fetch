<?php

require '../occam/occam.php';
require '../action.php';

list($router, $args) = \Occam\get_router();

\Occam\run($router, $args);
