<?php

namespace Hexlet_migrations;

use Phoenix\Migration\AbstractMigration;

/** @noinspection PhpUnused */
class AddFlowThing extends AbstractMigration
{
    protected function up(): void
    {
        $this->execute("

            CREATE TABLE flow_things (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `thing_id` INT NOT NULL,
                 is_public tinyint default 0 not null,
                `thing_created_at` DATETIME NOT NULL DEFAULT current_timestamp,
                `thing_updated_at` TIMESTAMP DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                `thing_type` VARCHAR(10) NOT NULL ,
                `thing_guid` BINARY(16) NOT NULL ,
                `thing_title` VARCHAR(40) NULL DEFAULT NULL ,
                owning_user_guid BINARY(16) DEFAULT NULL ,
                owning_project_guid BINARY(16) DEFAULT NULL ,
                owning_entry_guid BINARY(16) DEFAULT NULL ,
                thing_blurb VARCHAR(120) DEFAULT NULL,
                thing_text mediumtext DEFAULT NULL,
                allowed_readers_json JSON DEFAULT (JSON_ARRAY()) ,
                tag_used_by_json JSON DEFAULT (JSON_ARRAY()) ,
                css_json json default NULL,
                PRIMARY KEY (`id`),
                INDEX idx_owning_entry_guid (owning_entry_guid),
                INDEX idx_owning_project_guid (owning_project_guid)
           ) ENGINE = InnoDB COMMENT = 'stores general search stuff in one table';

       ");


        $this->execute("ALTER TABLE `flow_things` ADD UNIQUE `udx_id_type` (id,thing_type);");
        $this->execute("ALTER TABLE `flow_things` ADD UNIQUE `udx_guid` (`thing_guid`);");
        $this->execute("ALTER TABLE `flow_things` ADD INDEX `idx_title` (`thing_title`);");
        $this->execute("ALTER TABLE `flow_things` ADD INDEX `idx_type` (`thing_type`);");
        $this->execute("ALTER TABLE `flow_things` ADD FULLTEXT `ft_thing_blurb` (`thing_blurb`);");
        $this->execute("ALTER TABLE `flow_things` ADD FULLTEXT `ft_thing_text` (`thing_text`);");
    }

    protected function down(): void
    {
        $this->table('flow_things')->drop();
    }
}
