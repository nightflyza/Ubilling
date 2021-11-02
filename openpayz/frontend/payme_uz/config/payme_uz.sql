CREATE TABLE IF NOT EXISTS `paymeuz_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_create` datetime NOT NULL,
  `transact_id` varchar(255) NOT NULL,
  `op_transact_id` varchar(255) NOT NULL,
  `op_customer_id` varchar(255) NOT NULL,
  `amount` double NOT NULL DEFAULT 0,
  `state` tinyint(2) NOT NULL DEFAULT 0,
  `payme_transact_timestamp` int(13) NOT NULL,
  `create_timestamp` int(13) NOT NULL DEFAULT 0,
  `perform_timestamp` int(13) NOT NULL DEFAULT 0,
  `cancel_timestamp` int(13) NOT NULL DEFAULT 0,
  `cancel_reason` varchar(255) NOT NULL DEFAULT '',
  `receivers` text DEFAULT NULL
PRIMARY KEY (`id`),
KEY `date_create` (`date_create`),
KEY `transact_id` (`transact_id`),
KEY `op_transact_id` (`op_transact_id`),
KEY `op_customer_id` (`op_customer_id`),
KEY `payme_transact_time` (`payme_transact_time`)
KEY `create_timestamp` (`create_timestamp`)
KEY `perform_timestamp` (`perform_timestamp`)
KEY `cancel_timestamp` (`cancel_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;