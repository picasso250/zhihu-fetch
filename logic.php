<?php

use model\User;
use model\Question;
use model\Answer;

function save_answer($base_url, $username, $answer_link_list) {
    foreach ($answer_link_list as $url) {
        $url = $base_url.$url;
        list($_, $content) = odie_get($url);
        list($question, $content) = parse_answer($content);
        echo "\t$question\n";
        $content = '<link rel="stylesheet" href="http://static.zhihu.com/static/ver/f004e446ca569e4897e59cf26da3e2dc.z.css" type="text/css" media="screen,print" />'
            .$content
            .'<div><a href="'.$url.'">原链接</a></div>'
            .'<script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>'
            .'<script>$("img").each(function(){$(this).attr("src",$(this).attr("data-actualsrc"))});</script>'
            ;
        $root = __DIR__."/$username";
        if (!is_dir($root)) {
            mkdir($root);
        }
        $question = mb_strimwidth($question, 0, 40, '...', 'utf8');
        $question = str_replace('/', '-', $question);
        file_put_contents("$root/$question.html", $content);
    }
}

function fetch_answer($username) {
    $base_url = 'http://www.zhihu.com';
    try {
        User::updateByUserName($username, array('fetch' => User::FETCH_ING));
        $n++;
        $url = "$base_url/people/$username/answers";
        echo "\nfetch No.$n $username\t";
        timer();
        list($code, $content) = uget($url);
        $t = timer();
        $avg = intval(get_average($t, 'user page'));
        echo "[$code]\t$t ms\tAvg: $avg ms\n";
        slog("%s [%s] %s ms", $url, $code, $t);
        if ($code == 404) {
            slog("user $username fetch fail, code $code");
            User::updateByUserName($username, array('fetch' => User::FETCH_FAIL));
            echo "没有这个用户 $username\n";
            continue;
        }
        if ($code != 200) {
            slog("user $username fetch fail, code $code");
            User::updateByUserName($username, array('fetch' => User::FETCH_FAIL));
            echo "奇奇怪怪的返回码 $code\n";
            continue;
        }
        
        $dom = loadHTML($content);
        $dom = $dom->getElementById('zh-pm-page-wrap');
        foreach ($dom->getElementsByTagName('img') as $key => $node) {
            if (($attr = $node->getAttribute('class')) == 'zm-profile-header-img zg-avatar-big zm-avatar-editor-preview') {
                $src = ($node->getAttribute('src'));
            }
        }
        
        User::updateByUserName($username, array('avatar' => $src));

        $link_list = get_answer_link_list($content);
        $rs = Answer::saveAnswer($base_url, $username, $link_list);

        $num = get_page_num($content);
        if ($num > 1) {
            foreach (range(2, $num) as $i) {
                echo "\nNo. $n fetch page $i\t";
                $url_page = "$url?page=$i";
                timer();
                list($code, $content) = uget($url_page);
                $t = timer();
                $avg = intval(get_average($t, 'user page'));
                slog("%s [%s] %s ms", $url_page, $code, $t);
                echo "[$code]\t$t ms\tAvg: $avg ms\n";
                if ($code != 200) {
                    echo "奇奇怪怪的返回码 $code\n";
                    continue;
                }
                $link_list = get_answer_link_list($content);
                Answer::saveAnswer($base_url, $username, $link_list);
            }
        }
        User::updateByUserName($username, array('fetch' => User::FETCH_OK));
    } catch (Exception $e) {
        slog('warning: resume with '.$e->getCode().' '.$e->getMessage());
    }
}

function parse_answer($content) {
    $dom = loadHTML($content);
    $answer = $dom->getElementById('zh-question-answer-wrap');
    $answer = ($answer->C14N());
    $q = $dom->getElementById('zh-question-title');
    $a = $q->getElementsByTagName('a')->item(0);
    $question = $a->textContent;
    return array($question, $answer);
}

function parse_answer_pure($content) {
    $dom = loadHTML($content);
    $answerdom = $dom->getElementById('zh-question-answer-wrap');
    if (empty($answerdom)) {
        slog('warinng: no #zh-question-answer-wrap');
        file_put_contents('last_error.html', $content);
        throw new Exception("no #zh-question-answer-wrap", 1);
    }
    foreach ($answerdom->getElementsByTagName('div') as $div) {
        if ($class = $div->getAttribute('class')) {
            $class = explode(' ', $class);
            if (in_array('zm-editable-content', $class)) {
                $answer= $div->C14N();
            }
        }
    }
    foreach ($answerdom->getElementsByTagName('span') as $span) {
        if ($class = $span->getAttribute('class') == 'count') {
            $vote = intval($span->textContent);
        }
    }
    
    $q = $dom->getElementById('zh-question-title');
    $a = $q->getElementsByTagName('a')->item(0);
    $question = $a->textContent;
    
    $descript = $dom->getElementById('zh-question-detail');
    $descript = $descript->getElementsByTagName('div')->item(0)->C14N();
    
    return array($question, $descript, $answer, $vote);
}

function get_answer_link_list($content) {
    $dom = loadHTML($content);
    $dom = $dom->getElementById('zh-profile-answer-list');
    $ret = array();
    if (empty($dom)) {
        echo "empty #zh-profile-answer-list\n";
        slog('empty #zh-profile-answer-list');
        return $ret;
    }
    foreach ($dom->getElementsByTagName('a') as $key => $node) {
        if ($attr = $node->getAttribute('class') == 'question_link') {
            $ret[] = ($node->getAttribute('href'));
        }
    }
    return $ret;
}

function get_page_num($content) {
    $rs = preg_match_all('%<a href="\?page=(\d+)%', $content, $matches);
    if (!$rs) {
        return 1;
    }
    return (int) max($matches[1]);
}

function get_username_list($content) {
    $dom = loadHTML($content);
    $ret = array();
    foreach ($dom->getElementsByTagName('a') as $key => $node) {
        $href = $node->getAttribute('href');
        if (preg_match('%/people/(.+)$%', $href, $matches)) {
            $username = $matches[1];
            $ret[$username] = $node->textContent;
        }
    }
    return ($ret);
}

function save_answer_to_db($base_url, $username, $answer_link_list) {
    global $pdo;
    foreach ($answer_link_list as $url) {
        echo "\t{$base_url}$url";
        if (preg_match('%^/question/(\d+)/answer/(\d+)%', $url, $matches)) {
            $qid = $matches[1];
            $aid = $matches[2];
        } else {
            echo "$url not good\n";
            exit(1);
        }
        $url = $base_url.$url;
        list($code, $content) = odie_get($url);
        echo "\t$code\n";
        // 自动重刷
        $i = 0;
        while ($code != 200) {
            list($code, $content) = odie_get($url);
            echo "\t$code\n";
            if ($i > 5) {
                echo 'can not fetch',"\n";
                return false;
            }
            $i++;
        }
        if (empty($content)) {
            echo "content is empty\n";
            return false;
        }
        list($question, $descript, $content, $vote) = parse_answer_pure($content);
        echo "\t^$vote\t$question\n";
        $stmt = $pdo->prepare('INSERT INTO question (id, title, description) VALUES (?,?,?) ON DUPLICATE KEY UPDATE title=?,description=?');
        if (!$stmt->execute(array($qid, $question, $descript, $question, $descript))) {
            print_r($stmt->errorInfo());
        }

        $stmt = $pdo->prepare('INSERT INTO answer (id, q_id, user, text, vote) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE text=?, vote=?');
        if (!$stmt->execute(array($aid, $qid, $username, $content, $vote, $content, $vote))) {
            print_r($stmt->errorInfo());
        }
    }
}

function loadHTML($html)
{
    $dom = new DOMDocument;
    $dom->loadHTML($html);
    return $dom;
}
function get_average($n, $tag = 'default')
{
    static $data;
    if (empty($data)) {
        $data = array();
    }
    if (!isset($data[$tag])) {
        $data[$tag] = array('cnt' => 0, 'sum' => 0);
    }
    $data[$tag]['cnt']++;
    $data[$tag]['sum'] += $n;
    return $data[$tag]['sum']/$data[$tag]['cnt'];
}

function timer($tag = 'default')
{
    static $data;
    if (empty($data)) {
        $data = array();
    }
    if (!isset($data[$tag])) {
        $data[$tag] = microtime(true);
        return 0;
    } else {
        $t = microtime(true);
        $d = $t - $data[$tag];
        $data[$tag] = $t;
        return intval($d*1000);
    }
}
