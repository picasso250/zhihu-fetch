<?php

use model\User;
use model\Question;
use model\Answer;



$count = User::getNotFetchedUserCount();
echo "there are $count user to fetch\n";

while ($username = User::getNotFetchedUserName()) {
    fetch_answer($username);
}
