CREATE TABLE IF NOT EXISTS `ptv_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `subscriberid` int(11) NOT NULL,
  `login` varchar(64) NOT NULL,
  `maintariff` int(11) DEFAULT NULL,
  `addtariffs` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `ptv_tariffs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `serviceid` INT(11) NOT NULL,
  `main` tinyint(1) NOT NULL,
  `name` VARCHAR(64) NOT NULL,
  `chans` VARCHAR(42) DEFAULT NULL,
  `fee` DOUBLE NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `ponboxes_splitters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `boxid` int(11) NOT NULL,
  `splitter` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `ponboxes` MODIFY `name` varchar(200) NULL DEFAULT NULL;
ALTER TABLE `ponboxes` ADD `exten_info` varchar(250) NULL DEFAULT NULL AFTER `name`;

ALTER TABLE sms_history ADD INDEX (srvmsgself_id) USING BTREE;
ALTER TABLE sms_history ADD INDEX (srvmsgself_id) USING BTREE;
ALTER TABLE sms_history ADD INDEX (date_statuschk) USING BTREE;
