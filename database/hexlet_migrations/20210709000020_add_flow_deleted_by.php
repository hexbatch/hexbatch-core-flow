<?php

namespace Hexlet_migrations;

use Phoenix\Migration\AbstractMigration;

/** @noinspection PhpUnused */
class AddFlowDeletedBy extends AbstractMigration
{


    protected function up(): void
    {
        $this->execute("

            CREATE TABLE flow_deleted_by (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `flow_user_id` INT  NOT NULL ,
                `deleted_at_ts` INT NULL DEFAULT NULL,
                `deleted_guid` BINARY(16) NOT NULL  ,
                PRIMARY KEY (`id`)
           ) ENGINE = InnoDB COMMENT = 'records when something was deleted and by whom';

       ");

        $this->execute("ALTER TABLE `flow_deleted_by` ADD INDEX `idx_flow_user_id`   (`flow_user_id`);");
        $this->execute("ALTER TABLE `flow_deleted_by` ADD UNIQUE `udx_deleted_guid` (`deleted_guid`);");

        $this->execute("ALTER TABLE `flow_deleted_by` ADD CONSTRAINT `fk_flow_deleted_by_has_flow_user_id` 
            FOREIGN KEY (`flow_user_id`) REFERENCES `flow_project_users`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT ;");

    }

    protected function down(): void
    {
        $this->execute("ALTER TABLE `flow_deleted_by` DROP FOREIGN KEY `fk_flow_deleted_by_has_flow_user_id`");
        $this->table('flow_deleted_by')->drop();
    }
}
