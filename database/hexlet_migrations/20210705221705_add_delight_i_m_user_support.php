<?php

namespace Hexlet_migrations;

use Phoenix\Migration\AbstractMigration;

/** @noinspection PhpUnused */
class AddDelightIMUserSupport extends AbstractMigration
{
    protected function up(): void
    {
        $this->execute("
            CREATE TABLE 
                `users` (
                                       `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                       `email` varchar(249) COLLATE utf8mb4_0900_ai_ci NOT NULL,
                                       `password` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
                                       `username` varchar(100) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
                                       `status` tinyint(2) unsigned NOT NULL DEFAULT '0',
                                       `verified` tinyint(1) unsigned NOT NULL DEFAULT '0',
                                       `resettable` tinyint(1) unsigned NOT NULL DEFAULT '1',
                                       `roles_mask` int(10) unsigned NOT NULL DEFAULT '0',
                                       `registered` int(10) unsigned NOT NULL,
                                       `last_login` int(10) unsigned DEFAULT NULL,
                                       `force_logout` mediumint(7) unsigned NOT NULL DEFAULT '0',
                                       PRIMARY KEY (`id`),
                                       UNIQUE KEY `email` (`email`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE= utf8mb4_0900_ai_ci;
        ");


        $this->execute("
            CREATE TABLE
                `users_confirmations` (
                                     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                     `user_id` int(10) unsigned NOT NULL,
                                     `email` varchar(249) COLLATE utf8mb4_0900_ai_ci NOT NULL,
                                     `selector` varchar(16) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
                                     `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
                                     `expires` int(10) unsigned NOT NULL,
                                     PRIMARY KEY (`id`),
                                     UNIQUE KEY `selector` (`selector`),
                                     KEY `email_expires` (`email`,`expires`),
                                     KEY `user_id` (`user_id`)
                                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");

        $this->execute("
            CREATE TABLE 
                `users_remembered` (
                                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                      `user` int(10) unsigned NOT NULL,
                                      `selector` varchar(24) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
                                      `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
                                      `expires` int(10) unsigned NOT NULL,
                                      PRIMARY KEY (`id`),
                                      UNIQUE KEY `selector` (`selector`),
                                      KEY `user` (`user`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");

        $this->execute("
            CREATE TABLE 
                `users_resets` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `user` int(10) unsigned NOT NULL,
                                  `selector` varchar(20) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
                                  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
                                  `expires` int(10) unsigned NOT NULL,
                                  PRIMARY KEY (`id`),
                                  UNIQUE KEY `selector` (`selector`),
                                  KEY `user_expires` (`user`,`expires`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");

        $this->execute("
            CREATE TABLE IF NOT EXISTS 
                `users_throttling` (
                                  `bucket` varchar(44) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
                                  `tokens` float unsigned NOT NULL,
                                  `replenished_at` int(10) unsigned NOT NULL,
                                  `expires_at` int(10) unsigned NOT NULL,
                                  PRIMARY KEY (`bucket`),
                                  KEY `expires_at` (`expires_at`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");
    }


    protected function down(): void
    {
        $this->table('users_throttling')->drop();
        $this->table('users_resets')->drop();
        $this->table('users_remembered')->drop();
        $this->table('users_confirmations')->drop();
        $this->table('users')->drop();
    }
}
