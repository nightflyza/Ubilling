SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- DB: `openpayz`
--



--
-- log transactions table
--


CREATE TABLE IF NOT EXISTS `op_transactions` (
  `id` int(11) NOT NULL auto_increment,
  `hash` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `summ` int(11) NOT NULL,
  `customerid` varchar(255) NOT NULL,
  `paysys` varchar(255) NOT NULL,
  `processed` tinyint(1) NOT NULL,
  `note` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- view for compat with ubilling
-- transform users.login -> ip2int(users.IP);
-- CREATE VIEW op_customers (realid,virtualid) AS SELECT users.login, INET_ATON(users.IP) from `users`;


-- view for compat with stargazer
-- transform users.login -> users.login;
-- CREATE VIEW op_customers (realid,virtualid) AS SELECT users.login, users.login from `users`;

-- --------------------------------------------------------

--
--  virtual customers abstraction layer
--


CREATE TABLE IF NOT EXISTS `op_customers` (
  `id` int(11) NOT NULL auto_increment,
  `realid` varchar(255) NOT NULL,
  `virtualid` varchar(255) NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

-- unique fields

ALTER TABLE `op_transactions` ADD UNIQUE ( `hash` );
ALTER TABLE `op_customers` ADD UNIQUE ( `realid` );
ALTER TABLE `op_customers` ADD UNIQUE ( `virtualid`);

