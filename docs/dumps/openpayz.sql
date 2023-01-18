
-- Default OpenPayz MySQL preset. 
-- Must be applied after ubilling dump.

-- transactions log table
CREATE TABLE IF NOT EXISTS `op_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `summ` double NOT NULL,
  `customerid` varchar(255) NOT NULL,
  `paysys` varchar(255) NOT NULL,
  `processed` tinyint(1) NOT NULL,
  `note` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


-- default customers mappings view
CREATE VIEW op_customers (realid,virtualid) AS SELECT users.login, CRC32(users.login) FROM users LEFT JOIN op_denied ON users.login = op_denied.login WHERE op_denied.login IS NULL;



