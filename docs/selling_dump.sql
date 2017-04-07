-- -----------------------------------------------------
-- Table `selling`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `selling` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  `address` VARCHAR(255) NULL ,
  `geo` VARCHAR(255) NULL ,
  `contact` VARCHAR(255) NULL ,
  `count_cards` int(11) NULL ,
  `comment` TEXT  NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;

ALTER TABLE `cardbank` ADD `part` VARCHAR(255) NULL;
ALTER TABLE `cardbank` ADD `receipt_date` DATETIME NULL;
ALTER TABLE `cardbank` ADD `selling_id` int(11) NULL;