CREATE TABLE IF NOT EXISTS `custmaps_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mapid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `geo` longtext,
  `length_m` decimal(12,2) NOT NULL DEFAULT '0.00',
  `style_color` varchar(32) NOT NULL DEFAULT '#f57601',
  `style_width` int(11) NOT NULL DEFAULT '2',
  `fibers_amount` int(11) NOT NULL DEFAULT '0',
  `description` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mapid` (`mapid`),
  KEY `idx_updated_at` (`updated_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `custmaps` ADD `clustering` tinyint NOT NULL DEFAULT '0' AFTER `name`;
ALTER TABLE `custmaps` ADD `cmarkers` tinyint NOT NULL DEFAULT '0' AFTER `clustering`;
ALTER TABLE `custmaps` ADD `metrics` tinyint NOT NULL DEFAULT '0' AFTER `cmarkers`;
ALTER TABLE `filestorage` ADD `origname` varchar(255) NOT NULL DEFAULT '' AFTER `filename`;