CREATE TABLE IF NOT EXISTS `whiteboard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoryid` int(11) NOT NULL,
  `admin` varchar(255) NOT NULL,
  `employeeid` int(11) DEFAULT NULL,
  `createdate` datetime NOT NULL,
  `donedate` datetime DEFAULT NULL,
  `priority` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `text` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;