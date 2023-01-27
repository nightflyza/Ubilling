ALTER TABLE `condet` ADD `term` INT NULL AFTER `price`;

ALTER TABLE `cfitems` ADD INDEX(`login`);

ALTER TABLE `contractdates` ADD `from` DATE NULL AFTER `date`, ADD `till` DATE NULL AFTER `from`; 