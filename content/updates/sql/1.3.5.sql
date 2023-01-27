ALTER TABLE `condet` ADD `term` INT NULL AFTER `price`;

ALTER TABLE `cfitems` ADD INDEX(`login`);

ALTER TABLE `contractdates` ADD `from` DATE NULL AFTER `date`, ADD `till` DATE NULL AFTER `from`; 

ALTER TABLE `contrahens` ADD `agnameabbr` VARCHAR(255) NULL AFTER `contrname`, ADD `agsignatory` VARCHAR(255) NULL AFTER `agnameabbr`, ADD `agsignatory2` VARCHAR(255) NULL AFTER `agsignatory`, ADD `agbasis` VARCHAR(255) NULL AFTER `agsignatory2`, ADD `agmail` VARCHAR(100) NULL AFTER `agbasis`, ADD `siteurl` VARCHAR(255) NULL AFTER `agmail`; 

ALTER TABLE `corp_data` ADD `corpnameabbr` VARCHAR(255) NULL AFTER `notes`, ADD `corpsignatory` VARCHAR(255) NULL AFTER `corpnameabbr`, ADD `corpsignatory2` VARCHAR(255) NULL AFTER `corpsignatory`, ADD `corpbasis` VARCHAR(255) NULL AFTER `corpsignatory2`, ADD `corpemail` VARCHAR(100) NULL AFTER `corpbasis`;