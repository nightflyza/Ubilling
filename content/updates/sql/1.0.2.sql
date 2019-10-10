ALTER TABLE `qinq` ADD `svlan_id` int(10) NOT NULL AFTER `svlan`;

ALTER TABLE `qinq` DROP `svlan`;

RENAME TABLE `qinq` TO `qinq_bindings`;

CREATE TABLE IF NOT EXISTS `realms` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `realm` varchar(255) NOT NULL,
    `description` varchar(255) NULL,
    PRIMARY KEY (`id`),
    KEY (`realm`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `realms` (`id`,`realm`,`description`) VALUES (NULL, 'default', 'default realm');

CREATE TABLE IF NOT EXISTS `qinq_svlan` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `realm_id` int(11) NOT NULL,
    `svlan` int(4) NOT NULL,
    `description` varchar(255) NULL,
    PRIMARY KEY (`id`),
    KEY (`realm_id`),
    KEY (`svlan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `qinq_svlan` (`id`, `realm_id`, `svlan`, `description`) VALUES (1, 1, 0, 'Use it for untagged VLAN');

INSERT INTO `qinq_svlan` (`id`, `realm_id`, `svlan`) SELECT DISTINCT NULL, 1, `svlan` FROM `switches_qinq`;

ALTER TABLE `switches_qinq` ADD `svlan_id` int(11) NOT NULL AFTER `switchid`;

ALTER TABLE `switches_qinq` ADD KEY (`svlan_id`);

UPDATE `switches_qinq` AS `swq`, `qinq_svlan` AS `qsv` SET `swq`.`svlan_id` = `qsv`.`id` WHERE `swq`.`svlan` = `qsv`.`svlan` AND `qsv`.`realm_id` =1;

ALTER TABLE `switches_qinq` DROP `svlan`;

