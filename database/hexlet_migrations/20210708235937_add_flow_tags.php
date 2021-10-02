<?php

namespace Hexlet_migrations;

use app\hexlet\MYDB;
use Phoenix\Migration\AbstractMigration;
use RuntimeException;
use app\hexlet\DBSelector;
use app\hexlet\RecursiveClasses;

/** @noinspection PhpUnused */
class AddFlowTags extends AbstractMigration
{
    //triggers_20210708235937_flow_tags
    const NAME_PART = '20210708235937';
    const TRIGGER_DIR = HEXLET_BASE_PATH . '/database/hexlet_migrations/triggers/triggers_'.self::NAME_PART.'_flow_tags';

    protected function up(): void
    {
        $mydb = DBSelector::getConnection();

        if ( ! is_dir( static::TRIGGER_DIR ) ) {
            throw new RuntimeException( "Trigger dir of " . static::TRIGGER_DIR . " does not exist" );
        }

        $mydb->dropTriggersLike( static::NAME_PART );

        $this->execute("

            CREATE TABLE flow_tags (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `flow_project_id` INT NOT NULL , 
                `parent_tag_id` INT NULL DEFAULT NULL,
                `created_at_ts` INT NULL DEFAULT NULL,
                `flow_tag_guid` BINARY(16) NOT NULL ,
                `tag_effect` VARCHAR(15) NULL DEFAULT NULL ,
                `flow_tag_name` VARCHAR(40) NULL DEFAULT NULL ,
                PRIMARY KEY (`id`)
           ) ENGINE = InnoDB COMMENT = 'Defines the tags used everywhere';

       ");

        $this->execute("ALTER TABLE `flow_tags` ADD INDEX `idx_flow_project_id`   (`flow_project_id`);");
        $this->execute("ALTER TABLE `flow_tags` ADD INDEX `idx_parent_tag_id`   (`parent_tag_id`);");
        $this->execute("ALTER TABLE `flow_tags` ADD UNIQUE `udx_flow_tag_guid` (`flow_tag_guid`);");
        $this->execute("ALTER TABLE `flow_tags` ADD FULLTEXT `ft_flow_tag_name` (`flow_tag_name`);");


        $this->execute("ALTER TABLE `flow_tags` ADD CONSTRAINT `fk_flow_tags_has_project_id` 
            FOREIGN KEY (`flow_project_id`) REFERENCES `flow_projects`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT ;");

        $this->execute("ALTER TABLE `flow_tags` ADD CONSTRAINT `fk_flow_tags_has_parent_id` 
            FOREIGN KEY (`parent_tag_id`) REFERENCES `flow_tags`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT ;");

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

        $this->execute("ALTER TABLE `flow_tags` DROP FOREIGN KEY `fk_flow_tags_has_parent_id`");
        $this->execute("ALTER TABLE `flow_tags` DROP FOREIGN KEY `fk_flow_tags_has_project_id`");
        $this->table('flow_tags')->drop();
    }
}
