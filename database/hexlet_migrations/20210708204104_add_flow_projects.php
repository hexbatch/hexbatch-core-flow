<?php

namespace Hexlet_migrations;

use app\hexlet\MYDB;
use Phoenix\Migration\AbstractMigration;
use RuntimeException;
use app\hexlet\DBSelector;
use app\hexlet\RecursiveClasses;

/** @noinspection PhpUnused */
class AddFlowProjects extends AbstractMigration
{
    //triggers_20210708204104_flow_projects
    const NAME_PART = '20210708204104';
    const TRIGGER_DIR = HEXLET_BASE_PATH . '/database/hexlet_migrations/triggers/triggers_'.self::NAME_PART.'_flow_projects';

    protected function up(): void
    {
        $mydb = DBSelector::getConnection();

        if ( ! is_dir( static::TRIGGER_DIR ) ) {
            throw new RuntimeException( "Trigger dir of " . static::TRIGGER_DIR . " does not exist" );
        }

        $mydb->dropTriggersLike( static::NAME_PART );

        $this->execute("

            CREATE TABLE flow_projects (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `admin_flow_user_id` INT NOT NULL , 
                `parent_flow_project_id` INT NULL DEFAULT NULL, 
                `created_at_ts` INT NULL DEFAULT NULL,
                `is_public` TINYINT NOT NULL DEFAULT 0,
                `export_repo_do_auto_push` TINYINT NOT NULL DEFAULT 0,
                `flow_project_guid` BINARY(16) NOT NULL ,
                `flow_project_type` VARCHAR(10) NULL DEFAULT NULL ,
                `flow_project_title` VARCHAR(40) NULL DEFAULT NULL ,
                `flow_project_blurb` VARCHAR(120) NULL DEFAULT NULL ,
                `export_repo_url` VARCHAR(200) NULL DEFAULT NULL ,
                `export_repo_branch` VARCHAR(200) NULL DEFAULT NULL ,
                `export_repo_key` TEXT NULL DEFAULT NULL ,
                `import_repo_url` VARCHAR(200) NULL DEFAULT NULL ,
                `import_repo_branch` VARCHAR(200) NULL DEFAULT NULL ,
                `import_repo_key` TEXT NULL DEFAULT NULL ,
                `flow_project_readme` MEDIUMTEXT NULL DEFAULT NULL ,
                `flow_project_readme_bb_code` MEDIUMTEXT NULL DEFAULT NULL ,
                PRIMARY KEY (`id`)
           ) ENGINE = InnoDB COMMENT = 'defines the top most organization made in a flow';

       ");

        $this->execute("ALTER TABLE `flow_projects` ADD INDEX `idx_admin_flow_user_id`   (`admin_flow_user_id`);");
        $this->execute("ALTER TABLE `flow_projects` ADD INDEX `idx_flow_project_type`   (`flow_project_type`);");
        $this->execute("ALTER TABLE `flow_projects` ADD INDEX `idx_parent_flow_project_id`   (`parent_flow_project_id`);");
        $this->execute("ALTER TABLE `flow_projects` ADD UNIQUE `udx_flow_project_guid` (`flow_project_guid`);");
        $this->execute("ALTER TABLE `flow_projects` ADD FULLTEXT `ft_flow_project_title` (`flow_project_title`);");
        $this->execute("ALTER TABLE `flow_projects` ADD FULLTEXT `ft_flow_project_blurb` (`flow_project_blurb`);");
        $this->execute("ALTER TABLE `flow_projects` ADD FULLTEXT `ft_flow_project_readme` (`flow_project_readme`);");

        $this->execute("ALTER TABLE `flow_projects` ADD CONSTRAINT `fk_flow_projects_has_flow_user_id` 
            FOREIGN KEY (`admin_flow_user_id`) REFERENCES `flow_users`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT ;");

        $this->execute("ALTER TABLE `flow_projects` ADD CONSTRAINT `fk_flow_projects_has_parent_id` 
            FOREIGN KEY (`parent_flow_project_id`) REFERENCES `flow_projects`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT ;");

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

        $this->execute("ALTER TABLE `flow_projects` DROP FOREIGN KEY `fk_flow_projects_has_parent_id`");
        $this->execute("ALTER TABLE `flow_projects` DROP FOREIGN KEY `fk_flow_projects_has_flow_user_id`");
        $this->table('flow_projects')->drop();
    }
}
