ALTER TABLE `dealwithithist` ADD `datetimedone` DATETIME NULL DEFAULT NULL AFTER `date`;

UPDATE `dealwithithist` as `C` INNER JOIN (SELECT `mtime`,`originalid` FROM `dealwithithist` WHERE `done` = '1' AND `datetimedone` is NULL) as `A` on `C`.`originalid` = `A`.`originalid` SET `C`.`datetimedone` = `A`.`mtime`;
