CREATE TABLE IF NOT EXISTS `crm_leads` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `address` varchar(255) NOT NULL,
  `realname` varchar(255) NOT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `mobile` varchar(32) NOT NULL,
  `extmobile` varchar(32) DEFAULT NULL,
  `email` varchar(64) DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `tariff` varchar(64) DEFAULT NULL,
  `login` varchar(64) DEFAULT NULL,
  `employeeid` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
