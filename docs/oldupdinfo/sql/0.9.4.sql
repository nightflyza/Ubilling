CREATE TABLE IF NOT EXISTS `trinitytv_devices` (
  `id` int(11) NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  `subscriber_id` int(11) DEFAULT NULL,
  `mac` varchar(128) NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `trinitytv_subscribers` (
  `id` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  `contracttrinity` bigint(20) DEFAULT NULL,
  `tariffid` int(11) NOT NULL,
  `actdate` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `trinitytv_suspend` (
  `id` int(11) NOT NULL,
  `login` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `trinitytv_tariffs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `fee` double DEFAULT '0',
  `serviceid` varchar(45) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


ALTER TABLE `trinitytv_devices`  ADD PRIMARY KEY (`id`);
ALTER TABLE `trinitytv_subscribers`  ADD PRIMARY KEY (`id`);
ALTER TABLE `trinitytv_suspend`  ADD PRIMARY KEY (`id`);
ALTER TABLE `trinitytv_tariffs`  ADD PRIMARY KEY (`id`);
ALTER TABLE `trinitytv_devices`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `trinitytv_subscribers`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `trinitytv_suspend`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `trinitytv_tariffs`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sms_history` MODIFY `msg_text` varchar(500) NOT NULL DEFAULT '';

CREATE TABLE IF NOT EXISTS `pononuextusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `onuid` int(11) NOT NULL,
  `login` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `corp_persons` ADD COLUMN `notes` TEXT NULL AFTER `appointment`;