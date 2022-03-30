<?php

namespace Hexlet_migrations;

use app\hexlet\DBSelector;
use app\hexlet\MYDB;
use app\hexlet\RecursiveClasses;
use Phoenix\Migration\AbstractMigration;
use RuntimeException;

/** @noinspection PhpUnused */
class AddFlowGroupAttributes extends AbstractMigration
{
    //202203128077010_add_flow_tag_standard_attributes.php
    //triggers_202203128077010_flow_tag_standard_attributes
    const NAME_PART = '202203128077010';
    const TRIGGER_DIR = HEXLET_BASE_PATH . '/database/hexlet_migrations/triggers/triggers_' . self::NAME_PART . '_flow_tag_standard_attributes';

    protected function up(): void
    {

        $mydb = DBSelector::getConnection();

        if (!is_dir(static::TRIGGER_DIR)) {
            throw new RuntimeException("Trigger dir of " . static::TRIGGER_DIR . " does not exist");
        }

        $mydb->dropTriggersLike(static::NAME_PART);

        $this->execute("

            CREATE TABLE flow_tag_standard_attributes (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `flow_tag_id` INT NULL DEFAULT NULL ,
                `inherited_created_at` DATETIME NOT NULL DEFAULT current_timestamp,
                `inherited_updated_at` TIMESTAMP DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                inherited_attribute_guid BINARY(16) DEFAULT NULL ,
               `tag_attribute_name` VARCHAR(40) NULL DEFAULT NULL ,
                `tag_attribute_json` JSON NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY idx_tag_id (flow_tag_id),
                UNIQUE KEY udx_tag_attribute (flow_tag_id,tag_attribute_name)
           ) ENGINE = InnoDB COMMENT = 'Keeps a record of standard attributes as they stack up in a tag ';

       ");

        $this->execute("ALTER TABLE flow_tag_standard_attributes
            ADD CONSTRAINT `fk_flow_tag_standard_attributes_has_tag_id` 
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

        $this->execute("ALTER TABLE flow_tag_standard_attributes DROP FOREIGN KEY `fk_flow_tag_standard_attributes_has_tag_id`");
        $this->table('flow_tag_standard_attributes')->drop();
    }
}
