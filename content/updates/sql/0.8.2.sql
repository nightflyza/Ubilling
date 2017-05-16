ALTER TABLE `stickynotes` ADD `remindtime` TIME DEFAULT NULL AFTER `reminddate`, ADD INDEX (`remindtime`) ; 
 
ALTER TABLE `employee` ADD `telegram` VARCHAR(40) NULL DEFAULT NULL AFTER `mobile`; 
 
CREATE TABLE IF NOT EXISTS `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
 
CREATE TABLE IF NOT EXISTS `branchesadmins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branchid` int(11) NOT NULL,
  `admin` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
 
CREATE TABLE IF NOT EXISTS `branchesusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branchid` int(11) NOT NULL,
  `login` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
 
CREATE TABLE IF NOT EXISTS `branchescities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branchid` int(11) NOT NULL,
  `cityid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
 
CREATE TABLE IF NOT EXISTS `branchestariffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branchid` int(11) NOT NULL,
  `tariff` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
 
CREATE TABLE IF NOT EXISTS `branchesservices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branchid` int(11) NOT NULL,
  `serviceid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
 
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
 
CREATE  TABLE IF NOT EXISTS `print_card` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `field` varchar(255) NOT NULL,
  `color` varchar(255) DEFAULT '',
  `font_size` int(11) DEFAULT NULL,
  `top` int(11) DEFAULT NULL,
  `left` int(11) DEFAULT NULL,
  `text` text,
  PRIMARY KEY (`id`)
) ENGINE = MyISAM DEFAULT CHARSET=UTF8;
 
INSERT INTO
    `print_card` (`title`, `field`, `color`, `font_size`, `top`, `left`, `text`)
VALUES
    ('Serial number', 'number', '0.0.0', '12', '80', '130', 'Номер № {number}'),
    ('Serial part', 'serial', '0.0.0', '12', '80', '110', 'Серия {serial}'),
    ('Price', 'rating', '139.0.139', '16', '120', '90', 'Номинал {sum}грн. '),
    ('Phone', 'phone', '0.0.0', '8', '160', '3', '+38(096)xxx-xx-xx, +38(096)xxx-xx-xx, +38(096)xxx-xx-xx'),
('Site', 'site', '0.0.0', '10', '15', '5', 'Сайт: xxx.xxx.ua');
 
ALTER TABLE `speeds` ADD `burstdownload` VARCHAR(45) DEFAULT NULL;
 
ALTER TABLE `speeds` ADD `burstupload` VARCHAR(45) DEFAULT NULL;
 
ALTER TABLE `speeds` ADD `bursttimedownload` VARCHAR(45) DEFAULT NULL;
 
ALTER TABLE `speeds` ADD `burstimetupload` VARCHAR(45) DEFAULT NULL;
 
ALTER TABLE `uhw_brute` ADD `login` VARCHAR(255) NOT NULL AFTER `password`;