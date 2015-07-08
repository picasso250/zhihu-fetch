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
    $ret = [];
    foreach ($answer_link_list as $url) {
        $a = get_answer($url);
        if ($a) {
            $ret[$url] = $a;
        }
    }
    return $ret;
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
    assert(count($entries) === 1);
    $title = $entries->item(0)->nodeValue;
    $xpath = new DOMXPath($doc);
    $query = '//*[@id="zh-question-detail"]/div';
    $entries = $xpath->query($query);
    assert(count($entries) === 1);
    $detail = $entries->item(0)->nodeValue;
    return [compact('title', 'detail'), $content];
}
function get_user_info($doc) {
    $xpath = new DOMXPath($doc);

    // We starts from the root element
    $query = '//*[@id="zh-pm-page-wrap"]/div[1]/div[1]/div[1]/div[2]/a';
    $entries = $xpath->query($query);
    assert(count($entries) === 1);
    $name = $entries->item(0)->nodeValue;

    $xpath = new DOMXPath($doc);
    $query = '//*[@id="zh-pm-page-wrap"]/div[1]/div[1]/div[1]/div[2]/span';
    $entries = $xpath->query($query);
    assert(count($entries) === 1);
    $desc = $entries->item(0)->nodeValue;
    return compact('name', 'desc');
}

function xpath_query($doc, $query)
{
    $xpath = new DOMXPath($doc);
    // We starts from the root element
    return $entries = $xpath->query($query);
}
