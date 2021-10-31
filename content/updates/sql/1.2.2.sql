CREATE TABLE IF NOT EXISTS `paymeuz_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_create` datetime NOT NULL,
  `transact_id` varchar(255) NOT NULL,
  `op_transact_id` varchar(255) NOT NULL,
  `op_payment_id` varchar(255) NOT NULL,
  `amount` double NOT NULL DEFAULT 0,
  `payme_transact_time` int(13) NOT NULL,
  `create_time` int(13) NOT NULL,
  `perform_time` int(13) NOT NULL,
  `transact_body` text DEFAULT null
PRIMARY KEY (`id`),
KEY `date_create` (`date_create`),
KEY `transact_id` (`transact_id`),
KEY `op_transact_id` (`op_transact_id`),
KEY `op_payment_id` (`op_payment_id`),
KEY `payme_transact_time` (`payme_transact_time`)
KEY `create_time` (`create_time`)
KEY `perform_time` (`perform_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;