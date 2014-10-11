<?php

namespace model;

use DB;
use Exception;

// logic
class User extends Base
{
    public static function getTable()
    {
        return ('user');
    }

    public static function saveUser($username, $nickname)
    {
        $u = self::getTable();
        $update = array('name' => $username, 'nick_name' => $nickname);
        $where = array('name' => $username);
        $rs = DB::upsert($u, $update, $where);
        if ($rs['updatedExisting']) {
            echo "\tupdatedExisting";
        }
        echo "\n";
        if (!$rs['ok']) {
            echo basename(__FILE__).':'.__LINE__.' '.$rs['err']."\n";
        }
        
        return $rs['updatedExisting'];
    }

    public static function getNotFetchedUserCount()
    {
        $u = self::getTable();
        $where = array(
            'fetch' => 0
        );
        return Db::find($u, $where)->count();
    }
    
    public static function getNotFetchedUserName()
    {
        $u = self::getTable();
        $where = array(
            'fetch' => 0
        );
        $c = Db::find($u, $where)->fields(array('name'))->limit(1)->queryAll();
        foreach ($c as $v) {
            return $v['name'];
        }
        return false;
    }
    public static function updateByUserName($username, $args)
    {
        if (empty($args)) {
            return true;
        }
        $u = self::getTable();
        $rs = DB::upsert($u, $args, array("name" => $username));
        return $rs;
    }

}
