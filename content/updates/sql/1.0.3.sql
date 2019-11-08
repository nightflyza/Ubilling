CREATE TABLE IF NOT EXISTS `zte_qinq` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `swid` int(11) NOT NULL,
    `slot_number` int(11) NOT NULL,
    `port` int(4) NOT NULL,
    `svlan_id` int(11) NOT NULL,
    `cvlan` int(4) NOT NULL,        
    PRIMARY KEY (`id`),
    KEY (`svlan_id`),
    KEY (`cvlan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


ALTER TABLE `banksta2_presets` CHANGE `col_skiprow` col_skiprow varchar(100) DEFAULT '';
ALTER TABLE `banksta2_presets` ADD `replace_strs` tinyint(3) DEFAULT 0;
ALTER TABLE `banksta2_presets` ADD `col_replace_strs` varchar(100) DEFAULT '';
ALTER TABLE `banksta2_presets` ADD `strs_to_replace` varchar(200) DEFAULT '';
ALTER TABLE `banksta2_presets` ADD `strs_to_replace_with` varchar(200) DEFAULT '';
ALTER TABLE `banksta2_presets` ADD `replacements_cnt` tinyint(3) DEFAULT 1;
ALTER TABLE `banksta2_presets` ADD `remove_strs` tinyint(3) DEFAULT 0;
ALTER TABLE `banksta2_presets` ADD `col_remove_strs` varchar(100) DEFAULT '';
ALTER TABLE `banksta2_presets` ADD `strs_to_remove` varchar(200) DEFAULT '';
