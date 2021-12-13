CREATE TABLE IF NOT EXISTS `paymeuz_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_create` datetime NOT NULL,
  `transact_id` varchar(255) NOT NULL,
  `op_transact_id` varchar(255) NOT NULL,
  `op_customer_id` varchar(255) NOT NULL,
  `amount` double NOT NULL DEFAULT 0,
  `state` tinyint(2) NOT NULL DEFAULT 0,
  `payme_transact_timestamp` bigint(15) UNSIGNED NOT NULL DEFAULT 0,
  `create_timestamp` bigint(15) UNSIGNED NOT NULL DEFAULT 0,
  `perform_timestamp` bigint(15) UNSIGNED NOT NULL DEFAULT 0,
  `cancel_timestamp` bigint(15) UNSIGNED NOT NULL DEFAULT 0,
  `cancel_reason` varchar(255) NOT NULL DEFAULT '',
  `receivers` text DEFAULT '',
PRIMARY KEY (`id`),
KEY `date_create` (`date_create`),
KEY `transact_id` (`transact_id`),
KEY `op_transact_id` (`op_transact_id`),
KEY `op_customer_id` (`op_customer_id`),
KEY `payme_transact_timestamp` (`payme_transact_timestamp`),
KEY `create_timestamp` (`create_timestamp`),
KEY `perform_timestamp` (`perform_timestamp`),
KEY `cancel_timestamp` (`cancel_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `buildpassport` ADD `contract` TINYINT NULL , ADD `mediator` TINYINT NULL ; 

CREATE TABLE IF NOT EXISTS `ot_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `remoteid` int(11) NOT NULL,
  `login` varchar(64) NOT NULL,
  `email` varchar(64) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `code` varchar(64) DEFAULT NULL,
  `tariffid` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `ot_tariffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `alias` varchar(128) NOT NULL,
  `fee` DOUBLE NOT NULL,
  `period` varchar(8) DEFAULT NULL,
  `percent` DOUBLE DEFAULT NULL,
  `main` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;