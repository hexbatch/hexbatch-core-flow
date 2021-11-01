<?php

namespace app\models\entry;

class FlowEntrySearch {



    /**
     * @param ?FlowEntrySearchParams $params
     * @return FlowEntry[]
     */
   public static function search(?FlowEntrySearchParams $params): array {
       if (empty($params)) {return [];}
       $params->page = intval($params->page);
       $params->page_size = intval($params->page_size);
       if ($params->page < 1) {$params->page = 1;}
       if ($params->page_size < 1) { $params->page_size = 1;}

       $ret = [];

       return $ret;
   }
}