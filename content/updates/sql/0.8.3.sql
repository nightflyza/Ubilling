CREATE TABLE IF NOT EXISTS `wdycinfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `missedcount` int(11) DEFAULT NULL,
  `recallscount` int(11) DEFAULT NULL,
  `unsucccount` int(11) DEFAULT NULL,
  `missednumbers` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `taskman` ADD `change_admin` VARCHAR(255) NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `wh_reshist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `type` varchar(40) NOT NULL,
  `storageid` int(11) DEFAULT NULL,
  `itemtypeid` int(11) DEFAULT NULL,
  `count` double DEFAULT NULL,
  `employeeid` int(11) DEFAULT NULL,
  `admin` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`storageid`,`itemtypeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `wh_in` ADD `admin` VARCHAR(100) NULL DEFAULT NULL AFTER `notes`; 

ALTER TABLE `wh_out` ADD `admin` VARCHAR(100) NULL DEFAULT NULL AFTER `notes`; 