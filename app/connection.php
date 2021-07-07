<?php

declare(strict_types=1);

use DI\Container;
use ParagonIE\EasyDB\Exception\ConstructorFailed;
use ParagonIE\EasyDB\Exception\QueryError;
use ParagonIE\EasyDB\Factory;
use Psr\Log\LoggerInterface;

return function (Container $container) {
    $container->set('connection', function() use ($container) {
        $connection = $container->get('settings')->database;


        $db_host = $connection->host;
        $db_name = $connection->database;
        $charset = $connection->charset;
        $collation = $connection->collation;

        try {
            $hexlet_db = Factory::fromArray([
                "mysql:host=$db_host;dbname=$db_name;charset=$charset",
                $connection->user,
                $connection->password
            ]);

            $hexlet_db->safeQuery("set names $charset collate $collation");

            return $hexlet_db; // $hexlet_db->getPdo();
        } catch(ConstructorFailed $e) {
            $container->get(LoggerInterface::class)->alert('cannot connect to database',['exception'=>$e]);
        } catch(QueryError $e) {
            $container->get(LoggerInterface::class)->error('cannot set names to db',['exception'=>$e]);
        } catch(TypeError $e) {
            $container->get(LoggerInterface::class)->error('some sort of error with setting names to db',['exception'=>$e]);
        } catch (InvalidArgumentException $e) {
            $container->get(LoggerInterface::class)->error('some weird  error with setting names to db',['exception'=>$e]);
        }

        return null;
    });
};
