CREATE TABLE IF NOT EXISTS `frozen_charge_days` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `freeze_days_amount` smallint(3) NOT NULL DEFAULT 0,
  `freeze_days_used`  smallint(3) NOT NULL DEFAULT 0,
  `work_days_restore` smallint(3) NOT NULL DEFAULT 0,
  `days_worked` smallint(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;