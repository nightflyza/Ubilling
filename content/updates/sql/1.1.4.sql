ALTER TABLE `envydevices` ADD `cutstart` INT NULL DEFAULT NULL , ADD `cutend` INT NULL DEFAULT NULL ; 

ALTER TABLE `visor_dvrs` ADD `customurl` VARCHAR(255) NULL DEFAULT NULL AFTER `camlimit`; 

ALTER TABLE `stickyrevelations` ADD `dayweek` INT NULL DEFAULT NULL AFTER `dayto`; 