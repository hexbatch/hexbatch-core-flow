<?php

use app\hexlet\DBSelector;
use Symfony\Component\Yaml\Yaml;

require_once 'top-constants.php';
require_once 'config/flow-config-paths.php';
require_once HEXLET_BASE_PATH . '/vendor/autoload.php';
$db = Yaml::parseFile(HEXLET_BASE_PATH . '/config/database.yaml',Yaml::PARSE_OBJECT_FOR_MAP)->database;

$what =  [
    'log_table_name' => 'db_flow_migration_log',
    'migration_dirs' => [
        'first' => __DIR__ . '/database/hexlet_migrations',
    ],
    'environments' => [
        'local' => [
            'adapter' => 'mysql',
            'host' => $db->host,
            'port' => $db->hostport,
            'username' => $db->user,
            'password' => $db->password,
            'db_name' => $db->database,
            'charset' => $db->charset,
            'collation' => $db->collation
        ]
    ],
    'default_environment ' => 'local',
];

return $what;