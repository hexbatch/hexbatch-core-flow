<?php

namespace app\models\base;

use app\hexlet\WillFunctions;

class SearchParamBase {

    const UNLIMITED_RESULTS_PER_PAGE = 100000;
    const DEFAULT_PAGE_SIZE = 20;

    const ARG_IS_INT = 'arg-is-int';
    const ARG_IS_HEX = 'arg-is-hex';
    const ARG_IS_NAME = 'arg-is-string';
    const ARG_IS_EMAIL = 'arg-is-email';
    const ARG_IS_INVALID = 'arg-is-invalid';

    public static function find_type_of_arg($what) : string {
        if (is_string($what) && trim($what)) {
            if (ctype_digit($what) && (intval($what) < (PHP_INT_MAX/2))) {
                $n_thing = (int)$what;
                if ($n_thing >= 1) { return static::ARG_IS_INT;}
                else {return static::ARG_IS_INVALID;}
            } else {
                if (WillFunctions::is_valid_guid_format($what)) {
                    return static::ARG_IS_HEX;
                } else if (mb_strpos($what,'@') !== false) {
                    return static::ARG_IS_EMAIL;
                } else if(FlowBase::check_valid_title($what)){
                    return static::ARG_IS_NAME;
                } else {
                    return static::ARG_IS_INVALID;
                }
            }
        } elseif (is_int($what) && $what) {
            return static::ARG_IS_INT;
        }
        return static::ARG_IS_INVALID;
    }
}