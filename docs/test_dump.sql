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