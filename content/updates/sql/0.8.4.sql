ALTER TABLE `switches` ADD `snmpwrite` VARCHAR(45) NULL AFTER `swid`;

ALTER TABLE `phones` ADD INDEX (`login`);

ALTER TABLE `print_card` ADD UNIQUE (`title`);

CREATE TABLE IF NOT EXISTS `dealwithithist` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `originalid` INT(11) NOT NULL, 
 `mtime` datetime NOT NULL,
 `date` date NOT NULL,
 `login` varchar(45) NOT NULL,
 `action` varchar(45) NOT NULL,
 `param` varchar(45) DEFAULT NULL,
 `note` varchar(45) DEFAULT NULL,
 `admin` varchar(50) DEFAULT NULL,
 `done` TINYINT(1)  NOT NULL ,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;