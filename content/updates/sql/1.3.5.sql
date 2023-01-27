ALTER TABLE `condet` ADD `term` INT NULL AFTER `price`;

ALTER TABLE `cfitems` ADD INDEX(`login`);

ALTER TABLE `contractdates` ADD `from` DATE NULL AFTER `date`, ADD `till` DATE NULL AFTER `from`; 

ALTER TABLE `contrahens` ADD `agnameabbr` VARCHAR(255) NULL AFTER `contrname`, ADD `agsignatory` VARCHAR(255) NULL AFTER `agnameabbr`, ADD `agsignatory2` VARCHAR(255) NULL AFTER `agsignatory`, ADD `agbasis` VARCHAR(255) NULL AFTER `agsignatory2`, ADD `agmail` VARCHAR(100) NULL AFTER `agbasis`, ADD `siteurl` VARCHAR(255) NULL AFTER `agmail`; 