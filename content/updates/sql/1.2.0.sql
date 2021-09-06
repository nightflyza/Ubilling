CREATE TABLE IF NOT EXISTS `stigma` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scope` varchar(64) DEFAULT NULL,
  `itemid` varchar(128) NOT NULL,
  `state` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(64) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `extcontras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contras_id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `address_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `payday` tinyint(3) DEFAULT NULL,
  `date_create` datetime NOT NULL,
PRIMARY KEY (`id`),
KEY `contras_id` (`contras_id`),
KEY `contract_id` (`contract_id`),
KEY `address_id` (`address_id`),
KEY `period_id` (`period_id`),
KEY `payday` (`payday`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `extcontras_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `edrpo` varchar(100) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `name` (`name`),
KEY `edrpo` (`edrpo`),
KEY `contact` (`contact`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `extcontras_contracts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contract` varchar(150) DEFAULT NULL,
  `date_start` date NOT NULL,
  `date_end` date DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `full_sum` double DEFAULT 0,
  `autoprolong` tinyint(3) DEFAULT 1,
  `notes` varchar(255) DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `contract` (`contract`),
KEY `date_start` (`date_start`),
KEY `date_end` (`date_end`),
KEY `subject` (`subject`),
KEY `full_sum` (`full_sum`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `extcontras_address` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `address` varchar(255) NOT NULL,
  `summ`  double DEFAULT 0,
  `contract_notes` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `extcontras_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_name` varchar(100) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `extcontras_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contras_rec_id` int(11) NOT NULL,
  `internal_number` varchar(150) DEFAULT '',
  `invoice_number` varchar(150) NOT NULL,
  `date` date NOT NULL,
  `summ` double DEFAULT 0,
  `summ_vat` double DEFAULT 0,
  `notes` varchar(250) DEFAULT '',
  `incoming` tinyint(1) DEFAULT 0,
  `outgoing` tinyint(1) DEFAULT 0,
PRIMARY KEY (`id`),
KEY `contras_rec_id` (`contras_rec_id`),
KEY `invoice_number` (`invoice_number`),
KEY `date` (`date`),
KEY `summ` (`summ`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `extcontras_money` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_id` int(11) NOT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `address_id` int(11) DEFAULT NULL,
  `accrual_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `purpose` varchar(255) NOT NULL DEFAULT '',
  `date` datetime NOT NULL,
  `date_edit` datetime NOT NULL,
  `summ_accrual` double DEFAULT 0,
  `summ_payment` double DEFAULT 0,
  `incoming` tinyint(1) DEFAULT 0,
  `outgoing` tinyint(1) DEFAULT 0,
  `paynotes` varchar(255) NOT NULL DEFAULT '',
PRIMARY KEY (`id`),
KEY `profile_id` (`profile_id`),
KEY `contract_id` (`contract_id`),
KEY `address_id` (`address_id`),
KEY `accrual_id` (`accrual_id`),
KEY `purpose` (`purpose`),
KEY `date` (`date`),
KEY `date_edit` (`date_edit`),
KEY `summ_accrual` (`summ_accrual`),
KEY `summ_payment` (`summ_payment`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `extcontras_missed_payms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contras_rec_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `address_id` int(11) DEFAULT NULL,
  `period_id` int(11) NOT NULL,
  `payday` tinyint(3) DEFAULT NULL,
  `date_payment` date NOT NULL,
  `date_expired` datetime NOT NULL,
  `date_payed` datetime DEFAULT NULL,
  `summ_payment` double DEFAULT 0,
PRIMARY KEY (`id`),
KEY `contras_rec_id` (`contras_rec_id`),
KEY `profile_id` (`profile_id`),
KEY `contract_id` (`contract_id`),
KEY `address_id` (`address_id`),
KEY `period_id` (`period_id`),
KEY `date_payment` (`date_payment`),
KEY `date_payed` (`date_payed`),
KEY `summ_payment` (`summ_payment`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

