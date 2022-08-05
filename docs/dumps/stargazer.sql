
-- clean Stargazer MySQL dump. Must be applied before ubilling dump.

DROP TABLE IF EXISTS `admins`;

CREATE TABLE `admins` (
  `login` varchar(40) NOT NULL DEFAULT '',
  `password` varchar(150) DEFAULT '*',
  `ChgConf` tinyint(4) DEFAULT '0',
  `ChgPassword` tinyint(4) DEFAULT '0',
  `ChgStat` tinyint(4) DEFAULT '0',
  `ChgCash` tinyint(4) DEFAULT '0',
  `UsrAddDel` tinyint(4) DEFAULT '0',
  `ChgTariff` tinyint(4) DEFAULT '0',
  `ChgAdmin` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


LOCK TABLES `admins` WRITE;
INSERT INTO `admins` VALUES ('admin','geahonjehjfofnhammefahbbbfbmpkmkmmefahbbbfbmpkmkmmefahbbbfbmpkmkaa',1,1,1,1,1,1,1);
UNLOCK TABLES;


DROP TABLE IF EXISTS `info`;
CREATE TABLE `info` (
  `version` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `info` WRITE;
INSERT INTO `info` VALUES (1);
UNLOCK TABLES;


DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `login` varchar(40) DEFAULT '',
  `id` bigint(20) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `lastSendTime` int(11) DEFAULT NULL,
  `creationTime` int(11) DEFAULT NULL,
  `showTime` int(11) DEFAULT NULL,
  `stgRepeat` int(11) DEFAULT NULL,
  `repeatPeriod` int(11) DEFAULT NULL,
  `text` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `stat`;
CREATE TABLE `stat` (
  `login` varchar(50) DEFAULT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `year` smallint(6) DEFAULT NULL,
  `U0` bigint(20) DEFAULT NULL,
  `D0` bigint(20) DEFAULT NULL,
  `U1` bigint(20) DEFAULT NULL,
  `D1` bigint(20) DEFAULT NULL,
  `U2` bigint(20) DEFAULT NULL,
  `D2` bigint(20) DEFAULT NULL,
  `U3` bigint(20) DEFAULT NULL,
  `D3` bigint(20) DEFAULT NULL,
  `U4` bigint(20) DEFAULT NULL,
  `D4` bigint(20) DEFAULT NULL,
  `U5` bigint(20) DEFAULT NULL,
  `D5` bigint(20) DEFAULT NULL,
  `U6` bigint(20) DEFAULT NULL,
  `D6` bigint(20) DEFAULT NULL,
  `U7` bigint(20) DEFAULT NULL,
  `D7` bigint(20) DEFAULT NULL,
  `U8` bigint(20) DEFAULT NULL,
  `D8` bigint(20) DEFAULT NULL,
  `U9` bigint(20) DEFAULT NULL,
  `D9` bigint(20) DEFAULT NULL,
  `cash` double DEFAULT NULL,
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



DROP TABLE IF EXISTS `tariffs`;
CREATE TABLE `tariffs` (
  `name` varchar(40) NOT NULL DEFAULT '',
  `PriceDayA0` double DEFAULT '0',
  `PriceDayB0` double DEFAULT '0',
  `PriceNightA0` double DEFAULT '0',
  `PriceNightB0` double DEFAULT '0',
  `Threshold0` int(11) DEFAULT '0',
  `Time0` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount0` int(11) DEFAULT '0',
  `SinglePrice0` int(11) DEFAULT '0',
  `PriceDayA1` double DEFAULT '0',
  `PriceDayB1` double DEFAULT '0',
  `PriceNightA1` double DEFAULT '0',
  `PriceNightB1` double DEFAULT '0',
  `Threshold1` int(11) DEFAULT '0',
  `Time1` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount1` int(11) DEFAULT '0',
  `SinglePrice1` int(11) DEFAULT '0',
  `PriceDayA2` double DEFAULT '0',
  `PriceDayB2` double DEFAULT '0',
  `PriceNightA2` double DEFAULT '0',
  `PriceNightB2` double DEFAULT '0',
  `Threshold2` int(11) DEFAULT '0',
  `Time2` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount2` int(11) DEFAULT '0',
  `SinglePrice2` int(11) DEFAULT '0',
  `PriceDayA3` double DEFAULT '0',
  `PriceDayB3` double DEFAULT '0',
  `PriceNightA3` double DEFAULT '0',
  `PriceNightB3` double DEFAULT '0',
  `Threshold3` int(11) DEFAULT '0',
  `Time3` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount3` int(11) DEFAULT '0',
  `SinglePrice3` int(11) DEFAULT '0',
  `PriceDayA4` double DEFAULT '0',
  `PriceDayB4` double DEFAULT '0',
  `PriceNightA4` double DEFAULT '0',
  `PriceNightB4` double DEFAULT '0',
  `Threshold4` int(11) DEFAULT '0',
  `Time4` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount4` int(11) DEFAULT '0',
  `SinglePrice4` int(11) DEFAULT '0',
  `PriceDayA5` double DEFAULT '0',
  `PriceDayB5` double DEFAULT '0',
  `PriceNightA5` double DEFAULT '0',
  `PriceNightB5` double DEFAULT '0',
  `Threshold5` int(11) DEFAULT '0',
  `Time5` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount5` int(11) DEFAULT '0',
  `SinglePrice5` int(11) DEFAULT '0',
  `PriceDayA6` double DEFAULT '0',
  `PriceDayB6` double DEFAULT '0',
  `PriceNightA6` double DEFAULT '0',
  `PriceNightB6` double DEFAULT '0',
  `Threshold6` int(11) DEFAULT '0',
  `Time6` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount6` int(11) DEFAULT '0',
  `SinglePrice6` int(11) DEFAULT '0',
  `PriceDayA7` double DEFAULT '0',
  `PriceDayB7` double DEFAULT '0',
  `PriceNightA7` double DEFAULT '0',
  `PriceNightB7` double DEFAULT '0',
  `Threshold7` int(11) DEFAULT '0',
  `Time7` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount7` int(11) DEFAULT '0',
  `SinglePrice7` int(11) DEFAULT '0',
  `PriceDayA8` double DEFAULT '0',
  `PriceDayB8` double DEFAULT '0',
  `PriceNightA8` double DEFAULT '0',
  `PriceNightB8` double DEFAULT '0',
  `Threshold8` int(11) DEFAULT '0',
  `Time8` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount8` int(11) DEFAULT '0',
  `SinglePrice8` int(11) DEFAULT '0',
  `PriceDayA9` double DEFAULT '0',
  `PriceDayB9` double DEFAULT '0',
  `PriceNightA9` double DEFAULT '0',
  `PriceNightB9` double DEFAULT '0',
  `Threshold9` int(11) DEFAULT '0',
  `Time9` varchar(15) DEFAULT '0:0-0:0',
  `NoDiscount9` int(11) DEFAULT '0',
  `SinglePrice9` int(11) DEFAULT '0',
  `PassiveCost` double DEFAULT '0',
  `Fee` double DEFAULT '0',
  `Free` double DEFAULT '0',
  `TraffType` varchar(10) DEFAULT '',
  `period` varchar(32) NOT NULL DEFAULT 'month',
  `change_policy` varchar(32) NOT NULL DEFAULT 'allow',
  `change_policy_timeout` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `login` varchar(50) NOT NULL DEFAULT '',
  `Password` varchar(150) NOT NULL DEFAULT '*',
  `Passive` int(3) DEFAULT '0',
  `Down` int(3) DEFAULT '0',
  `DisabledDetailStat` int(3) DEFAULT '0',
  `AlwaysOnline` int(3) DEFAULT '0',
  `Tariff` varchar(40) NOT NULL DEFAULT '',
  `Address` varchar(254) NOT NULL DEFAULT '',
  `Phone` varchar(128) NOT NULL DEFAULT '',
  `Email` varchar(50) NOT NULL DEFAULT '',
  `Note` text NOT NULL,
  `RealName` varchar(254) NOT NULL DEFAULT '',
  `StgGroup` varchar(40) NOT NULL DEFAULT '',
  `Credit` double DEFAULT '0',
  `TariffChange` varchar(40) NOT NULL DEFAULT '',
  `Userdata0` varchar(254) NOT NULL,
  `Userdata1` varchar(254) NOT NULL,
  `Userdata2` varchar(254) NOT NULL,
  `Userdata3` varchar(254) NOT NULL,
  `Userdata4` varchar(254) NOT NULL,
  `Userdata5` varchar(254) NOT NULL,
  `Userdata6` varchar(254) NOT NULL,
  `Userdata7` varchar(254) NOT NULL,
  `Userdata8` varchar(254) NOT NULL,
  `Userdata9` varchar(254) NOT NULL,
  `CreditExpire` int(11) DEFAULT '0',
  `IP` varchar(254) DEFAULT '*',
  `D0` bigint(30) DEFAULT '0',
  `U0` bigint(30) DEFAULT '0',
  `D1` bigint(30) DEFAULT '0',
  `U1` bigint(30) DEFAULT '0',
  `D2` bigint(30) DEFAULT '0',
  `U2` bigint(30) DEFAULT '0',
  `D3` bigint(30) DEFAULT '0',
  `U3` bigint(30) DEFAULT '0',
  `D4` bigint(30) DEFAULT '0',
  `U4` bigint(30) DEFAULT '0',
  `D5` bigint(30) DEFAULT '0',
  `U5` bigint(30) DEFAULT '0',
  `D6` bigint(30) DEFAULT '0',
  `U6` bigint(30) DEFAULT '0',
  `D7` bigint(30) DEFAULT '0',
  `U7` bigint(30) DEFAULT '0',
  `D8` bigint(30) DEFAULT '0',
  `U8` bigint(30) DEFAULT '0',
  `D9` bigint(30) DEFAULT '0',
  `U9` bigint(30) DEFAULT '0',
  `Cash` double DEFAULT '0',
  `FreeMb` double DEFAULT '0',
  `LastCashAdd` double DEFAULT '0',
  `LastCashAddTime` int(11) DEFAULT '0',
  `PassiveTime` int(11) DEFAULT '0',
  `LastActivityTime` int(11) DEFAULT '0',
  `NAS` varchar(17) NOT NULL,
  PRIMARY KEY (`login`),
  KEY `AlwaysOnline` (`AlwaysOnline`),
  KEY `IP` (`IP`),
  KEY `Address` (`Address`),
  KEY `Tariff` (`Tariff`),
  KEY `Phone` (`Phone`),
  KEY `Email` (`Email`),
  KEY `RealName` (`RealName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
