ALTER TABLE `vservices` ADD `exclude_tags` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `vservices` ADD `archived` TINYINT(1) NOT NULL DEFAULT 0;