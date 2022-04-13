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

    public function is_empty() : bool {
        $ignore_non_empty = ['page','page_size'];
        foreach ($this as $key => $value) {
            if (in_array($key,$ignore_non_empty)) { continue;}
            if (!empty($value)) { return false;}
        }
        return true;
    }


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


    protected int     $page = 1;
    protected int     $page_size =  self::DEFAULT_PAGE_SIZE;

    public function getPage() :int  {
        return $this->page;
    }
    public function getPageSize() :int  {return $this->page_size;}

    public function setPage(int $what) {
        $this->page = intval($what);
        if ($this->page < 1) {$this->page = 1;}
    }

    public function setPageSize(int $what) {
        $this->page_size = intval($what);
        if ($this->page_size < 1) { $this->page_size = 1;}
        if ($this->page_size === SearchParamBase::UNLIMITED_RESULTS_PER_PAGE) {
            $this->page = 1;
        }
    }

    public function __construct()
    {
        $this->page = 1;
        $this->page_size = static::DEFAULT_PAGE_SIZE;
    }


}