ALTER TABLE `callmeback` ADD `userlogin` VARCHAR(64) NULL DEFAULT NULL AFTER `admin`;
ALTER TABLE `contrahens_extinfo` ADD `paysys_callback_url` VARCHAR(255) NOT NULL DEFAULT '';