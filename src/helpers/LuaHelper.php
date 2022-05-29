<?php

namespace app\helpers;

use DI\DependencyException;
use DI\NotFoundException;
use LogicException;
use LuaSandbox;
use LuaSandboxRuntimeError;

class LuaHelper extends BaseHelper
{

    public static function get_lua_helper(): LuaHelper
    {
        try {
            return static::get_container()->get('luaHelper');
        } catch (DependencyException|NotFoundException $e) {
            throw new LogicException($e->getMessage());
        }
    }

    public function register_standard_libraries(LuaSandbox $sandbox) {
        $sandbox->registerLibrary('hexflow', [
            'log' => function ($string) {
                echo "$string\n";
            },
            'error' => function () {
                throw new LuaSandboxRuntimeError("Something is wrong");
            }
        ]);
    }


}