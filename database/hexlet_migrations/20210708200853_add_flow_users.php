<?php

namespace Hexlet_migrations;

use app\hexlet\MYDB;
use Phoenix\Migration\AbstractMigration;
use RuntimeException;
use app\hexlet\DBSelector;
use app\hexlet\RecursiveClasses;

/** @noinspection PhpUnused */
class AddFlowUsers extends AbstractMigration
{
    //triggers_20210708200853_flow_users
    const NAME_PART = '20210708200853';
    const TRIGGER_DIR = HEXLET_BASE_PATH . '/database/hexlet_migrations/triggers/triggers_'.self::NAME_PART.'_flow_users';

    protected function up(): void
    {
        $mydb = DBSelector::getConnection();

        if ( ! is_dir( static::TRIGGER_DIR ) ) {
            throw new RuntimeException( "Trigger dir of " . static::TRIGGER_DIR . " does not exist" );
        }

        $mydb->dropTriggersLike( static::NAME_PART );


        $this->execute("

            CREATE TABLE flow_users (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `base_user_id` INT UNSIGNED NOT NULL , 
                `created_at_ts` INT NULL DEFAULT NULL,
                `last_logged_in_page_ts` INT NULL DEFAULT NULL,
                `flow_user_guid` BINARY(16) NOT NULL ,
                `flow_user_name` VARCHAR(39) NULL DEFAULT NULL ,
                `flow_user_email` varchar(100) NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
           ) ENGINE = InnoDB COMMENT = 'defines the users for a flow, extends separate auth system';

       ");

        $this->execute("ALTER TABLE `flow_users` ADD UNIQUE `udx_base_user_id`   (`base_user_id`);");
        $this->execute("ALTER TABLE `flow_users` ADD UNIQUE `udx_flow_user_guid` (`flow_user_guid`);");
        $this->execute("ALTER TABLE `flow_users` ADD UNIQUE `udx_flow_user_name` (`flow_user_name`);");

        $this->execute("ALTER TABLE `flow_users` ADD CONSTRAINT `fk_flow_users_has_user_id` 
            FOREIGN KEY (`base_user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;");


        //NOW UPDATE THE TRIGGERS !
        $files = MYDB::recursive_search_sql_files(static::TRIGGER_DIR);

        foreach ($files as $file) {
            $sql = trim(file_get_contents($file));
            if (empty($sql)) {continue;}
            $this->execute($sql);
        }


    }

    protected function down(): void
    {
        $mydb = DBSelector::getConnection();

        if (RecursiveClasses::constant_value($this,'NAME_PART')) {
            $mydb->dropTriggersLike( static::NAME_PART );
        }

        $this->execute("ALTER TABLE `flow_users` DROP FOREIGN KEY `fk_flow_users_has_user_id`");

        $this->table('flow_users')->drop();
    }
}
