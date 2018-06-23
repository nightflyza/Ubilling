ALTER TABLE `dealwithithist` ADD `datetimedone` DATETIME NOT NULL AFTER `date`;

UPDATE `dealwithithist` as `C` INNER JOIN (SELECT `mtime`,`originalid` FROM `dealwithithist` WHERE `done` = '1' AND `datetimedone` = '0000-00-00 00:00:00') as `A` on `C`.`originalid` = `A`.`originalid` SET `C`.`datetimedone` = `A`.`mtime`;
