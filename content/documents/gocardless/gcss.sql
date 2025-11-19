CREATE TABLE IF NOT EXISTS `gcss_mandates` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `login` varchar(250) NOT NULL,
    `mandate_id` varchar(50) NOT NULL,
    `customer_id` varchar(50) NOT NULL,
    `creation_date` datetime NOT NULL,
    `status` varchar(250) DEFAULT '',
    `canceled` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY (`mandate_id`),
    KEY (`login`)
)  ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `gcss_charges` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `login` varchar(250) NOT NULL,
    `mandate_id` varchar(50) NOT NULL,
    `attempt` tinyint(4) NOT NULL DEFAULT 0,
    `last_payment_id` varchar(50) NOT NULL DEFAULT '',
    `last_payment_invoice` varchar(50) NOT NULL DEFAULT '',
    `last_charge_date` date NOT NULL,
    `next_charge_date` date NOT NULL,
    `gcss_charge_date` date NOT NULL,
    `gcss_payout_date` date NOT NULL,
    `credit_sum_charged` tinyint(1) NOT NULL DEFAULT 0,
    `lst_paym_succeeded` tinyint(1) NOT NULL DEFAULT 0,
    `lst_paym_failed` tinyint(1) NOT NULL DEFAULT 0,
    `warn_mail_send` tinyint(1) NOT NULL DEFAULT 0,
    `debtor` tinyint(1) NOT NULL DEFAULT 0,
    `charge_failed` tinyint(1) NOT NULL DEFAULT 0,
    `mandate_canceled` tinyint(1) NOT NULL DEFAULT 0,
    `status` varchar(250) DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY (`mandate_id`),
    KEY (`login`)
)  ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `gcss_events` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `login` varchar(250) NOT NULL,
    `event` varchar(150) NOT NULL,
    `event_date` datetime NOT NULL,
    `session_token` varchar(40) DEFAULT '',
    `redir_flow_id` varchar(100) DEFAULT '',
    PRIMARY KEY (`id`),
    KEY (`login`, `session_token`, `redir_flow_id`)
)  ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;