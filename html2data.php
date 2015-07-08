<?php
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
function parse_user_answer($content)
{
    return array(get_answer_link_list($content), get_page_num($content));
}
function get_answer_list($answer_link_list) {
    foreach ($answer_link_list as $url) {
        $ret[$url] = get_answer($url);
    }
    return $ret;
}
function get_answer($url) {
    list($code, $content) = zhihu_get($url);
    if ($code !== 200) {
        throw new Exception("code $code", 1);
    }
    return $code;
}
