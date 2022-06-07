<?php

namespace app\models\entry_node\lua;

use app\helpers\Utilities;
use app\models\base\FlowBase;
use DI\DependencyException as DiDependencyException;
use DI\NotFoundException as DiNotFoundException;
use JsonException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;

class LuaLog  extends FlowBase {

    protected Logger $logger;

    /**
     * @return Logger
     * @throws DiDependencyException
     * @throws DiNotFoundException
     */
    protected static function make_logger(): Logger
    {
        $log_settings = static::getContainer()->get('settings')->lau->logger;

        $logger = new Logger($log_settings->name);

        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $path = HEXLET_BASE_PATH. DIRECTORY_SEPARATOR. HEXBATCH_LOG_ROOT. DIRECTORY_SEPARATOR.$log_settings->file_name;
        $level = Logger::toMonologLevel($log_settings->level);

        $handler = new StreamHandler($path, $level); //for named pipe use stream too
        $logger->pushHandler($handler);
        return $logger;
    }

    /**
     * @throws DiDependencyException
     * @throws DiNotFoundException
     */
    public function __construct()  {
        parent::__construct();
        $this->logger = static::make_logger();
        $this->records = [];
    }

    /**
     * @var LuaLogRecord[]
     */
    protected array $records;

    /**
     * @return LuaLogRecord[]
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    public function clearRecords(): void { $this->records = [];}


    /**
     * @param $pre
     * @param $data
     * @return void
     * @throws JsonException
     */
    public function debug($pre,$data = null): void
    {
        $as_array = Utilities::convert_to_array($data);
        $this->logger->debug($pre,$as_array);
        $this->records[] = new LuaLogRecord(level: 'debug',pre:$pre,data:$data);
    }


    /**
     * @param $pre
     * @param $data
     * @return void
     * @throws JsonException
     */
    public function info($pre,$data = null): void
    {
        $as_array = Utilities::convert_to_array($data);
        $this->logger->info($pre,$as_array);
        $this->records[] = new LuaLogRecord(level: 'info',pre:$pre,data:$data);
    }

    /**
     * @param $pre
     * @param $data
     * @return void
     * @throws JsonException
     */
    public function warning($pre,$data = null): void
    {
        $as_array = Utilities::convert_to_array($data);
        $this->logger->warning($pre,$as_array);
        $this->records[] = new LuaLogRecord(level: 'warning',pre:$pre,data:$data);
    }

    /**
     * @param $pre
     * @param $data
     * @return void
     * @throws JsonException
     */
    public function error($pre,$data = null): void
    {
        $as_array = Utilities::convert_to_array($data);
        $this->logger->error($pre,$as_array);
        $this->records[] = new LuaLogRecord(level: 'error',pre:$pre,data:$data);
    }
}