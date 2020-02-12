CREATE TABLE IF NOT EXISTS `visor_chans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visorid` int(11) NOT NULL,
  `dvrid` int(11) NOT NULL,
  `chan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `visor_secrets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visorid` int(11) NOT NULL,
  `login` varchar(64) NOT NULL,
  `password` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `frozen_charge_days` ADD `last_freeze_charge_dt` datetime NOT NULL AFTER `freeze_days_used`;
ALTER TABLE `frozen_charge_days` ADD `last_workdays_upd_dt` datetime NOT NULL;

ALTER TABLE `visor_dvrs` ADD `camlimit` int(11) NULL DEFAULT 0 AFTER `type`;

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