<?php

namespace app\helpers;

use app\hexlet\DBSelector;
use app\hexlet\JsonHelper;
use app\hexlet\MYDB;
use app\models\tag\FlowTag;
use app\models\tag\FlowTagSearchParams;
use Exception;
use Psr\Http\Message\ServerRequestInterface;

class SQLHelper {
    /**
     * @param bool $b_dry_run
     * @return string[]
     */
    public static function redo_all_triggers(bool $b_dry_run = false) : array {
        $ret = [];
        $mydb = DBSelector::getConnection();
        $mydb->dropTriggersLike('%',$b_dry_run);


        $trigger_dor = HEXLET_BASE_PATH . '/database/hexlet_migrations/triggers';
        $files = MYDB::recursive_search_sql_files($trigger_dor);

        $found_files = [];
        foreach ($files as $file) {
            $sql = trim(file_get_contents($file));
            if (empty($sql)) {continue;}
            $found_files[] = $file;
            if ($b_dry_run) {
                continue;
            }
            $mydb->execute($sql);
        }
        $ret['found_files'] = $found_files;
        $ret['triggers'] = $mydb->dropTriggersLike('%',true); //just to get the names
        return $ret;
    }

    public static function refresh_flow_things_except_css() :int  {
        $sql_array = [
            'SET @trigger_refresh_things := 1;',
            'UPDATE flow_users u SET u.created_at_ts = u.created_at_ts + 1 WHERE 1;',
            'UPDATE flow_projects u SET u.created_at_ts = u.created_at_ts + 1 WHERE 1;',
            'UPDATE flow_tags u SET u.created_at_ts = u.created_at_ts + 1 WHERE 1;',
            'UPDATE flow_entries u SET u.created_at_ts = u.created_at_ts + 1 WHERE 1;',
            'UPDATE flow_project_users u SET u.created_at_ts = u.created_at_ts + 1 WHERE 1;',
            'UPDATE flow_tags u SET u.created_at_ts = u.created_at_ts + 1 WHERE 1;',
            'UPDATE flow_applied_tags u SET u.created_at_ts = u.created_at_ts + 1 WHERE 1;',
            'SET @trigger_refresh_things := 0;',
        ];

        $mydb = DBSelector::getConnection();
        $count = 0;
        foreach ($sql_array as $sql) {
            $count+= $mydb->execSQL($sql,null,MYDB::ROWS_AFFECTED);
        }
        return $count;
    }

    /**
     * @return array
     * @throws Exception
     */
    public static function refresh_flow_things_css() : array {
        $ret = [];
        $search_params = new FlowTagSearchParams();
        $all_tags = FlowTag::get_tags($search_params, 1,1000000);
        foreach ($all_tags as $tag) {
           $tag->update_flow_things_with_css();
        }
        return $ret;
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     * @noinspection PhpUnused
     * @throws Exception
     */
    public static function refresh_all_flow_things(ServerRequestInterface $request) :array {

        $args = $request->getQueryParams();
        $data = [];
        if (isset($args['triggers']) && JsonHelper::var_to_boolean($args['triggers'])) {
            $data['triggers'] = SQLHelper::redo_all_triggers();
        }

        if (isset($args['refresh']) && JsonHelper::var_to_boolean($args['refresh'])) {
            $data['update_count'] = SQLHelper::refresh_flow_things_except_css();
        }

        if (isset($args['css']) && JsonHelper::var_to_boolean($args['css'])) {
            $data['css'] = SQLHelper::refresh_flow_things_css();
        }

       return $data;
    }
}