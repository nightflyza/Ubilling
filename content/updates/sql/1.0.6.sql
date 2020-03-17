ALTER TABLE `vservices` MODIFY `price` double NOT NULL DEFAULT 0;
ALTER TABLE `vservices` ADD `charge_period_days` tinyint(3) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `invoice_num` varchar(40) NOT NULL DEFAULT '',
  `invoice_date` datetime NOT NULL,
  `invoice_sum` double NOT NULL DEFAULT 0,
  `invoice_body` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`invoice_num`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

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