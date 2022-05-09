<?php

declare(strict_types=1);

namespace Hexlet_migrations;

use app\hexlet\DBSelector;
use app\hexlet\MYDB;
use app\hexlet\RecursiveClasses;
use Phoenix\Migration\AbstractMigration;
use RuntimeException;

/** @noinspection PhpUnused */

class AddFlowEntryNodes extends AbstractMigration
{
    //20220509154921_add_flow_entry_nodes.php
    //triggers_20220509154921_flow_entry_nodes
    const NAME_PART = '20220509154921';
    const TRIGGER_DIR = HEXLET_BASE_PATH . '/database/hexlet_migrations/triggers/triggers_' . self::NAME_PART . '_flow_entry_nodes';

    protected function up(): void
    {
        $mydb = DBSelector::getConnection();

        if (!is_dir(static::TRIGGER_DIR)) {
            throw new RuntimeException("Trigger dir of " . static::TRIGGER_DIR . " does not exist");
        }

        $mydb->dropTriggersLike(static::NAME_PART);
        $mydb->dropTriggersLike(AddFlowAppliedTags::NAME_PART);

        /*
         * varchar(20) bb_tag_name
         * mediumtext entry_node_words
         */
        $this->execute("

            CREATE TABLE flow_entry_nodes (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `flow_entry_id` INT NULL DEFAULT NULL ,
                `flow_entry_parent_id` INT NULL DEFAULT NULL ,
                `entry_node_created_at` DATETIME NOT NULL DEFAULT current_timestamp,
                `entry_node_updated_at` TIMESTAMP DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                 entry_node_guid BINARY(16) DEFAULT NULL ,
                `bb_tag_name` VARCHAR(20) NOT NULL ,
                `entry_node_words` MEDIUMTEXT DEFAULT NULL,
                `entry_node_attributes` JSON NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY idx_flow_entry_id (flow_entry_id),
                KEY idx_flow_entry_parent_id (flow_entry_parent_id),
                UNIQUE KEY udx_entry_node_guid (entry_node_guid),
                constraint fk_flow_entry_nodes_has_entry_id
                    foreign key (flow_entry_id) references flow_entries (id)
                        on update cascade on delete cascade,
                constraint fk_flow_entry_nodes_has_parent_id
                    foreign key (flow_entry_parent_id) references flow_entry_nodes (id)
                        on update cascade on delete cascade        
           ) ENGINE = InnoDB COMMENT = 'Holds parsed bb code to allow tag manipulation and consistency ';

       ");

        $this->execute("alter table flow_applied_tags add tagged_flow_entry_node_id int default NULL null after tagged_flow_project_id;");
        $this->execute("create index idx_flow_entry_node_id on flow_applied_tags (tagged_flow_entry_node_id);");
        $this->execute("create index udx_tagged_flow_entry_node_id on flow_applied_tags (flow_tag_id, tagged_flow_entry_node_id);");
        $this->execute("alter table flow_applied_tags
                                add constraint flow_applied_tags_has_node_id_fk
                                foreign key (tagged_flow_entry_node_id) references flow_entry_nodes (id)
                                on update cascade on delete set null ;");


        //add column to the tag applied



        //NOW UPDATE THE TRIGGERS !
        $files = MYDB::recursive_search_sql_files(static::TRIGGER_DIR);

        foreach ($files as $file) {
            $sql = trim(file_get_contents($file));
            if (empty($sql)) {
                continue;
            }
            $this->execute($sql);
        }

        $files = MYDB::recursive_search_sql_files(AddFlowAppliedTags::TRIGGER_DIR);

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

        $this->execute("ALTER TABLE flow_entry_nodes DROP FOREIGN KEY `fk_flow_entry_nodes_has_entry_id`");
        $this->execute("ALTER TABLE flow_entry_nodes DROP FOREIGN KEY `fk_flow_entry_nodes_has_parent_id`");
        $this->execute("ALTER TABLE flow_applied_tags DROP FOREIGN KEY `flow_applied_tags_has_node_id_fk`");
        $this->table('flow_applied_tags')->dropIndexByName('udx_tagged_flow_entry_node_id');
        $this->table('flow_applied_tags')->dropColumn('tagged_flow_entry_node_id');
        $this->table('flow_entry_nodes')->drop();
    }
}

