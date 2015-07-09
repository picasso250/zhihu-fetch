<?php

use model\User;
use model\Question;
use model\Answer;

function fetch_answer($username) {
    $base_url = 'http://www.zhihu.com';
    try {
        User::updateByUserName($username, array('fetch' => User::FETCH_ING));
        $url = "$base_url/people/$username/answers";
        echo "\nfetch $username\t";
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
                echo "\n fetch page $i\t";
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
/**
 * @return array of answer
 */
function fetch_users_answers($username)
{
    $url = "/people/$username/answers";
    echo "fetch $username $url\n";
    list($code, $content) = zhihu_get($url);
    if ($code == 404) {
        echo "没有这个用户 $username\n";
        exit(1);
    }
    if ($code !== 200) {
        throw new Exception("code $code", 1);
    }

    $num = proc_user_page($content, $username);
    if ($num > 1) {
        foreach (range(2, $num) as $i) {
            echo "fetch page $i\n";
            $url_page = "$url?page=$i";
            list($code, $content) = zhihu_get($url_page);
            if ($code !== 200) {
                throw new Exception("$url_page => $code", 1);
            }
            $num = proc_user_page($content, $username);
        }
    }
}
function proc_user_page($content, $username)
{
    $dom = loadHTML($content);
    file_put_contents('user', $content);
    $link_list = get_answer_link_list($dom);
    $info = get_user_info($dom);
    $answers = [];
    foreach ($link_list as $url => $title) {
        if (!preg_match('#^/question/(\d+)/answer/(\d+)$#', $url, $matches)) {
            throw new Exception("url not parse", 1);
        }
        $qid = $matches[1];
        $aid = $matches[2];
        fetch_question_page("$qid");
        save_answer_to_db(['qid' => $qid, 'id' => $aid, 'username' => $username]);
    }
    $key = "/user/$username";
    save_file($key, serialize($info));
    return $num = get_page_num($content);
}
function fetch_question_page($id)
{
    $url = "/question/$id";
    list($code, $content) = zhihu_get($url);
    if ($code == 404) {
        echo "no $url\n";
        exit(1);
    }
    if ($code !== 200) {
        error_log("$url [$code]");
        return false;
    }
    save_file($url, $content);
    save_question($id, get_question_info($content));
    return $content;
}
