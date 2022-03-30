<?php

namespace app\helpers;

use app\hexlet\DBSelector;
use app\hexlet\MYDB;
use app\models\base\SearchParamBase;
use app\models\standard\StandardAttributeWrite;
use app\models\tag\FlowTagSearch;
use app\models\tag\FlowTagSearchParams;
use Exception;

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

    public static function truncate_flow_things() :int  {
        $sql_array = [
            'TRUNCATE TABLE flow_things;',
        ];

        $mydb = DBSelector::getConnection();
        $count = 0;
        foreach ($sql_array as $sql) {
            $count+= $mydb->execSQL($sql,null,MYDB::ROWS_AFFECTED);
        }
        return $count;
    }

    /**
     * @return int
     * @throws Exception
     */
    public static function refresh_flow_things() : int {
        $search_params = new FlowTagSearchParams();
        $search_params->setPage(1);
        $search_params->setPageSize(SearchParamBase::UNLIMITED_RESULTS_PER_PAGE);
        $all_tags = FlowTagSearch::get_tags($search_params);
        $writer = new StandardAttributeWrite($all_tags);
        $ret = $writer->write();
        return $ret;
    }

}