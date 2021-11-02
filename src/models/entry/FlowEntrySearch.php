<?php

namespace app\models\entry;

class FlowEntrySearch {



    /**
     * @param ?FlowEntrySearchParams $params
     * @return IFlowEntry[]
     */
   public static function search(?FlowEntrySearchParams $params): array {
       if (empty($params)) {return [];}


       $ret = [];

       return $ret;
   }
}