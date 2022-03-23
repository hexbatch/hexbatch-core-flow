<?php

namespace Hexlet_migrations;

use app\hexlet\MYDB;
use Phoenix\Migration\AbstractMigration;
use RuntimeException;
use app\hexlet\DBSelector;
use app\hexlet\RecursiveClasses;

/** @noinspection PhpUnused */
class AddFlowAppliedTags extends AbstractMigration
{

    //triggers_20210709000009_flow_applied_tags
    const NAME_PART = '20210709000009';
    const TRIGGER_DIR = HEXLET_BASE_PATH . '/database/hexlet_migrations/triggers/triggers_'.self::NAME_PART.'_flow_applied_tags';

    protected function up(): void
    {
        $mydb = DBSelector::getConnection();

        if ( ! is_dir( static::TRIGGER_DIR ) ) {
            throw new RuntimeException( "Trigger dir of " . static::TRIGGER_DIR . " does not exist" );
        }

        $mydb->dropTriggersLike( static::NAME_PART );

        $this->execute("

            CREATE TABLE flow_applied_tags (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `flow_tag_id` INT NOT NULL , 
                `tagged_flow_entry_id` INT NULL DEFAULT NULL,
                `tagged_flow_user_id` INT NULL DEFAULT NULL ,
                `tagged_flow_project_id` INT NULL DEFAULT NULL ,
                `created_at_ts` INT NULL DEFAULT NULL,
                `flow_applied_tag_guid` BINARY(16) NOT NULL ,
                PRIMARY KEY (`id`)
           ) ENGINE = InnoDB COMMENT = 'allows applying the tag to something, can only be one thing a row';

       ");

        $this->execute("ALTER TABLE `flow_applied_tags` ADD INDEX `idx_flow_tag_id`   (`flow_tag_id`);");

        $this->execute("ALTER TABLE `flow_applied_tags` ADD INDEX `idx_tagged_flow_entry_id`   (`tagged_flow_entry_id`);");
        $this->execute("ALTER TABLE `flow_applied_tags` ADD INDEX `idx_tagged_flow_user_id`   (`tagged_flow_user_id`);");
        $this->execute("ALTER TABLE `flow_applied_tags` ADD INDEX `idx_tagged_flow_project_id`   (`tagged_flow_project_id`);");

        $this->execute("ALTER TABLE `flow_applied_tags` ADD UNIQUE `udx_tagged_flow_entry_id`   (`flow_tag_id`,`tagged_flow_entry_id`);");
        $this->execute("ALTER TABLE `flow_applied_tags` ADD UNIQUE `udx_tagged_flow_user_id`   (`flow_tag_id`,`tagged_flow_user_id`);");
        $this->execute("ALTER TABLE `flow_applied_tags` ADD UNIQUE `udx_tagged_flow_project_id`   (`flow_tag_id`,`tagged_flow_project_id`);");


        $this->execute("ALTER TABLE `flow_applied_tags` ADD UNIQUE `udx_flow_applied_tag_guid` (`flow_applied_tag_guid`);");



        $this->execute("ALTER TABLE `flow_applied_tags` ADD CONSTRAINT `fk_flow_applied_tags_has_tag_id` 
            FOREIGN KEY (`flow_tag_id`) REFERENCES `flow_tags`(`id`) ON DELETE CASCADE ON UPDATE CASCADE ;");


        $this->execute("ALTER TABLE `flow_applied_tags` ADD CONSTRAINT `fk_flow_applied_tags_has_tagged_entry_id` 
            FOREIGN KEY (`tagged_flow_entry_id`) REFERENCES `flow_entries`(`id`) ON DELETE CASCADE ON UPDATE CASCADE ;");

        $this->execute("ALTER TABLE `flow_applied_tags` ADD CONSTRAINT `fk_flow_applied_tags_has_tagged_project_id` 
            FOREIGN KEY (`tagged_flow_project_id`) REFERENCES `flow_projects`(`id`) ON DELETE CASCADE ON UPDATE CASCADE ;");

        $this->execute("ALTER TABLE `flow_applied_tags` ADD CONSTRAINT `fk_flow_applied_tags_has_tagged_flow_user_id` 
            FOREIGN KEY (`tagged_flow_user_id`) REFERENCES `flow_users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE ;");




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

        $this->execute("ALTER TABLE `flow_applied_tags` DROP FOREIGN KEY `fk_flow_applied_tags_has_tagged_flow_user_id`");
        $this->execute("ALTER TABLE `flow_applied_tags` DROP FOREIGN KEY `fk_flow_applied_tags_has_tagged_project_id`");
        $this->execute("ALTER TABLE `flow_applied_tags` DROP FOREIGN KEY `fk_flow_applied_tags_has_tagged_entry_id`");
        $this->execute("ALTER TABLE `flow_applied_tags` DROP FOREIGN KEY `fk_flow_applied_tags_has_tag_id`");
        $this->table('flow_applied_tags')->drop();
    }
}
