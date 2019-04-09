CREATE TABLE IF NOT EXISTS `capabhist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `capabid` int(11) NOT NULL,
  `admin` varchar(40) NOT NULL,
  `date` datetime NOT NULL,
  `type` varchar(40) NOT NULL,
  `event` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


ALTER TABLE `ddt_options` ADD `setcredit` TINYINT NULL AFTER `chargeuntilday`; 