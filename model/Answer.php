<?php

namespace model;

use DB;
use Exception;

class Answer extends Base
{
    public static function _saveAnswer($aid, $qid, $username, $content, $vote) {
        $update = array('id' => $aid, 'q_id' => $qid, 'user' => $username, 'text' => $content, 'vote' => $vote);
        $where = array('id' => $aid);
        return DB::upsert('answer', $update, $where);
    }

    public static function saveAnswer($base_url, $username, $answer_link_list) {
        foreach ($answer_link_list as $url) {
            if (preg_match('%^/question/(\d+)/answer/(\d+)%', $url, $matches)) {
                $qid = $matches[1];
                $aid = $matches[2];
            } else {
                echo "$url not good\n";
                exit(1);
            }
            $url = $base_url.$url;
            echo "\t$url";
            $t = microtime(true);
            list($code, $content) = odie_get($url);
            echo "\t[$code]";
            if ($code != 200) { // fail fast
                echo "\tfail\n";
                slog("$url [$code] error");
                $success_ratio = get_average(0, 'success_ratio');
                continue;
            } else {
                $success_ratio = get_average(1, 'success_ratio');
            }
            $t = intval((microtime(true) - $t) * 1000);
            $avg = intval(get_average($t));
            echo "\t$t ms\n";
            if (empty($content)) {
                echo "content is empty\n";
                slog("$url [$code] empty");
                return false;
            }
            list($question, $descript, $content, $vote) = parse_answer_pure($content);
            slog("$url [$code] ^$vote\t$question");

            Question::saveQuestion($qid, $question, $descript);

            Answer::_saveAnswer($aid, $qid, $username, $content, $vote);
        }
        if (isset($success_ratio) && isset($avg)) {
            $success_ratio = intval($success_ratio*100).'%';
            echo "\tAvg: $avg ms\tsuccess_ratio: $success_ratio\n";
        }
    }
}