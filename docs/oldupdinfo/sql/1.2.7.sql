CREATE TABLE IF NOT EXISTS `olt_qinq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `swid` int(11) NOT NULL,
  `port` int(4) NOT NULL,
  `svlan_id` int(11) NOT NULL,
  `cvlan` int(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `svlan_id` (`svlan_id`),
  KEY `cvlan` (`cvlan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `op_sms_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `login` varchar(255) NOT NULL,
  `balance` double NOT NULL DEFAULT 0,
  `summ` double NOT NULL DEFAULT 0,
  `processed` tinyint(1) UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_id` (`payment_id`),
  KEY `login` (`login`),
  KEY `date` (`date`),
  KEY `summ` (`summ`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;