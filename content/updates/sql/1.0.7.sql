CREATE TABLE IF NOT EXISTS `address_extended` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `postal_code` varchar(10) NOT NULL DEFAULT '',
  `town_district` varchar(150) NOT NULL DEFAULT '',
  `address_exten` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `payments` MODIFY `note` varchar(200) NULL DEFAULT NULL;
ALTER TABLE `paymentscorr` MODIFY `note` varchar(200) NULL DEFAULT NULL;