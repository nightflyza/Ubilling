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


