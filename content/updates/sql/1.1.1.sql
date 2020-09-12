CREATE TABLE IF NOT EXISTS `taskstates` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `taskid` INT(11) NOT NULL,
  `state` VARCHAR(42) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `taskid` (`taskid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
