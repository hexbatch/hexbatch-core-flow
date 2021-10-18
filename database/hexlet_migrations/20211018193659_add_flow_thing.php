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
                `thing_created_at` DATETIME NOT NULL DEFAULT current_timestamp,
                `thing_type` VARCHAR(10) NOT NULL ,
                `thing_guid` BINARY(16) NOT NULL ,
                `thing_title` VARCHAR(40) NULL DEFAULT NULL ,
                PRIMARY KEY (`id`)
           ) ENGINE = InnoDB COMMENT = 'stores general search stuff in one table';

       ");

        $this->execute("ALTER TABLE `flow_things` ADD UNIQUE `udx_id_type` (id,thing_type);");
        $this->execute("ALTER TABLE `flow_things` ADD UNIQUE `udx_guid` (`thing_guid`);");
        $this->execute("ALTER TABLE `flow_things` ADD INDEX `idx_title` (`thing_title`);");
        $this->execute("ALTER TABLE `flow_things` ADD INDEX `idx_type` (`thing_type`);");
    }

    protected function down(): void
    {
        $this->table('flow_things')->drop();
    }
}
