<?php

namespace model;

use DB;
use Exception;

class Question extends Base
{
    public static function getTable()
    {
        return 'question';
    }

    public static function setFetched($qid)
    {
        $where = array('id' => $qid);
        $set = array('fetch' => self::FETCH_OK);
        return DB::update(self::getTable(), $set, $where);
    }

    public static function saveQuestion($qid, $question, $description)
    {
        $where = array('id' => $qid);
        $set = array('title' => $question, 'description' => $description);
        return DB::upsert(self::getTable(), $set, $where);
    }
    
    public static function getIds()
    {
        $where = array('fetch' => 0);
        $c = DB::find(self::getTable(), $where)->fields(array('id'))->queryAll();

        $ret = array();
        foreach ($c as $v) {
            $ret[] = $v['id'];
        }
        return $ret;
    }
}
