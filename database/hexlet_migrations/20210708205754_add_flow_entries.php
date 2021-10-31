<?php

namespace Hexlet_migrations;

use app\hexlet\MYDB;
use Phoenix\Migration\AbstractMigration;
use RuntimeException;
use app\hexlet\DBSelector;
use app\hexlet\RecursiveClasses;

/** @noinspection PhpUnused */
class AddFlowEntries extends AbstractMigration
{
    //triggers_20210708205754_flow_entries
    const NAME_PART = '20210708205754';
    const TRIGGER_DIR = HEXLET_BASE_PATH . '/database/hexlet_migrations/triggers/triggers_'.self::NAME_PART.'_flow_entries';


    protected function up(): void
    {
        $mydb = DBSelector::getConnection();

        if ( ! is_dir( static::TRIGGER_DIR ) ) {
            throw new RuntimeException( "Trigger dir of " . static::TRIGGER_DIR . " does not exist" );
        }

        $mydb->dropTriggersLike( static::NAME_PART );

        $this->execute("

            CREATE TABLE flow_entries (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `flow_project_id` INT  NOT NULL , 
                `parent_flow_entry_id` INT NULL DEFAULT NULL ,
                `created_at_ts` INT NULL DEFAULT NULL,
                `flow_entry_guid` BINARY(16) NOT NULL ,
                `flow_entry_type` VARCHAR(10) NULL DEFAULT NULL ,
                `flow_entry_title` VARCHAR(40) NULL DEFAULT NULL,
                `flow_entry_blurb` VARCHAR(120) NULL DEFAULT NULL,
                `flow_entry_body_bb_code` MEDIUMTEXT NULL DEFAULT NULL,
                `flow_entry_body_bb_text` MEDIUMTEXT NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
           ) ENGINE = InnoDB COMMENT = 'defines the units made in a flow';

       ");

        $this->execute("ALTER TABLE `flow_entries` ADD INDEX `idx_flow_project_id`   (`flow_project_id`);");
        $this->execute("ALTER TABLE `flow_entries` ADD INDEX `idx_parent_flow_entry_id`   (`parent_flow_entry_id`);");
        $this->execute("ALTER TABLE `flow_entries` ADD UNIQUE `udx_flow_entry_guid` (`flow_entry_guid`);");
        $this->execute("ALTER TABLE `flow_entries` ADD FULLTEXT `ft_flow_entry_title` (`flow_entry_title`);");
        $this->execute("ALTER TABLE `flow_entries` ADD FULLTEXT `ft_flow_entry_blurb` (`flow_entry_blurb`);");
        $this->execute("ALTER TABLE `flow_entries` ADD FULLTEXT `ft_flow_entry_body` (flow_entry_body_bb_code);");
        $this->execute("ALTER TABLE `flow_entries` ADD FULLTEXT `ft_flow_entry_body_bb_text` (flow_entry_body_bb_text);");

        $this->execute("ALTER TABLE `flow_entries` ADD CONSTRAINT `fk_flow_entries_has_flow_project_id` 
            FOREIGN KEY (`flow_project_id`) REFERENCES `flow_projects`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT ;");

        $this->execute("ALTER TABLE `flow_entries` ADD CONSTRAINT `fk_flow_entries_has_flow_entry_id` 
            FOREIGN KEY (`parent_flow_entry_id`) REFERENCES `flow_entries`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT ;");

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

        $this->execute("ALTER TABLE `flow_entries` DROP FOREIGN KEY `fk_flow_entries_has_flow_entry_id`");
        $this->execute("ALTER TABLE `flow_entries` DROP FOREIGN KEY `fk_flow_entries_has_flow_project_id`");
        $this->table('flow_entries')->drop();
    }
}
