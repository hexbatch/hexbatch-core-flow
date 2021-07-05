<?php

require_once 'flow-config.php';

$what =  [
    'log_table_name' => 'db_flow_migration_log',
    'migration_dirs' => [
        'first' => __DIR__ . '/database/hexlet_migrations',
    ],
    'environments' => [
        'local' => [
            'adapter' => 'mysql',
            'host' => DB_FLOW_HOST,
            'port' => DB_FLOW_HOSTPORT,
            'username' => DB_FLOW_USER,
            'password' => DB_FLOW_PASSWORD,
            'db_name' => DB_FLOW_DATABASE,
            'charset' => DB_FLOW_CHARSET,
            'collation' => DB_FLOW_COLLATION
        ]
    ],
    'default_environment ' => 'local',
];

return $what;