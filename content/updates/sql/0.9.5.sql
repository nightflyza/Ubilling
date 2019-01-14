ALTER TABLE `employee` ADD `amountLimit` VARCHAR(45) NOT NULL DEFAULT '0';

CREATE TABLE IF NOT EXISTS `callshist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `number` varchar(120) NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;