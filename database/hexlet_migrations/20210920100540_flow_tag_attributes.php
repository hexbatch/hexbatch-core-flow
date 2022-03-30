<?php

namespace Hexlet_migrations;

use app\hexlet\MYDB;
use Phoenix\Migration\AbstractMigration;
use RuntimeException;
use app\hexlet\DBSelector;
use app\hexlet\RecursiveClasses;

/** @noinspection PhpUnused */

class FlowTagAttributes extends AbstractMigration
{
    //triggers_20210920100540_flow_tag_attributes
    const NAME_PART = '20210920100540';
    const TRIGGER_DIR = HEXLET_BASE_PATH . '/database/hexlet_migrations/triggers/triggers_' . self::NAME_PART . '_flow_tag_attributes';

    protected function up(): void
    {
        $mydb = DBSelector::getConnection();

        if (!is_dir(static::TRIGGER_DIR)) {
            throw new RuntimeException("Trigger dir of " . static::TRIGGER_DIR . " does not exist");
        }

        $mydb->dropTriggersLike(static::NAME_PART);

        $this->execute("

            CREATE TABLE flow_tag_attributes (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `flow_tag_id` INT NULL DEFAULT NULL ,
                `created_at_ts` INT NULL DEFAULT NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `points_to_entry_id` INT NULL DEFAULT NULL,
                `points_to_user_id` INT NULL DEFAULT NULL ,
                `points_to_project_id` INT  NULL DEFAULT NULL , 
                `flow_tag_attribute_guid` BINARY(16) NOT NULL ,
                `tag_attribute_name` VARCHAR(40) NULL DEFAULT NULL ,
                `tag_attribute_long` BIGINT NULL DEFAULT NULL,
                `tag_attribute_text` LONGTEXT NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
           ) ENGINE = InnoDB COMMENT = 'Defines the attributes of tags and when they are applied';

       ");

        $this->execute("ALTER TABLE `flow_tag_attributes` ADD INDEX `idx_flow_tag_id`  (`flow_tag_id`);");
        $this->execute("ALTER TABLE `flow_tag_attributes` ADD UNIQUE `udx_flow_tag_guid` (`flow_tag_attribute_guid`);");
        $this->execute("ALTER TABLE `flow_tag_attributes` ADD FULLTEXT `ft_flow_tag_name` (`tag_attribute_name`);");
        $this->execute("ALTER TABLE `flow_tag_attributes` ADD INDEX `idx_points_to_entry_id`   (points_to_entry_id);");
        $this->execute("ALTER TABLE `flow_tag_attributes` ADD INDEX `idx_points_to_user_id`   (`points_to_user_id`);");
        $this->execute("ALTER TABLE `flow_tag_attributes` ADD INDEX `idx_points_to_project_id`   (`points_to_project_id`);");
        $this->execute("ALTER TABLE `flow_tag_attributes` ADD FULLTEXT `ft_tag_attribute_text` (`tag_attribute_text`);");


        $this->execute("ALTER TABLE `flow_tag_attributes` ADD CONSTRAINT `fk_flow_tag_attributes_has_pointee_entry_id` 
            FOREIGN KEY (points_to_entry_id) REFERENCES `flow_entries`(`id`) ON DELETE SET NULL ON UPDATE CASCADE ;");

        $this->execute("ALTER TABLE `flow_tag_attributes` ADD CONSTRAINT `fk_flow_tag_attributes_has_pointee_project_id` 
            FOREIGN KEY (`points_to_project_id`) REFERENCES `flow_projects`(`id`) ON DELETE SET NULL ON UPDATE CASCADE ;");

        $this->execute("ALTER TABLE `flow_tag_attributes` ADD CONSTRAINT `fk_flow_tag_attributes_has_pointee_flow_user_id` 
            FOREIGN KEY (`points_to_user_id`) REFERENCES `flow_users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE ;");

        $this->execute("ALTER TABLE `flow_tag_attributes` ADD CONSTRAINT `fk_flow_tag_attributes_has_tag_id` 
            FOREIGN KEY (`flow_tag_id`) REFERENCES `flow_tags`(`id`) ON DELETE CASCADE ON UPDATE CASCADE ;");



        //NOW UPDATE THE TRIGGERS !
        $files = MYDB::recursive_search_sql_files(static::TRIGGER_DIR);

        foreach ($files as $file) {
            $sql = trim(file_get_contents($file));
            if (empty($sql)) {
                continue;
            }
            $this->execute($sql);
        }

    }

    protected function down(): void
    {
        $mydb = DBSelector::getConnection();

        if (RecursiveClasses::constant_value($this, 'NAME_PART')) {
            $mydb->dropTriggersLike(static::NAME_PART);
        }

        $this->execute("ALTER TABLE `flow_tag_attributes` DROP FOREIGN KEY `fk_flow_tag_attributes_has_tag_id`");
        $this->execute("ALTER TABLE `flow_tag_attributes` DROP FOREIGN KEY `fk_flow_tag_attributes_has_pointee_flow_user_id`");
        $this->execute("ALTER TABLE `flow_tag_attributes` DROP FOREIGN KEY `fk_flow_tag_attributes_has_pointee_project_id`");
        $this->execute("ALTER TABLE `flow_tag_attributes` DROP FOREIGN KEY `fk_flow_tag_attributes_has_pointee_entry_id`");
        $this->table('flow_tag_attributes')->drop();
    }
}

