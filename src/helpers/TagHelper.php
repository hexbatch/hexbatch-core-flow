<?php

namespace app\helpers;

use DI\DependencyException;
use DI\NotFoundException;
use LogicException;

class TagHelper extends BaseHelper
{
    public static function get_tag_helper(): TagHelper
    {
        try {
            return static::get_container()->get('tagHelper');
        } catch (DependencyException|NotFoundException $e) {
            throw new LogicException($e->getMessage());
        }
    }


}