CREATE TABLE IF NOT EXISTS `visor_chans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visorid` int(11) NOT NULL,
  `dvrid` int(11) NOT NULL,
  `chan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;