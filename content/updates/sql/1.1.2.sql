ALTER TABLE `passportdata` ADD `pinn` VARCHAR(15) NULL DEFAULT NULL;
ALTER TABLE `banksta2_presets` ADD `col_srvidents` varchar(20) DEFAULT '' AFTER `col_contract`;
ALTER TABLE `banksta2_presets` ADD `srvidents_preffered` tinyint(3) DEFAULT 0 AFTER `guess_contract`;

CREATE TABLE IF NOT EXISTS `user_dataexport_allowed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(100) NOT NULL,
  `export_allowed` tinyint(3) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `contrahens_extinfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agentid` int(11) NOT NULL,
  `service_type` varchar(50) NOT NULL DEFAULT '',
  `internal_paysys_name` varchar(50)  NOT NULL DEFAULT '',
  `internal_paysys_id` varchar(50)  NOT NULL DEFAULT '',
  `internal_paysys_srv_id` varchar(50)  NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;