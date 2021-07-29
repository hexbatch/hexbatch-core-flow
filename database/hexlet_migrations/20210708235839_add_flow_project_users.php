<?php

namespace Hexlet_migrations;

use app\hexlet\MYDB;
use Phoenix\Migration\AbstractMigration;
use RuntimeException;
use app\hexlet\DBSelector;
use app\hexlet\RecursiveClasses;

/** @noinspection PhpUnused */
class AddFlowProjectUsers extends AbstractMigration
{
    //triggers_20210708235839_flow_project_users
    const NAME_PART = '20210708235839';
    const TRIGGER_DIR = HEXLET_BASE_PATH . '/database/hexlet_migrations/triggers/triggers_'.self::NAME_PART.'_flow_project_users';

    protected function up(): void
    {
        $mydb = DBSelector::getConnection();

        if ( ! is_dir( static::TRIGGER_DIR ) ) {
            throw new RuntimeException( "Trigger dir of " . static::TRIGGER_DIR . " does not exist" );
        }

        $mydb->dropTriggersLike( static::NAME_PART );

        $this->execute("

            CREATE TABLE flow_project_users (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `flow_project_id` INT NOT NULL , 
                `flow_user_id` INT  NOT NULL ,
                `can_write` TINYINT NOT NULL DEFAULT 0 ,
                `can_read` TINYINT NOT NULL DEFAULT 0 ,
                `created_at_ts` INT NULL DEFAULT NULL,
                `flow_project_user_guid` BINARY(16) NOT NULL ,
                PRIMARY KEY (`id`)
           ) ENGINE = InnoDB COMMENT = 'sets optional permissions for a project, if none set then all can read and owner can write';

       ");

        $this->execute("ALTER TABLE `flow_project_users` ADD INDEX `idx_flow_project_id`   (`flow_project_id`);");
        $this->execute("ALTER TABLE `flow_project_users` ADD INDEX `idx_flow_user_id`   (`flow_user_id`);");
        $this->execute("ALTER TABLE `flow_project_users` ADD INDEX `udx_flow_user_project`   (`flow_user_id`,`flow_project_id`);");
        $this->execute("ALTER TABLE `flow_project_users` ADD INDEX `idx_can_read`   (`can_read`);");
        $this->execute("ALTER TABLE `flow_project_users` ADD INDEX `idx_can_write`   (`can_write`);");
        $this->execute("ALTER TABLE `flow_project_users` ADD UNIQUE `udx_flow_entry_guid` (`flow_project_user_guid`);");


        $this->execute("ALTER TABLE `flow_project_users` ADD CONSTRAINT `fk_flow_project_user_has_project_id` 
            FOREIGN KEY (`flow_project_id`) REFERENCES `flow_projects`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT ;");

        $this->execute("ALTER TABLE `flow_project_users` ADD CONSTRAINT `fk_flow_project_user_has_flow_user_id` 
            FOREIGN KEY (`flow_user_id`) REFERENCES `flow_users`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT ;");

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

        $this->execute("ALTER TABLE `flow_project_users` DROP FOREIGN KEY `fk_flow_project_user_has_flow_user_id`");
        $this->execute("ALTER TABLE `flow_project_users` DROP FOREIGN KEY `fk_flow_project_user_has_project_id`");
        $this->table('flow_project_users')->drop();
    }
}
