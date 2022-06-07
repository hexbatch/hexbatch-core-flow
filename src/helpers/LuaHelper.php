<?php

namespace app\helpers;

use app\models\entry_node\lua\LuaLog;
use app\models\entry_node\lua\LuaLogRecord;
use app\models\user\IFlowUserAuth;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use LogicException;
use LuaSandbox;
use LuaSandboxRuntimeError;
use Monolog\Logger;

class LuaHelper extends BaseHelper
{

    protected LuaLog $luaLog;


    public function __construct(IFlowUserAuth $auth, Logger $logger, Container $container)
    {
        parent::__construct($auth,$logger,$container);
        $this->luaLog = new LuaLog();
    }


    public static function get_lua_helper(): LuaHelper
    {
        try {
            return static::get_container()->get('luaHelper');
        } catch (DependencyException|NotFoundException $e) {
            throw new LogicException($e->getMessage());
        }
    }

    public function register_standard_libraries(LuaSandbox $sandbox) {
        $this->luaLog->clearRecords();

        $sandbox->registerLibrary('hexflow', [
            'log_debug' => function ($string,$what = null) {
                $this->luaLog->debug($string,$what);
            },
            'log_info' => function ($string,$what = null) {
                $this->luaLog->info($string,$what);
            },
            'log_warning' => function ($string,$what = null) {
                $this->luaLog->warning($string,$what);
            },
            'log_error' => function ($string,$what = null) {
                $this->luaLog->error($string,$what);
            },
            'error' => function ($what) {
                throw new LuaSandboxRuntimeError($what);
            }
        ]);
    }

    /**
     * @return LuaLogRecord[]
     */
    public function get_lua_log_records(): array
    { return $this->luaLog->getRecords();}


}