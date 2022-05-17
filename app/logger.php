<?php /** @noinspection PhpUnused */

declare(strict_types=1);

use DI\Container;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;

const HEXBATCH_LOG_ROOT = 'storage/logs';
return function (Container $container) {
    $container->set(LoggerInterface::class, function (ContainerInterface $container) {
        $settings = $container->get('settings')->logger;

        $logger = new Logger($settings->name);

        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $path = HEXLET_BASE_PATH. DIRECTORY_SEPARATOR. HEXBATCH_LOG_ROOT. DIRECTORY_SEPARATOR.$settings->file_name;
        $level = Logger::toMonologLevel($settings->level);

        $handler = new StreamHandler($path, $level); //for named pipe use stream too
        $logger->pushHandler($handler);

        return $logger;
    });
};
