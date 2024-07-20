CREATE TABLE IF NOT EXISTS `banksta2` (
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
  `canceled` tinyint(4) NOT NULL,
  `service_type` varchar(100) NOT NULL DEFAULT '',
  `payid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `banksta2_presets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `presetname` varchar(80) NOT NULL,
  `col_realname` varchar(20) DEFAULT '',
  `col_address` varchar(20) DEFAULT '',
  `col_paysum` varchar(20) DEFAULT '',
  `col_paypurpose` varchar(20) DEFAULT '',
  `col_paydate` varchar(20) DEFAULT '',
  `col_paytime` varchar(20) DEFAULT '',
  `col_contract` varchar(20) DEFAULT '',
  `guess_contract` tinyint(3) DEFAULT 0,
  `contract_delim_start` varchar(40) DEFAULT '',
  `contract_delim_end` varchar(40) DEFAULT '',
  `contract_min_len` tinyint(3) DEFAULT 0,
  `contract_max_len` tinyint(3) DEFAULT 0,
  `service_type` varchar(100) NOT NULL DEFAULT '',
  `inet_srv_start_delim` varchar(40) DEFAULT '',
  `inet_srv_end_delim` varchar(40) DEFAULT '',
  `inet_srv_keywords` varchar(200) DEFAULT '',
  `ukv_srv_start_delim` varchar(40) DEFAULT '',
  `ukv_srv_end_delim` varchar(40) DEFAULT '',
  `ukv_srv_keywords` varchar(200) DEFAULT '',
  `skip_row` tinyint(3) DEFAULT 0,
  `col_skiprow` varchar(20) DEFAULT '',
  `skip_row_keywords` varchar(200) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`presetname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `visor_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `regdate` datetime NOT NULL,
  `realname` varchar(250) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `chargecams` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `visor_cams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visorid` int(11) NOT NULL,
  `login` varchar(250) NOT NULL,
  `primary` tinyint(4) NOT NULL,
  `camlogin` varchar(250) DEFAULT NULL,
  `campassword` varchar(250) DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  `dvrid` int(11) DEFAULT NULL,
  `dvrlogin` varchar(250) DEFAULT NULL,
  `dvrpassword` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `visor_dvrs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(250) NOT NULL,
  `port` int(11) DEFAULT NULL,
  `login` varchar(250) DEFAULT NULL,
  `password` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;