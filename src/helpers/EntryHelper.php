<?php

namespace app\helpers;

use app\controllers\entry\EntryNodes;
use DI\DependencyException;
use DI\NotFoundException;
use LogicException;

class EntryHelper extends BaseHelper {


    public static function get_entry_helper() : EntryHelper {
        try {
            return static::get_container()->get('entryHelper');
        } catch (DependencyException|NotFoundException $e) {
            throw new LogicException($e->getMessage());
        }

    }

    public static function get_entry_nodes() : EntryNodes {
        try {
            return static::get_container()->get('entryNodes');
        } catch (DependencyException|NotFoundException $e) {
            throw new LogicException($e->getMessage());
        }

    }
}