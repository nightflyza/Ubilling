ALTER TABLE `ukv_users` ADD `tariffnmid` INT NULL AFTER `tariffid`;
ALTER TABLE `sms_history` ADD `smssrvid` INT(11) NOT NULL DEFAULT 0 AFTER `id`;
ALTER TABLE `sms_history` ADD INDEX(`smssrvid`);

CREATE TABLE IF NOT EXISTS `sms_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `login` varchar(255) NOT NULL,
  `passwd` varchar(255) NOT NULL,
  `url_addr` varchar(255) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `alpha_name` varchar(40) NOT NULL,
  `default_service` tinyint(1) UNSIGNED DEFAULT 0,
  `api_file_name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `sms_services_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sms_srv_id` int(11) NOT NULL,
  `user_login` varchar(255) DEFAULT NULL,
  `employee_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`user_login`),
  UNIQUE KEY (`employee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `switches_qinq` (
  `switchid` int(11) NOT NULL,
  `svlan` int(11) NOT NULL,
  `cvlan` int(11) NOT NULL,
  PRIMARY KEY (`switchid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `bankstamd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `hash` varchar(255) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `admin` varchar(255) NOT NULL,
  `contract` varchar(255) DEFAULT NULL,
  `summ` varchar(42) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `realname` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `pdate` varchar(42) DEFAULT NULL,
  `ptime` varchar(42) DEFAULT NULL,
  `processed` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;