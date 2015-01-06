<?php

use model\User;
use model\Question;
use model\Answer;

use adapter\Mysql;

DB::setAdapter(new Mysql());

$count = User::getNotFetchedUserCount();
echo "there are $count user to fetch\n";
$n = 0;
while ($username = User::getNotFetchedUserName()) {
    fetch_answer($username);
}
