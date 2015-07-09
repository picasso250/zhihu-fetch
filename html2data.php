<?php
function get_answer_link_list($dom) {
    $dom = $dom->getElementById('zh-profile-answer-list');
    $ret = array();
    if (empty($dom)) {
        echo "empty #zh-profile-answer-list\n";
        slog('empty #zh-profile-answer-list');
        return $ret;
    }
    foreach ($dom->getElementsByTagName('a') as $key => $node) {
        if ($attr = $node->getAttribute('class') == 'question_link') {
            $ret[$node->getAttribute('href')] = $node->nodeValue;
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

/**
 * http://www.zhihu.com/question/30586801/answer/49549164
 */
function get_answer($url) {
    list($code, $content) = zhihu_get($url);
    if ($code !== 200) {
        error_log("$url [$code]");
        return false;
    }
    $doc = loadHTML($content);
    $xpath = new DOMXPath($doc);
    $query = '//*[@id="zh-question-title"]/h2/a';
    $entries = $xpath->query($query);
    assert($entries->length === 1);
    $title = $entries->item(0)->nodeValue;
    $xpath = new DOMXPath($doc);
    $query = '//*[@id="zh-question-detail"]/div';
    $entries = $xpath->query($query);
    assert($entries->length === 1);
    $detail = $entries->item(0)->nodeValue;
    return [compact('title', 'detail'), $content];
}
function get_user_info($doc) {
    $xpath = new DOMXPath($doc);

    // We starts from the root element
    $query = '//*[@id="zh-pm-page-wrap"]/div[1]/div[1]/div[1]';//div[2]/a';
    // $query = '//*[@id="zh-pm-page-wrap"]/div[1]/div[1]/div[1]/div/a';
    $entries = $xpath->query($query);
    if ($entries->length !== 1) {
        throw new Exception("not find user name", 1);
    }
    $div_top = $entries->item(0);
    $length = $div_top->childNodes->length;
    assert($length === 3 || $length === 5);
    $divs = filter_by_class($div_top->childNodes, 'title-section ellipsis');
    assert(count($divs) === 1);
    $list = filter_by_class($divs[0]->childNodes, 'name');
    assert(count($list) === 1);
    $name = $list[0]->nodeValue;
    $list = filter_by_class($divs[0]->childNodes, 'bio');
    $desc = '';
    if ($list) {
        $desc = $list[0]->nodeValue;
    }
    return compact('name', 'desc');
}
function filter_by_class($list, $class)
{
    $ret = [];
    foreach ($list as $elem) {
        if ($elem instanceof DOMElement && $elem->getAttribute('class') === $class) {
            $ret[] = $elem;
        }
    }
    return $ret;
}

function xpath_query($doc, $query)
{
    $xpath = new DOMXPath($doc);
    // We starts from the root element
    return $entries = $xpath->query($query);
}
function get_question_info($content)
{
    $doc = loadHTML($content);
    $xpath = new DOMXPath($doc);
    $query = '//*[@id="zh-question-title"]/h2/text()';
    $entries = $xpath->query($query);
    assert($entries->length === 1);
    return ['title' => trim($entries->item(0)->nodeValue)];
}

function save_answer_to_db($info) {
    global $db;
    $db->upsert('answer', $info);
}
