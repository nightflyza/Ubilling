ALTER TABLE `switches` ADD `snmpwrite` VARCHAR(45) NULL AFTER `swid`;

ALTER TABLE `phones` ADD INDEX (`login`);

ALTER TABLE `stg`.`print_card` ADD UNIQUE (`title`);