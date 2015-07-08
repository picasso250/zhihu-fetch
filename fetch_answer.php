<?php

use model\User;
use model\Question;
use model\Answer;



$count = User::getNotFetchedUserCount();
echo "there are $count user to fetch\n";

while (($username = User::getNotFetchedUserName()) !== false) {
    fetch_answer($username);
}
