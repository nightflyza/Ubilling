-- -----------------------------------------------------
-- Table `realname`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `realname` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `login` VARCHAR(45) NULL ,
  `realname` VARCHAR(255) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `weblogs`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `weblogs` (
  `id` int(11) NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `admin` varchar(45) default NULL,
  `ip` varchar(64) default NULL,
  `event` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `date` (`date`),
  KEY `date_2` (`date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


-- -----------------------------------------------------
-- Table `phones`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `phones` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `login` VARCHAR(45) NULL ,
  `phone` VARCHAR(255) NULL ,
  `mobile` VARCHAR(255) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `speeds`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `speeds` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `tariff` VARCHAR(45) NULL ,
  `speeddown` VARCHAR(45) NULL ,
  `speedup` VARCHAR(45) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `city`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `city` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `cityname` VARCHAR(255) NOT NULL ,
  `cityalias` VARCHAR(45) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `street`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `street` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `cityid` INT NOT NULL ,
  `streetname` VARCHAR(255) NOT NULL ,
  `streetalias` VARCHAR(45) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `build`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `build` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `streetid` INT NOT NULL ,
  `buildnum` VARCHAR(10) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `apt`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `apt` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `buildid` INT NOT NULL ,
  `entrance` VARCHAR(15) NULL ,
  `floor` VARCHAR(15) NULL ,
  `apt` VARCHAR(5) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `networks`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `networks` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `startip` VARCHAR(45) NOT NULL ,
  `endip` VARCHAR(45) NOT NULL ,
  `desc` VARCHAR(45) NOT NULL ,
  `nettype` VARCHAR(20) NOT NULL ,
    PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `userreg`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `userreg` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `date` DATETIME NOT NULL ,
  `admin` VARCHAR(45) NOT NULL ,
  `login` VARCHAR(45) NOT NULL ,
  `address` VARCHAR(255) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `services`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `netid` int(11) NOT NULL,
  `desc` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `contracts`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `contracts` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `login` VARCHAR(45) NOT NULL ,
  `contract` VARCHAR(255) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `tagtypes`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tagtypes` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `tagname` VARCHAR(255) NOT NULL ,
  `tagcolor` VARCHAR(15) NOT NULL ,
  `tagsize` INT NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `tags`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tags` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `tagid` INT NOT NULL ,
  `login` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `servtariff`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `servtariff` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `serviceid` INT NOT NULL ,
  `tariffs` VARCHAR(255) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `nethosts`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `nethosts` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `netid` INT NOT NULL ,
  `ip` VARCHAR(45) NOT NULL ,
  `mac` VARCHAR(45) NULL DEFAULT NULL ,
  `option` VARCHAR(45) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `dhcp`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `dhcp` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `netid` INT NOT NULL ,
  `dhcpconfig` TEXT  NULL ,
  `confname` VARCHAR(255) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `payments`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `payments` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `login` VARCHAR(45) NOT NULL ,
  `date` DATETIME NOT NULL ,
  `balance` VARCHAR(45) NOT NULL ,
  `summ` VARCHAR(45) NOT NULL ,
  `cashtypeid` INT NOT NULL ,
  `note` VARCHAR(45) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `cashtype`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `cashtype` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `cashtype` VARCHAR(50) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `emails`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `emails` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `login` VARCHAR(45) NOT NULL ,
  `email` VARCHAR(255) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `cardbank`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `cardbank` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `serial` VARCHAR(255) NOT NULL ,
  `cash` VARCHAR(45) NOT NULL ,
  `admin` VARCHAR(45) NOT NULL ,
  `date` DATETIME NOT NULL ,
  `active` TINYINT(1)  NOT NULL ,
  `used` TINYINT(1)  NOT NULL ,
  `usedate` DATETIME NULL ,
  `usedlogin` VARCHAR(45) NOT NULL ,
  `usedip` VARCHAR(45) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `cardbrute`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `cardbrute` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `serial` VARCHAR(255) NOT NULL ,
  `date` DATETIME NOT NULL ,
  `login` VARCHAR(45) NOT NULL ,
  `ip` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `switches`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `switches` (
  `id` INT NOT NULL ,
  `modelid` INT NOT NULL ,
  `ip` VARCHAR(45) NULL ,
  `desc` VARCHAR(255) NOT NULL ,
  `location` VARCHAR(255) NULL ,
  `snmp` VARCHAR(45) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `switchmodels`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `switchmodels` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `modelname` VARCHAR(255) NOT NULL ,
  `ports` INT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `cpe`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `cpe` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `cpemodelid` INT NOT NULL ,
  `ip` VARCHAR(45) NULL ,
  `desc` VARCHAR(255) NOT NULL ,
  `location` VARCHAR(255) NULL ,
  `snmp` VARCHAR(45) NULL ,
  `netid` INT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `cpetypes`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `cpetypes` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `cpemodel` VARCHAR(45) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `directions`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `directions` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `rulenumber` INT NOT NULL ,
  `rulename` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `jobtypes`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `jobtypes` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `jobname` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `jobs`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` int(11) NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `jobid` int(11) NOT NULL,
  `workerid` int(11) NOT NULL,
  `login` varchar(45) NOT NULL,
  `note` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `employee`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `employee` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  `appointment` VARCHAR(255) NOT NULL ,
  `active` TINYINT(1)  NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `address`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `address` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`login` VARCHAR( 45 ) NOT NULL ,
`aptid` INT NOT NULL
) ENGINE = MYISAM  DEFAULT CHARSET=UTF8;


-- -----------------------------------------------------
-- Table `taskman`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `taskman` (
  `id` int(11) NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `address` varchar(255) NOT NULL,
  `jobtype` int(11) NOT NULL,
  `jobnote` varchar(255) default NULL,
  `phone` varchar(255) default NULL,
  `employee` int(11) NOT NULL,
  `employeedone` int(11) NOT NULL,
  `donenote` varchar(255) default NULL,
  `startdate` date NOT NULL,
  `enddate` date default NULL,
  `admin` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=UTF8;

CREATE TABLE  IF NOT EXISTS `userspeeds` (
`id` INT NOT NULL AUTO_INCREMENT ,
`login` VARCHAR( 255 ) NOT NULL ,
`speed` INT NOT NULL ,
PRIMARY KEY ( `id` )
) ENGINE = MYISAM  DEFAULT CHARSET=UTF8; 

CREATE TABLE `notes` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`login` VARCHAR( 255 ) NOT NULL ,
`note` VARCHAR( 255 ) NOT NULL
) ENGINE = MYISAM  DEFAULT CHARSET=UTF8;

CREATE TABLE `nas` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`netid` INT NOT NULL ,
`nasip` VARCHAR( 255 )  NOT NULL,
`nasname` VARCHAR( 255 ) NOT NULL ,
`nastype` VARCHAR( 45 ) NULL,
`bandw`  VARCHAR( 255 ) NULL
) ENGINE = MYISAM DEFAULT CHARSET=UTF8;

CREATE TABLE `vservices` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`tagid` INT NOT NULL ,
`price` INT NOT NULL ,
`cashtype` VARCHAR ( 40 ) NOT NULL ,
`priority` INT NOT NULL
) ENGINE = MYISAM DEFAULT CHARSET=UTF8;

CREATE TABLE `vcash` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`login` VARCHAR( 255 ) NOT NULL ,
`cash` INT NOT NULL
) ENGINE = MYISAM DEFAULT CHARSET=UTF8;

CREATE TABLE IF NOT EXISTS `vcashlog` (
  `id` int(11) NOT NULL auto_increment,
  `login` varchar(45) NOT NULL,
  `date` datetime NOT NULL,
  `balance` varchar(45) NOT NULL,
  `summ` varchar(45) NOT NULL,
  `cashtypeid` int(11) NOT NULL,
  `note` varchar(45) default NULL,
  PRIMARY KEY  (`id`),
  KEY `login` (`login`),
  KEY `date` (`date`),
  KEY `login_2` (`login`),
  KEY `date_2` (`date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


-- docsis modem support tables
CREATE TABLE IF NOT EXISTS `modems` (
  `id` int(11) NOT NULL auto_increment,
  `maclan` varchar(255) NOT NULL,
  `macusb` varchar(255) NOT NULL,
  `date` date default NULL,
  `ip` varchar(25) NOT NULL,
  `conftemplate` varchar(20) NOT NULL,
  `userbind` varchar(100) default NULL,
  `nic` varchar(100) NOT NULL,
  `note` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `modem_templates` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `body` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `contrahens` (
  `id` int(11) NOT NULL auto_increment,
  `bankacc` varchar(255)  NULL,
  `bankname` varchar(255)  NULL,
  `bankcode` varchar(255)  NULL,
  `edrpo` varchar(255)  NULL,
  `ipn` varchar(255)  NULL,
  `licensenum` varchar(255)  NULL,
  `juraddr` varchar(255)  NULL,
  `phisaddr` varchar(255)  NULL,
  `phone` varchar(255)  NULL,
  `contrname` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `ahenassign` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`ahenid` INT NOT NULL ,
`streetname` VARCHAR( 255 ) NOT NULL
) ENGINE = MYISAM DEFAULT CHARSET=utf8;


-- midle patch
-- ALTER TABLE `services` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;
-- ALTER TABLE `weblogs` ADD `ip` VARCHAR( 64 ) NULL AFTER `admin`;
INSERT INTO `cashtype` (`Id`, `cashtype`) VALUES (1, 'Cash money');

INSERT INTO `directions` (`id`, `rulenumber`, `rulename`) VALUES (1, 0, 'Internet');


-- indexes tuning
ALTER TABLE `address` ADD INDEX ( `login` );
ALTER TABLE `address` ADD INDEX ( `aptid` );
ALTER TABLE `apt` ADD INDEX ( `apt` );
ALTER TABLE `apt` ADD INDEX ( `buildid` );
ALTER TABLE `build` ADD INDEX ( `buildnum` );
ALTER TABLE `build` ADD INDEX ( `streetid` );
ALTER TABLE `cashtype` ADD INDEX ( `cashtype` );
ALTER TABLE `city` ADD INDEX ( `cityname` );
ALTER TABLE `contracts` ADD INDEX ( `login` );
ALTER TABLE `contracts` ADD INDEX ( `login` );
ALTER TABLE `directions` ADD INDEX ( `rulenumber` );
ALTER TABLE `directions` ADD INDEX ( `rulename` );
ALTER TABLE `emails` ADD INDEX ( `login` );
ALTER TABLE `nas` ADD INDEX ( `netid` ) ;
ALTER TABLE `nethosts` ADD INDEX ( `netid` ); 
ALTER TABLE `nethosts` ADD INDEX ( `ip` );
ALTER TABLE `notes` ADD INDEX ( `login` );
ALTER TABLE `payments` ADD INDEX ( `login` );
ALTER TABLE `payments` ADD INDEX ( `date` );
ALTER TABLE `phones` ADD INDEX ( `phone` );
ALTER TABLE `phones` ADD INDEX ( `mobile` );
ALTER TABLE `realname` ADD INDEX ( `login` ); 
ALTER TABLE `realname` ADD INDEX ( `realname` );
ALTER TABLE `services` ADD INDEX ( `netid` );
ALTER TABLE `speeds` ADD INDEX ( `tariff` );
ALTER TABLE `speeds` ADD INDEX ( `speeddown` );
ALTER TABLE `speeds` ADD INDEX ( `speedup` );
ALTER TABLE `street` ADD INDEX ( `cityid` );
ALTER TABLE `street` ADD INDEX ( `streetname` ); 
ALTER TABLE `userreg` ADD INDEX ( `date` );
ALTER TABLE `userspeeds` ADD INDEX ( `speed` );
ALTER TABLE `userspeeds` ADD INDEX ( `login` );
ALTER TABLE `weblogs` ADD INDEX ( `date` ); 


-- 0.0.9 fixes

CREATE TABLE `cftypes` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`type` VARCHAR( 15 ) NOT NULL ,
`name` VARCHAR( 255 ) NOT NULL
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

CREATE TABLE `cfitems` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`typeid` INT NOT NULL ,
`login` VARCHAR( 255 ) NOT NULL ,
`content` TEXT NOT NULL
) ENGINE = MYISAM DEFAULT CHARSET=utf8;


-- 0.1.1 fixes
ALTER TABLE `switches` DROP PRIMARY KEY , ADD PRIMARY KEY ( `id` );
ALTER TABLE `switches` CHANGE `id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT;

-- 0.1.5 fixes
ALTER TABLE `payments` ADD `admin` VARCHAR( 255 ) NULL DEFAULT NULL AFTER `date`;

-- 0.1.7 update

CREATE TABLE `dshape_time` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`tariff`  VARCHAR( 255 ) NOT NULL ,
`threshold1` TIME NOT NULL ,
`threshold2` TIME NOT NULL ,
`speed` INT NOT NULL
) ENGINE = MYISAM  CHARSET=utf8;

-- 0.2.2 update

CREATE TABLE IF NOT EXISTS `ticketing` (
  `id` int(11) NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `replyid` int(11) default NULL,
  `status` int(11) default NULL,
  `from` varchar(255) default NULL,
  `to` varchar(255) default NULL,
  `text` text,
  `admin` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


-- 0.2.3 update

CREATE TABLE `catv_tariffs` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` VARCHAR( 255 ) NOT NULL ,
`price` FLOAT NOT NULL ,
`chans` INT NULL
) ENGINE = MYISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `catv_users` (
  `id` int(11) NOT NULL  AUTO_INCREMENT PRIMARY KEY,
  `contract` varchar(255) default NULL,
  `realname` varchar(255) default NULL,
  `street` varchar(255) default NULL,
  `build` varchar(15) default NULL,
  `apt` varchar(15) default NULL,
  `phone` varchar(255) default NULL,
  `tariff` int(11) default NULL,
  `tariff_nm` int(11) default NULL,
  `cash` float NOT NULL,
  `discount` float default NULL,
  `notes` varchar(255) default NULL,
  `decoder` int(11) default NULL,
  `inetlink` varchar(255) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `catv_payments` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`date` DATETIME NOT NULL ,
`userid` INT NOT NULL ,
`summ` FLOAT NOT NULL ,
`from_month` INT NOT NULL ,
`from_year` INT NOT NULL ,
`to_month` INT NOT NULL ,
`to_year` INT NOT NULL ,
`notes` VARCHAR( 255 ) NULL ,
`admin` VARCHAR( 255 ) NULL
) ENGINE = MYISAM CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `catv_fees` (
`id` INT NOT NULL  AUTO_INCREMENT PRIMARY KEY,
`date` DATETIME NOT NULL ,
`userid` INT NOT NULL ,
`summ` FLOAT NOT NULL ,
`balance` FLOAT NULL ,
`month` INT NOT NULL ,
`year` INT NOT NULL ,
`admin` VARCHAR( 255 )  NULL
) ENGINE = MYISAM CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `catv_activity` (
`id` INT NOT NULL  AUTO_INCREMENT PRIMARY KEY,
`userid` INT NOT NULL ,
`state` TINYINT NOT NULL ,
`date` DATETIME NOT NULL ,
`admin` VARCHAR( 255 ) NULL
) ENGINE = MYISAM CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `catv_signups` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`date` DATETIME NOT NULL ,
`userid` INT NOT NULL ,
`admin` VARCHAR ( 255 ) NULL
) ENGINE = MYISAM CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `catv_decoders` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`date` DATETIME NOT NULL ,
`userid` INT NOT NULL ,
`decoder` VARCHAR( 255 ) NOT NULL
) ENGINE = MYISAM CHARSET=utf8 AUTO_INCREMENT=1;


-- 0.2.4 update

CREATE TABLE `lousytariffs` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`tariff` VARCHAR( 255 ) NOT NULL
) ENGINE = MYISAM CHARSET=utf8 AUTO_INCREMENT=1;

-- 0.2.5 update

CREATE TABLE `genocide` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`tariff` VARCHAR( 255 ) NOT NULL ,
`speed` INT NOT NULL
) ENGINE = MYISAM CHARSET=utf8 AUTO_INCREMENT=1;

-- 0.2.6 update

CREATE TABLE `ubstats` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`key` VARCHAR( 40 ) NULL ,
`value` VARCHAR ( 255 ) NULL
) ENGINE = MYISAM CHARSET=utf8 AUTO_INCREMENT=1;

-- 0.2.7 update
CREATE TABLE `bankstaraw` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`filename` VARCHAR( 255 ) NOT NULL ,
`rawdata` TEXT NOT NULL
) ENGINE = MYISAM CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `bankstaparsed` (
  `id` int(11) NOT NULL auto_increment,
  `hash` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `row` int(11) NOT NULL,
  `realname` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `summ` float NOT NULL,
  `state` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.2.8 update

CREATE TABLE `nastemplates` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`nasid` INT NOT NULL ,
`template` TEXT NOT NULL
) ENGINE = MYISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;



CREATE TABLE `radattr` (
  `id` int(11) NOT NULL auto_increment,
  `login` varchar(255) NOT NULL,
  `attr` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 0.2.9 update

CREATE TABLE `ubstorage` (
  `id` int(11) NOT NULL auto_increment,
  `key` varchar(255) default NULL,
  `value` text,
  PRIMARY KEY  (`id`),
  KEY `key` (`key`),
  FULLTEXT KEY `value` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `sigreq` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`date` DATETIME NOT NULL ,
`state` TINYINT NOT NULL ,
`ip` VARCHAR( 40 ) NOT NULL ,
`street` VARCHAR( 255 ) NOT NULL ,
`build` VARCHAR( 40 ) NOT NULL ,
`apt` VARCHAR( 40 ) NOT NULL ,
`realname` VARCHAR( 255 ) NOT NULL ,
`phone` VARCHAR( 255 ) NOT NULL ,
`service` VARCHAR( 255 ) NOT NULL ,
`notes` TEXT default NULL
) ENGINE = MYISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.3.0 Updates

ALTER TABLE `taskman` CHANGE `jobnote` `jobnote` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `taskman` CHANGE `donenote` `donenote` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `taskman` ADD `status` INT NOT NULL , ADD INDEX ( STATUS );


CREATE TABLE `catv_bankstaraw` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`filename` VARCHAR( 255 ) NOT NULL ,
`rawdata` TEXT NOT NULL
) ENGINE = MYISAM CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `catv_bankstaparsed` (
  `id` int(11) NOT NULL auto_increment,
  `hash` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `row` int(11) NOT NULL,
  `realname` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
   `summ` float NOT NULL,
  `state` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.3.1 update

CREATE TABLE `uhw_log` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`date` DATETIME NOT NULL ,
`password` VARCHAR( 255 ) NOT NULL ,
`login` VARCHAR( 255 ) NOT NULL ,
`ip` VARCHAR( 255 ) NOT NULL ,
`nhid` INT NOT NULL ,
`oldmac` VARCHAR( 255 ) NULL ,
`newmac` VARCHAR( 255 ) NOT NULL
) ENGINE = MYISAM CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE `uhw_brute` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`date` DATETIME NOT NULL ,
`password` VARCHAR( 255 ) NOT NULL ,
`mac` VARCHAR( 255 ) NOT NULL
) ENGINE = MYISAM CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.3.2 update

CREATE TABLE IF NOT EXISTS `paymentscorr` (
  `id` int(11) NOT NULL auto_increment,
  `login` varchar(45) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(255) default NULL,
  `balance` varchar(45) NOT NULL,
  `summ` varchar(45) NOT NULL,
  `cashtypeid` int(11) NOT NULL,
  `note` varchar(45) default NULL,
  PRIMARY KEY  (`id`),
  KEY `login` (`login`),
  KEY `date` (`date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 0.3.4 update
ALTER TABLE `switches` ADD `geo` VARCHAR( 255 ) NULL DEFAULT NULL AFTER `snmp` ;

-- 0.3.5 update
CREATE TABLE IF NOT EXISTS `contractdates` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`contract` VARCHAR( 255 ) NOT NULL ,
`date` DATE NULL DEFAULT NULL
) ENGINE = MYISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `passportdata` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`login` VARCHAR( 255 ) NOT NULL ,
`birthdate` DATE NULL ,
`passportnum` VARCHAR( 255 ) NULL ,
`passportdate` DATE NULL ,
`passportwho` VARCHAR( 255 ) NULL ,
`pcity` VARCHAR( 255 ) NULL ,
`pstreet` VARCHAR( 255 ) NULL ,
`pbuild` VARCHAR( 10 ) NULL ,
`papt` VARCHAR( 10 ) NULL ,

INDEX ( `login` )
) ENGINE = MYISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


-- 0.3.6 update

CREATE TABLE `switchdeadlog` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`date` DATETIME NOT NULL ,
`timestamp` INT NOT NULL ,
`swdead` TEXT NOT NULL ,
INDEX ( `date` , `timestamp` )
) ENGINE = MYISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 0.3.7 update

CREATE TABLE IF NOT EXISTS `catv_paymentscorr` (
  `id` int(11) NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `userid` int(11) NOT NULL,
  `summ` float NOT NULL,
  `from_month` int(11) NOT NULL,
  `from_year` int(11) NOT NULL,
  `to_month` int(11) NOT NULL,
  `to_year` int(11) NOT NULL,
  `notes` varchar(255) default NULL,
  `admin` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `ub_im` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `read` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


-- 0.3.9 update
ALTER TABLE `ubstorage` CHANGE `value` `value` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

-- 0.4.1 update
ALTER TABLE `switchmodels` ADD `snmptemplate` VARCHAR( 255 ) DEFAULT NULL ;

-- 0.4.2 update
CREATE TABLE IF NOT EXISTS `deathtime` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `mtnasifaces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nasid` int(11) NOT NULL,
  `iface` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.4.3 update
ALTER TABLE `nas` ADD `options` TEXT DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `switchportassign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `switchid` int(11) NOT NULL,
  `port` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.4.6 update

ALTER TABLE `build` ADD `geo` VARCHAR( 255 ) DEFAULT NULL ;

ALTER TABLE  `networks` ADD `use_radius` TINYINT(1) NOT NULL DEFAULT '0';

-- 0.4.7 update

CREATE TABLE IF NOT EXISTS `watchdog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` TINYINT(1) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `checktype` varchar(255) NOT NULL,
  `param` varchar(255) NOT NULL,
  `operator` varchar(255) NOT NULL,
  `condition` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `oldresult` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `name` (`name`),
  KEY `oldresult` (`oldresult`),
  KEY `param` (`param`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- 0.4.8 update

CREATE TABLE IF NOT EXISTS `capab` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `stateid` int(11) NOT NULL DEFAULT '0',
  `notes` text,
  `price` varchar(255) DEFAULT NULL,
  `employeeid` int(11) DEFAULT NULL,
  `donedate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `state` (`stateid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `capabstates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state` varchar(255) NOT NULL,
  `color` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `employee` ADD `mobile` VARCHAR( 50 ) NULL DEFAULT NULL AFTER `appointment`;


-- 0.5.0 update

CREATE TABLE IF NOT EXISTS `docxtemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `admin` varchar(255) DEFAULT NULL,
  `public` tinyint(4) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `path` (`path`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `docxdocuments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  `public` tinyint(4) DEFAULT NULL,
  `templateid` int(11) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `public` (`public`),
  KEY `path` (`path`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.5.1 update
ALTER TABLE `taskman` ADD `smsdata` TEXT NULL DEFAULT NULL ;


CREATE TABLE IF NOT EXISTS `buildpassport` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buildid` int(11) NOT NULL,
  `owner` varchar(255) DEFAULT NULL,
  `ownername` varchar(255) DEFAULT NULL,
  `ownerphone` varchar(255) DEFAULT NULL,
  `ownercontact` varchar(255) DEFAULT NULL,
  `keys` tinyint(4) DEFAULT NULL,
  `accessnotices` varchar(255) DEFAULT NULL,
  `floors` int(11) DEFAULT NULL,
  `apts` int(11) DEFAULT NULL,
  `entrances` int(11) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `buildid` (`buildid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `ukv_tariffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tariffname` varchar(255) NOT NULL,
  `price` double NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ukv_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contract` varchar(40) DEFAULT NULL,
  `tariffid` int(11) DEFAULT NULL,
  `cash` double NOT NULL,
  `active` tinyint(4) NOT NULL,
  `realname` varchar(255) DEFAULT NULL,
  `passnum` varchar(40) DEFAULT NULL,
  `passwho` varchar(255) DEFAULT NULL,
  `passdate` date DEFAULT NULL,
  `paddr` varchar(255) DEFAULT NULL,
  `ssn` varchar(40) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `mobile` varchar(40) DEFAULT NULL,
  `regdate` datetime NOT NULL,
  `city` varchar(40) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `build` varchar(40) DEFAULT NULL,
  `apt` varchar(20) DEFAULT NULL,
  `inetlogin` varchar(40) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract` (`contract`),
  KEY `tariffid` (`tariffid`),
  KEY `cash` (`cash`),
  KEY `active` (`active`),
  KEY `regdate` (`regdate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `ukv_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(255) DEFAULT NULL,
  `balance` varchar(45) NOT NULL,
  `summ` varchar(45) NOT NULL,
  `visible` tinyint(4) NOT NULL,
  `cashtypeid` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`,`date`,`visible`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `ukv_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `yearmonth` varchar(42) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `yearmonth` (`yearmonth`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ukv_banksta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `hash` varchar(255) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `admin` varchar(255) NOT NULL,
  `contract` varchar(255) DEFAULT NULL,
  `summ` varchar(42) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `realname` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `pdate` varchar(42) DEFAULT NULL,
  `ptime` varchar(42) DEFAULT NULL,
  `processed` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `signup_prices_tariffs` (
  `tariff` varchar(40) NOT NULL,
  `price` double NOT NULL,
  PRIMARY KEY (`tariff`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `signup_prices_users` (
  `login` varchar(50) NOT NULL,
  `price` double NOT NULL,
  PRIMARY KEY (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 0.5.3 update
ALTER TABLE `employee` ADD `admlogin` VARCHAR( 255 ) NULL DEFAULT NULL AFTER `mobile`;

-- 0.5.4 update
CREATE TABLE IF NOT EXISTS `zbssclog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `zbsannouncements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `public` tinyint(4) DEFAULT '0',
  `type` varchar(20) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `public` (`public`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.5.5 UPDATE
CREATE TABLE IF NOT EXISTS `vols_docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) DEFAULT NULL,
  `date` datetime NOT NULL,
  `line_id` int(11) DEFAULT NULL,
  `mark_id` int(11) DEFAULT NULL,
  `path` varchar(128) NOT NULL DEFAULT '/',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `vols_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `point_start` varchar(255) NOT NULL,
  `point_end` varchar(255) NOT NULL,
  `fibers_amount` int(11) NOT NULL DEFAULT '0',
  `length` double NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `param_color` varchar(32) NOT NULL,
  `param_width` int(11) NOT NULL,
  `geo` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `vols_marks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_id` int(11) NOT NULL,
  `number` int(11) DEFAULT NULL,
  `placement` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `vols_marks_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon_color` varchar(255) NOT NULL DEFAULT 'blue',
  `icon_style` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `corp_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `corpname` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `doctype` int(11) DEFAULT NULL,
  `docnum` varchar(255) DEFAULT NULL,
  `docdate` date DEFAULT NULL,
  `bankacc` varchar(255) DEFAULT NULL,
  `bankname` varchar(255) DEFAULT NULL,
  `bankmfo` varchar(255) DEFAULT NULL,
  `edrpou` varchar(255) DEFAULT NULL,
  `ndstaxnum` varchar(255) DEFAULT NULL,
  `inncode` varchar(255) DEFAULT NULL,
  `taxtype` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `corp_taxtypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `corp_persons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `corpid` int(11) NOT NULL,
  `realname` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `im` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `appointment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `corp_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `corpid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.5.6 update

CREATE TABLE IF NOT EXISTS `netextpools` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `netid` int(11) NOT NULL,
  `pool` varchar(255) NOT NULL,
  `netmask` varchar(255) NOT NULL,
  `gw` varchar(255) DEFAULT NULL,
  `clientip` varchar(255) DEFAULT NULL,
  `broadcast` varchar(255) DEFAULT NULL,
  `vlan` varchar(255) DEFAULT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT  CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `netextips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poolid` int(11) NOT NULL,
  `ip` varchar(40)  NOT NULL,
  `nas` varchar(255) DEFAULT NULL,
  `iface` varchar(40) DEFAULT NULL,
  `mac` varchar(40) DEFAULT NULL,
  `switchid` int(11) DEFAULT NULL,
  `port` varchar(40) DEFAULT NULL,
  `vlan` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- 0.5.9 update
CREATE TABLE IF NOT EXISTS `sigreqconf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  KEY `key` (`key`),
  FULLTEXT KEY `value` (`value`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 0.6.0 update
CREATE TABLE IF NOT EXISTS `stickynotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner` varchar(255) NOT NULL,
  `createdate` datetime NOT NULL,
  `reminddate` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `text` text,
  PRIMARY KEY (`id`),
  KEY `owner` (`owner`),
  KEY `reminddate` (`reminddate`),
  KEY `active` (`active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.6.1 update
ALTER TABLE `jobtypes` ADD `jobcolor` VARCHAR(40) NULL AFTER `jobname`, ADD INDEX (`jobcolor`) ; 

ALTER TABLE `taskman` ADD `login` VARCHAR(255) NULL AFTER `address`, ADD INDEX (`login`) ; 

ALTER TABLE `taskman` ADD `starttime` TIME NULL AFTER `startdate`, ADD INDEX (`starttime`) ; 

CREATE TABLE IF NOT EXISTS `adcomments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scope` varchar(255) NOT NULL,
  `item` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(40) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `scope` (`scope`),
  KEY `item` (`item`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `ahenassignstrict` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agentid` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `vlan_pools` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`desc` varchar(32) DEFAULT "*",
`firstvlan` int(4) DEFAULT NULL,
`endvlan` int(4) DEFAULT NULL,
`qinq` int(1) DEFAULT NULL,
`svlan` int(4) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `vlanhosts` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`vlanpoolid` int(11) NOT NULL,
`login` varchar(32) DEFAULT "*",
`vlan` int(4) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `vlanhosts_qinq` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`vlanpoolid` int(11) NOT NULL,
`login` varchar(32) DEFAULT "*",
`svlan` int(4) DEFAULT NULL,
`cvlan` int(4) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `vlan_terminators` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`netid` int(4) DEFAULT NULL,
`vlanpoolid` int(4) DEFAULT NULL,
`ip` varchar(20) DEFAULT NULL,
`type` varchar (50) DEFAULT NULL,
`username` varchar(50) DEFAULT NULL,
`password` varchar(50) DEFAULT NULL,
`remote-id` varchar(50) DEFAULT NULL,
`interface` varchar(50) DEFAULT NULL,
`relay` varchar(50) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 0.6.3 update
CREATE TABLE IF NOT EXISTS `photostorage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scope` varchar(255) NOT NULL,
  `item` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(40) NOT NULL,
  `filename` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `scope` (`scope`),
  KEY `item` (`item`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- 0.6.4 update
ALTER TABLE `switches` ADD `parentid` INT NULL AFTER `geo`, ADD INDEX (`parentid`) ; 

-- 0.6.5 update
CREATE TABLE IF NOT EXISTS `switch_login` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`swid` int(5) DEFAULT NULL,
`swlogin` varchar(50) DEFAULT NULL,
`swpass` varchar(50) DEFAULT NULL,
`method` varchar(10) DEFAULT NULL,
`community` varchar(50) DEFAULT NULL,
`enable` varchar(3) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


ALTER TABLE `ukv_users` ADD `cableseal` VARCHAR(40) NULL AFTER `inetlogin`; 

-- 0.6.6 update
CREATE TABLE IF NOT EXISTS `condet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) DEFAULT NULL,
  `seal` varchar(40) DEFAULT NULL,
  `length` varchar(40) DEFAULT NULL,
  `price` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.6.7 update

CREATE TABLE IF NOT EXISTS `custmaps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `custmapsitems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mapid` int(11) DEFAULT NULL,
  `type` varchar(40) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mapid` (`mapid`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `pononu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `onumodelid` int(11) DEFAULT NULL,
  `oltid` int(11) DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `mac` varchar(20) DEFAULT NULL,
  `serial` varchar(255) DEFAULT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `cudiscounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `discount` double DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `days` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

 CREATE TABLE IF NOT EXISTS `capdata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `date` datetime DEFAULT NULL,
  `days` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `ukv_banksta` ADD `payid` INT NULL ; 

-- 0.6.9 update

CREATE TABLE IF NOT EXISTS `salary_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `taskid` int(11) DEFAULT NULL,
  `employeeid` int(11) NOT NULL,
  `jobtypeid` int(11) NOT NULL,
  `factor` double DEFAULT NULL,
  `overprice` double DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `salary_jobprices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jobtypeid` int(11) NOT NULL,
  `price` double NOT NULL,
  `unit` varchar(255) NOT NULL,
  `time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `salary_wages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employeeid` int(11) NOT NULL,
  `wage` double NOT NULL,
  `bounty` double NOT NULL,
  `worktime` int(11) NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `salary_paid` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jobid` int(11) NOT NULL,
  `employeeid` int(11) NOT NULL,
  `paid` double DEFAULT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `salary_timesheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date  NOT NULL,
  `employeeid` int(11) NOT NULL,
  `hours` int(11) NOT NULL DEFAULT '0',
  `holiday` tinyint(1) NOT NULL DEFAULT '0',
  `hospital` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `cemetery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- 0.7.0 update
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `wh_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `wh_itemtypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoryid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `unit` varchar(40) NOT NULL,
  `reserve` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categoryid` (`categoryid`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `wh_storages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `wh_contractors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `wh_in` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `itemtypeid` int(11) NOT NULL,
  `contractorid` int(11) NOT NULL,
  `count` double NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `storageid` int(11) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`itemtypeid`,`contractorid`,`storageid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `wh_out` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `desttype` varchar(40) NOT NULL,
  `destparam` varchar(255) NOT NULL,
  `storageid` int(11) NOT NULL,
  `itemtypeid` int(11) NOT NULL,
  `count` double NOT NULL,
  `price` double DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`storageid`,`itemtypeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `taskman` CHANGE `employeedone` `employeedone` INT(11) NULL; 

-- 0.7.1 update

CREATE TABLE IF NOT EXISTS `wh_reserve` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `storageid` int(11) NOT NULL,
  `itemtypeid` int(11) NOT NULL,
  `count` double NOT NULL,
  `employeeid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `storageid` (`storageid`),
  KEY `itemtypeid` (`itemtypeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `friendship` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friend` varchar(255) NOT NULL,
  `parent` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `friend` (`friend`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `taskman` ADD INDEX(`address`); 

ALTER TABLE `taskman` ADD INDEX(`startdate`); 

-- 0.7.2 update
ALTER TABLE `switch_login` ADD `snmptemplate` VARCHAR(32) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `taskmantrack` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskid` int(11) NOT NULL,
  `admin` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `taskid` (`taskid`,`admin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `vlan_mac_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(45) DEFAULT NULL,
  `vlan` int(4) DEFAULT NULL,
  `mac` varchar(45) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.7.3 update

CREATE TABLE IF NOT EXISTS `dealwithit` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `date` date NOT NULL,
 `login` varchar(45) NOT NULL,
 `action` varchar(45) NOT NULL,
 `param` varchar(45) DEFAULT NULL,
 `note` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mg_tariffs` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(255) NOT NULL,
 `fee` double DEFAULT NULL,
 `serviceid` varchar(45) DEFAULT NULL,
 `primary` TINYINT(1) NOT NULL DEFAULT '0',
 `freeperiod` TINYINT(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `mg_subscribers` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `login` varchar(255) NOT NULL,
 `tariffid`  int(11) NOT NULL,
 `actdate` DATETIME NOT NULL,
 `active` TINYINT(1) NOT NULL DEFAULT '0',
 `primary` TINYINT(1) NOT NULL DEFAULT '0',
 `freeperiod` TINYINT(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mg_history` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `login` varchar(255) NOT NULL,
 `tariffid`  int(11) NOT NULL,
 `actdate` DATETIME NOT NULL,
 `freeperiod` TINYINT(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `mg_queue` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `login` varchar(255) NOT NULL,
 `date` DATETIME NOT NULL,
 `action` varchar(45) NOT NULL,
 `tariffid` int(11)  NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.7.8

CREATE TABLE IF NOT EXISTS `exhorse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `u_totalusers` int(11) DEFAULT NULL,
  `u_activeusers` int(11) DEFAULT NULL,
  `u_inactiveusers` int(11) DEFAULT NULL,
  `u_frozenusers` int(11) DEFAULT NULL,
  `u_complextotal` int(11) DEFAULT NULL,
  `u_complexactive` int(11) DEFAULT NULL,
  `u_complexinactive` int(11) DEFAULT NULL,
  `u_signups` int(11) DEFAULT NULL,
  `u_citysignups` text,
  `f_totalmoney` double DEFAULT NULL,
  `f_paymentscount` int(11) DEFAULT NULL,
  `f_arpu` double DEFAULT NULL,
  `f_arpau` double DEFAULT NULL,
  `c_totalusers` int(11) DEFAULT NULL,
  `c_activeusers` int(11) DEFAULT NULL,
  `c_inactiveusers` int(11) DEFAULT NULL,
  `c_illegal` int(11) DEFAULT NULL,
  `c_complex` int(11) DEFAULT NULL,
  `c_social` int(11) DEFAULT NULL,
  `c_totalmoney` double DEFAULT NULL,
  `c_paymentscount` int(11) DEFAULT NULL,
  `c_arpu` double DEFAULT NULL,
  `c_arpau` double DEFAULT NULL,
  `c_totaldebt` double DEFAULT NULL,
  `c_signups` int(11) DEFAULT NULL,
  `a_totalcalls` int(11) DEFAULT NULL,
  `a_totalanswered` int(11) DEFAULT NULL,
  `a_totalcallsduration` int(11) DEFAULT NULL,
  `a_averagecallduration` int(11) DEFAULT NULL,
  `e_switches` int(11) DEFAULT NULL,
  `e_pononu` int(11) DEFAULT NULL,
  `e_docsis` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;