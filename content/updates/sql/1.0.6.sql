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
