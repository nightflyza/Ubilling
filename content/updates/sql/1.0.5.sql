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