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