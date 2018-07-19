ALTER TABLE `dealwithithist` ADD `datetimedone` DATETIME NULL DEFAULT NULL AFTER `date`;

UPDATE `dealwithithist` as `C` INNER JOIN (SELECT `mtime`,`originalid` FROM `dealwithithist` WHERE `done` = '1' AND `datetimedone` is NULL) as `A` on `C`.`originalid` = `A`.`originalid` SET `C`.`datetimedone` = `A`.`mtime`;

CREATE TABLE IF NOT EXISTS `sms_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `srvmsgself_id` varchar(255) NOT NULL,
  `srvmsgpack_id` varchar(255) NOT NULL,
  `date_send` datetime NOT NULL,
  `date_statuschk` datetime NOT NULL,
  `delivered` tinyint(1) UNSIGNED DEFAULT 0,
  `no_statuschk` tinyint(1) UNSIGNED DEFAULT 0,
  `send_status` varchar(255) NOT NULL DEFAULT '',
  `msg_text` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `login` (`login`) USING BTREE,
  KEY `phone` (`phone`) USING BTREE,
  KEY `date_send` (`date_send`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `taskmandone` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskid` int(11) DEFAULT NULL,
  `date` datetime NOT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `punchscripts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alias` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` text,
  PRIMARY KEY  (`id`),
  KEY `alias` (`alias`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;