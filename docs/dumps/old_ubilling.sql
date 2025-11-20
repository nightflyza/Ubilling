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
  KEY `date` (`date`)
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
  `burstdownload` varchar(45) DEFAULT NULL ,
  `burstupload` varchar(45) DEFAULT NULL ,
  `bursttimedownload` varchar(45) DEFAULT NULL ,
  `burstimetupload` varchar(45) DEFAULT NULL ,
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
  PRIMARY KEY  (`id`)
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
  KEY `date` (`date`)
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

-- catv_* tables was here. Deprecated.

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `signup_prices_users` (
  `login` varchar(50) NOT NULL,
  `price` double NOT NULL,
  PRIMARY KEY (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `vols_marks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_id` int(11) NOT NULL,
  `number` int(11) DEFAULT NULL,
  `placement` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `vols_marks_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon_color` varchar(255) NOT NULL DEFAULT 'blue',
  `icon_style` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


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

-- 0.7.9

ALTER TABLE `switches` ADD `swid` VARCHAR(32) DEFAULT NULL;

ALTER TABLE `exhorse` ADD `f_cashmoney` DOUBLE NULL DEFAULT NULL AFTER `f_paymentscount`, ADD `f_cashcount` INT NULL DEFAULT NULL AFTER `f_cashmoney`;

-- 0.8.1

CREATE TABLE IF NOT EXISTS `policedog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `mac` varchar(40) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`mac`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `policedogalerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `mac` varchar(40) NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`mac`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `ukv_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tagtypeid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.8.2

ALTER TABLE `stickynotes` ADD `remindtime` TIME DEFAULT NULL AFTER `reminddate`, ADD INDEX (`remindtime`) ; 

ALTER TABLE `employee` ADD `telegram` VARCHAR(40) NULL DEFAULT NULL AFTER `mobile`; 

CREATE TABLE IF NOT EXISTS `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `branchesadmins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branchid` int(11) NOT NULL,
  `admin` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `branchesusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branchid` int(11) NOT NULL,
  `login` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `branchescities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branchid` int(11) NOT NULL,
  `cityid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `branchestariffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branchid` int(11) NOT NULL,
  `tariff` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `branchesservices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branchid` int(11) NOT NULL,
  `serviceid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `selling` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  `address` VARCHAR(255) NULL ,
  `geo` VARCHAR(255) NULL ,
  `contact` VARCHAR(255) NULL ,
  `count_cards` int(11) NULL ,
  `comment` TEXT  NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;

ALTER TABLE `cardbank` ADD `part` VARCHAR(255) NULL;

ALTER TABLE `cardbank` ADD `receipt_date` DATETIME NULL;

ALTER TABLE `cardbank` ADD `selling_id` int(11) NULL;

CREATE  TABLE IF NOT EXISTS `print_card` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `field` varchar(255) NOT NULL,
  `color` varchar(255) DEFAULT '',
  `font_size` int(11) DEFAULT NULL,
  `top` int(11) DEFAULT NULL,
  `left` int(11) DEFAULT NULL,
  `text` text,
  PRIMARY KEY (`id`)
) ENGINE = MyISAM DEFAULT CHARSET=UTF8;

INSERT INTO
    `print_card` (`title`, `field`, `color`, `font_size`, `top`, `left`, `text`)
VALUES
    ('Serial number', 'number', '0.0.0', '12', '80', '130', 'Номер № {number}'),
    ('Serial part', 'serial', '0.0.0', '12', '80', '110', 'Серия {serial}'),
    ('Price', 'rating', '139.0.139', '16', '120', '90', 'Номинал {sum}грн. '),
    ('Phone', 'phone', '0.0.0', '8', '160', '3', '+38(096)xxx-xx-xx, +38(096)xxx-xx-xx, +38(096)xxx-xx-xx'),
('Site', 'site', '0.0.0', '10', '15', '5', 'Сайт: xxx.xxx.ua');

ALTER TABLE `uhw_brute` ADD `login` VARCHAR(255) NOT NULL AFTER `password`;

-- 0.8.3

CREATE TABLE IF NOT EXISTS `wdycinfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `missedcount` int(11) DEFAULT NULL,
  `recallscount` int(11) DEFAULT NULL,
  `unsucccount` int(11) DEFAULT NULL,
  `missednumbers` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `taskman` ADD `change_admin` VARCHAR(255) NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `wh_reshist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `type` varchar(40) NOT NULL,
  `storageid` int(11) DEFAULT NULL,
  `itemtypeid` int(11) DEFAULT NULL,
  `count` double DEFAULT NULL,
  `employeeid` int(11) DEFAULT NULL,
  `admin` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`storageid`,`itemtypeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `wh_in` ADD `admin` VARCHAR(100) NULL DEFAULT NULL AFTER `notes`; 

ALTER TABLE `wh_out` ADD `admin` VARCHAR(100) NULL DEFAULT NULL AFTER `notes`; 

ALTER TABLE `employee` ADD `tagid` INT(11) NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `admannouncements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `admacquainted` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `admin` varchar(40) NOT NULL,
  `annid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`admin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.8.4
ALTER TABLE `switches` ADD `snmpwrite` VARCHAR(45) NULL AFTER `swid`;

ALTER TABLE `phones` ADD INDEX (`login`);

ALTER TABLE `print_card` ADD UNIQUE (`title`);

CREATE TABLE IF NOT EXISTS `dealwithithist` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `originalid` INT(11) NOT NULL, 
 `mtime` datetime NOT NULL,
 `date` date NOT NULL,
 `login` varchar(45) NOT NULL,
 `action` varchar(45) NOT NULL,
 `param` varchar(45) DEFAULT NULL,
 `note` varchar(45) DEFAULT NULL,
 `admin` varchar(50) DEFAULT NULL,
 `done` TINYINT(1)  NOT NULL ,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.8.5

CREATE TABLE IF NOT EXISTS `wcpedevices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modelid` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `mac` varchar(45) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `bridge` tinyint(4) NOT NULL DEFAULT '0',
  `uplinkapid` int(11) DEFAULT NULL,
  `uplinkcpeid` int(11) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `wcpeusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cpeid` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- 0.8.6
ALTER TABLE `salary_jobs` ADD INDEX(`taskid`);

ALTER TABLE `wh_out` ADD INDEX(`desttype`);

ALTER TABLE `wh_out` ADD INDEX(`destparam`); 

CREATE TABLE IF NOT EXISTS `polls` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_date` datetime DEFAULT '0000-00-00 00:00:00',
  `params` text NOT NULL,
  `admin` varchar(255) NOT NULL DEFAULT '',
  `voting` VARCHAR(255) NOT NULL DEFAULT 'Users',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `polls_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `poll_id` (`id`,`poll_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `polls_votes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `option_id` int(11) NOT NULL DEFAULT '0',
  `poll_id` int(11) NOT NULL DEFAULT '0',
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_poll` (`poll_id`,`login`) USING BTREE,
  UNIQUE KEY `login_poll_option` (`option_id`,`poll_id`,`login`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `zbsannhist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `annid` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `annid` (`annid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `vservices` ADD `fee_charge_always` TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS `zte_cards` (
`id` INT NOT NULL AUTO_INCREMENT, 
`swid` INT NOT NULL, 
`slot_number` INT NOT NULL, 
`card_name` VARCHAR(5) NOT NULL, 
PRIMARY KEY (`id`), 
KEY (`swid`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;
 
CREATE TABLE IF NOT EXISTS `zte_vlan_bind` (
`id` INT NOT NULL AUTO_INCREMENT,
`swid` INT NOT NULL,
`slot_number` INT NOT NULL,
`port_number` INT(2) NOT NULL,
`vlan` INT(4) NOT NULL,
PRIMARY KEY (`id`),
KEY (`swid`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;
 
ALTER TABLE `zte_cards` ADD COLUMN `chasis_number` INT (1) NOT NULL;

-- 0.8.7 update
CREATE TABLE IF NOT EXISTS `mobileext` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(64) NOT NULL,
  `mobile` varchar(64) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`,`mobile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 0.8.8 update
CREATE TABLE IF NOT EXISTS `smz_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `text` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `smz_filters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `filters` TEXT NOT NULL,
  PRIMARY KEY (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `smz_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `smz_nums` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numid` int(11) NOT NULL,
  `mobile` varchar(40) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `smz_excl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mobile` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ldap_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `groups` TEXT DEFAULT NULL,
  `changed` TINYINT(1)  NOT NULL ,
  PRIMARY KEY (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ldap_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ldap_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task` varchar(255) NOT NULL,
  `param` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


ALTER TABLE `wcpedevices` ADD `snmp` VARCHAR(45) NULL DEFAULT NULL AFTER `mac`;

-- 0.9.0 update

CREATE TABLE IF NOT EXISTS `frozen_charge_days` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `freeze_days_amount` smallint(3) NOT NULL DEFAULT 0,
  `freeze_days_used`  smallint(3) NOT NULL DEFAULT 0,
  `work_days_restore` smallint(3) NOT NULL DEFAULT 0,
  `days_worked` smallint(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `wdycinfo` ADD `totaltrytime` INT NULL DEFAULT NULL; 

ALTER TABLE `exhorse` ADD `a_recallunsuccess` DOUBLE NULL DEFAULT NULL ,
 ADD `a_recalltrytime` INT NULL DEFAULT NULL ,
 ADD `e_deadswintervals` INT NULL DEFAULT NULL ,
 ADD `t_sigreq` INT NULL DEFAULT NULL ,
 ADD `t_tickets` INT NULL DEFAULT NULL ,
 ADD `t_tasks` INT NULL DEFAULT NULL ,
 ADD `t_capabtotal` INT NULL DEFAULT NULL ,
 ADD `t_capabundone` INT NULL DEFAULT NULL ;
 
ALTER TABLE `nethosts` ADD UNIQUE `net-ip` (`netid`, `ip`);

CREATE TABLE IF NOT EXISTS `districtnames` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `districtdata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `districtid` int(11) NOT NULL,
  `cityid` int(11) DEFAULT NULL,
  `streetid` int(11) DEFAULT NULL,
  `buildid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `userreg` ADD INDEX `login` (`login`);

ALTER TABLE `dealwithithist` ADD `datetimedone` DATETIME NULL DEFAULT NULL AFTER `date`;

CREATE TABLE IF NOT EXISTS `taskmandone` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskid` int(11) DEFAULT NULL,
  `date` datetime NOT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `sms_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `srvmsgself_id` varchar(255) NOT NULL,
  `srvmsgpack_id` varchar(255) NOT NULL,
  `date_send` datetime NOT NULL,
  `date_statuschk` datetime NOT NULL,
  `delivered` tinyint(1) UNSIGNED DEFAULT 0,
  `no_statuschk` tinyint(1) UNSIGNED DEFAULT 0,
  `send_status` varchar(255) NOT NULL DEFAULT '',
  `msg_text` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `login` (`login`) USING BTREE,
  KEY `phone` (`phone`) USING BTREE,
  KEY `date_send` (`date_send`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `punchscripts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alias` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` text,
  PRIMARY KEY  (`id`),
  KEY `alias` (`alias`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `whiteboard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoryid` int(11) NOT NULL,
  `admin` varchar(255) NOT NULL,
  `employeeid` int(11) DEFAULT NULL,
  `createdate` datetime NOT NULL,
  `donedate` datetime DEFAULT NULL,
  `priority` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `text` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `taskmanlogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskid` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `admin` varchar(45) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `event` varchar(255) NOT NULL,
  `logs` text,
  PRIMARY KEY (`id`),
  KEY `taskid` (`taskid`) USING BTREE,
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `om_tariffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tariffid` int(11) NOT NULL,
  `tariffname` varchar(255) NOT NULL,
  `type` varchar(64) NOT NULL,
  `fee` DOUBLE DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `om_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `customerid` bigint(20) NOT NULL,
  `basetariffid` int(11) DEFAULT NULL,
  `bundletariffs` varchar(255) DEFAULT NULL,
  `active` int(11) DEFAULT NULL,
  `actdate` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `om_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerid` bigint(20) NOT NULL,
  `tariffid` int(11) DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `om_suspend` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 0.9.3 update
ALTER TABLE `ukv_users` ADD `tariffnmid` INT NULL AFTER `tariffid`;
ALTER TABLE `sms_history` ADD `smssrvid` INT(11) NOT NULL DEFAULT 0 AFTER `id`;
ALTER TABLE `sms_history` ADD INDEX(`smssrvid`);

CREATE TABLE IF NOT EXISTS `sms_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `login` varchar(255) NOT NULL,
  `passwd` varchar(255) NOT NULL,
  `url_addr` varchar(255) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `alpha_name` varchar(40) NOT NULL,
  `default_service` tinyint(1) UNSIGNED DEFAULT 0,
  `api_file_name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `sms_services_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sms_srv_id` int(11) NOT NULL,
  `user_login` varchar(255) DEFAULT NULL,
  `employee_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`user_login`),
  UNIQUE KEY (`employee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `switches_qinq` (
  `switchid` int(11) NOT NULL,
  `svlan` int(11) NOT NULL,
  `cvlan` int(11) NOT NULL,
  PRIMARY KEY (`switchid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `bankstamd` (
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

-- 0.9.4 update

CREATE TABLE IF NOT EXISTS `trinitytv_devices` (
  `id` int(11) NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  `subscriber_id` int(11) DEFAULT NULL,
  `mac` varchar(128) NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `trinitytv_subscribers` (
  `id` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  `contracttrinity` bigint(20) DEFAULT NULL,
  `tariffid` int(11) NOT NULL,
  `actdate` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `trinitytv_suspend` (
  `id` int(11) NOT NULL,
  `login` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `trinitytv_tariffs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `fee` double DEFAULT '0',
  `serviceid` varchar(45) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


ALTER TABLE `trinitytv_devices`  ADD PRIMARY KEY (`id`);
ALTER TABLE `trinitytv_subscribers`  ADD PRIMARY KEY (`id`);
ALTER TABLE `trinitytv_suspend`  ADD PRIMARY KEY (`id`);
ALTER TABLE `trinitytv_tariffs`  ADD PRIMARY KEY (`id`);
ALTER TABLE `trinitytv_devices`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `trinitytv_subscribers`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `trinitytv_suspend`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `trinitytv_tariffs`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sms_history` MODIFY `msg_text` varchar(500) NOT NULL DEFAULT '';

CREATE TABLE IF NOT EXISTS `pononuextusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `onuid` int(11) NOT NULL,
  `login` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `corp_persons` ADD COLUMN `notes` TEXT NULL AFTER `appointment`;

-- 0.9.5 update
ALTER TABLE `employee` ADD `amountLimit` VARCHAR(45) NOT NULL DEFAULT '0';

CREATE TABLE IF NOT EXISTS `callshist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `number` varchar(120) NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `stickyrevelations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner` varchar(255) NOT NULL,
  `showto` text,
  `createdate` datetime NOT NULL,
  `dayfrom` int(11) DEFAULT NULL,
  `dayto` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `text` text,
  PRIMARY KEY (`id`),
  KEY `owner` (`owner`),
  KEY `dayfrom` (`dayfrom`),
  KEY `dayto` (`dayto`),
  KEY `active` (`active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `trinitytv_tariffs` ADD `description` VARCHAR(128) NULL DEFAULT NULL AFTER `name`;

ALTER TABLE `wh_reshist` ADD `resid` INT NULL AFTER `id`; 

CREATE TABLE IF NOT EXISTS `mlg_ishimura` (
  `login` varchar(50) DEFAULT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `year` smallint(6) DEFAULT NULL,
  `U0` bigint(20) DEFAULT NULL,
  `D0` bigint(20) DEFAULT NULL,
  `cash` double DEFAULT NULL,
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

-- 0.9.6 update

CREATE TABLE IF NOT EXISTS `ddt_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tariffname` varchar(40) NOT NULL,
  `period` varchar(10) NOT NULL,
  `startnow` tinyint(4) NOT NULL,
  `duration` int(11) NOT NULL,
  `chargefee` tinyint(4) NOT NULL,
  `chargeuntilday` int(11) DEFAULT NULL,
  `tariffmove` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ddt_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(32) NOT NULL,
  `active` tinyint(4) NOT NULL,
  `startdate` datetime NOT NULL,
  `curtariff` varchar(40) NOT NULL,
  `enddate` date NOT NULL,
  `nexttariff` varchar(40) NOT NULL,
  `dwiid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `switch_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupname` varchar(255) NOT NULL,
  `groupdescr` varchar(500) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`groupname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `switch_groups_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `switch_id` int(11) NOT NULL,
  `sw_group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`switch_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 0.9.7 update

CREATE TABLE IF NOT EXISTS `capabhist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `capabid` int(11) NOT NULL,
  `admin` varchar(40) NOT NULL,
  `date` datetime NOT NULL,
  `type` varchar(40) NOT NULL,
  `event` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `ddt_options` ADD `setcredit` TINYINT NULL AFTER `chargeuntilday`;

-- 0.9.8 update
ALTER TABLE `pononu` ADD KEY login (`login`);

-- 0.9.9 update

CREATE TABLE IF NOT EXISTS `banksta2` (
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
  `canceled` tinyint(4) NOT NULL,
  `service_type` varchar(100) NOT NULL DEFAULT '',
  `payid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `banksta2_presets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `presetname` varchar(80) NOT NULL,
  `col_realname` varchar(20) DEFAULT '',
  `col_address` varchar(20) DEFAULT '',
  `col_paysum` varchar(20) DEFAULT '',
  `col_paypurpose` varchar(20) DEFAULT '',
  `col_paydate` varchar(20) DEFAULT '',
  `col_paytime` varchar(20) DEFAULT '',
  `col_contract` varchar(20) DEFAULT '',
  `guess_contract` tinyint(3) DEFAULT 0,
  `contract_delim_start` varchar(40) DEFAULT '',
  `contract_delim_end` varchar(40) DEFAULT '',
  `contract_min_len` tinyint(3) DEFAULT 0,
  `contract_max_len` tinyint(3) DEFAULT 0,
  `service_type` varchar(100) NOT NULL DEFAULT '',
  `inet_srv_start_delim` varchar(40) DEFAULT '',
  `inet_srv_end_delim` varchar(40) DEFAULT '',
  `inet_srv_keywords` varchar(200) DEFAULT '',
  `ukv_srv_start_delim` varchar(40) DEFAULT '',
  `ukv_srv_end_delim` varchar(40) DEFAULT '',
  `ukv_srv_keywords` varchar(200) DEFAULT '',
  `skip_row` tinyint(3) DEFAULT 0,
  `col_skiprow` varchar(20) DEFAULT '',
  `skip_row_keywords` varchar(200) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`presetname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `visor_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `regdate` datetime NOT NULL,
  `realname` varchar(250) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `chargecams` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `visor_cams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visorid` int(11) NOT NULL,
  `login` varchar(250) NOT NULL,
  `primary` tinyint(4) NOT NULL,
  `camlogin` varchar(250) DEFAULT NULL,
  `campassword` varchar(250) DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  `dvrid` int(11) DEFAULT NULL,
  `dvrlogin` varchar(250) DEFAULT NULL,
  `dvrpassword` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `visor_dvrs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(250) NOT NULL,
  `port` int(11) DEFAULT NULL,
  `login` varchar(250) DEFAULT NULL,
  `password` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 1.0.0 update
ALTER TABLE `visor_users` ADD `primarylogin` VARCHAR(255) NULL AFTER `chargecams`, ADD INDEX (`primarylogin`);

CREATE TABLE IF NOT EXISTS `fdbarchive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `devid` int(11) DEFAULT NULL,
  `devip` varchar(64) DEFAULT NULL,
  `data` longtext,
  `pon` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `devid` (`devid`,`devip`),
  KEY `pon` (`pon`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `askcalls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(250) DEFAULT NULL,
  `login` varchar(250) DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 1.0.1 update
CREATE TABLE IF NOT EXISTS `dreamkas_operations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operation_id` varchar(255) NOT NULL,
  `date_create` datetime NOT NULL,
  `date_finish` datetime NOT NULL,
  `date_resend` datetime NOT NULL,
  `status` varchar(255) NOT NULL,
  `error_code` varchar(255) NOT NULL,
  `error_message` varchar(255) NOT NULL,
  `receipt_id` varchar(255) NOT NULL,
  `operation_body` TEXT NOT NULL,
  `repeat_count` tinyint(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`operation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `dreamkas_services_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service` varchar(42) NOT NULL,
  `goods_id` varchar(255) NOT NULL,
  `goods_name` varchar(255) NOT NULL,
  `goods_type` varchar(255) NOT NULL,
  `goods_price` double NOT NULL,
  `goods_tax` varchar(255) NOT NULL,
  `goods_vendorcode` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`service`, `goods_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `dreamkas_banksta2_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bs2_rec_id` int(11) NOT NULL,
  `operation_id` varchar(255) NOT NULL,
  `receipt_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (bs2_rec_id),
  UNIQUE KEY (`operation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `callmeback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `number` varchar(250) DEFAULT NULL,
  `state` varchar(40) DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `salary_jobprices` CHANGE `time` `time` FLOAT NULL DEFAULT NULL;

ALTER TABLE `dreamkas_operations` ADD `repeated_fiscop_id` varchar(255) NOT NULL AFTER `operation_body`;

CREATE TABLE IF NOT EXISTS `qinq` (
    `id` INT NOT NULL AUTO_INCREMENT, 
    `login` VARCHAR(45) NULL, 
    `svlan` INT(4) NULL,
    `cvlan` INT(4) NULL,
    PRIMARY KEY (`id`), 
    UNIQUE KEY (`login`)
) ENGINE = MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=UTF8;

ALTER TABLE `qinq` ADD `svlan_id` int(10) NOT NULL AFTER `svlan`;

ALTER TABLE `qinq` DROP `svlan`;

RENAME TABLE `qinq` TO `qinq_bindings`;

CREATE TABLE IF NOT EXISTS `realms` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `realm` varchar(255) NOT NULL,
    `description` varchar(255) NULL,
    PRIMARY KEY (`id`),
    KEY (`realm`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `realms` (`id`,`realm`,`description`) VALUES (NULL, 'default', 'default realm');

CREATE TABLE IF NOT EXISTS `qinq_svlan` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `realm_id` int(11) NOT NULL,
    `svlan` int(4) NOT NULL,
    `description` varchar(255) NULL,
    PRIMARY KEY (`id`),
    KEY (`realm_id`),
    KEY (`svlan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `qinq_svlan` (`id`, `realm_id`, `svlan`, `description`) VALUES (NULL, 1, 0, 'Use it for untagged VLAN');

ALTER TABLE `switches_qinq` ADD `svlan_id` int(11) NOT NULL AFTER `switchid`;

ALTER TABLE `switches_qinq` ADD KEY (`svlan_id`);

ALTER TABLE `switches_qinq` DROP `svlan`;

CREATE TABLE IF NOT EXISTS `zte_qinq` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `swid` int(11) NOT NULL,
    `slot_number` int(11) NOT NULL,
    `port` int(4) NOT NULL,
    `svlan_id` int(11) NOT NULL,
    `cvlan` int(4) NOT NULL,
    PRIMARY KEY (`id`),
    KEY (`svlan_id`),
    KEY (`cvlan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;



ALTER TABLE `banksta2_presets` CHANGE `col_skiprow` col_skiprow varchar(100) DEFAULT '';
ALTER TABLE `banksta2_presets` ADD `replace_strs` tinyint(3) DEFAULT 0;
ALTER TABLE `banksta2_presets` ADD `col_replace_strs` varchar(100) DEFAULT '';
ALTER TABLE `banksta2_presets` ADD `strs_to_replace` varchar(200) DEFAULT '';
ALTER TABLE `banksta2_presets` ADD `strs_to_replace_with` varchar(200) DEFAULT '';
ALTER TABLE `banksta2_presets` ADD `replacements_cnt` tinyint(3) DEFAULT 1;
ALTER TABLE `banksta2_presets` ADD `remove_strs` tinyint(3) DEFAULT 0;
ALTER TABLE `banksta2_presets` ADD `col_remove_strs` varchar(100) DEFAULT '';
ALTER TABLE `banksta2_presets` ADD `strs_to_remove` varchar(200) DEFAULT '';

ALTER TABLE `visor_dvrs` ADD `apikey` VARCHAR(255) NULL DEFAULT NULL AFTER `password`; 
ALTER TABLE `visor_dvrs` ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `apikey`;
ALTER TABLE `visor_dvrs` ADD `type` VARCHAR(40) NULL DEFAULT NULL AFTER `name`;

CREATE TABLE IF NOT EXISTS `traptypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `cardbank` ADD KEY `serial` (`serial`);
 
ALTER TABLE `cardbank` ADD KEY `part` (`part`);

-- ALTER TABLE `cardbank` ADD KEY `serial_part` (`serial`,`part`);

-- 1.0.4 update

CREATE TABLE IF NOT EXISTS `envyscripts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modelid` int(11) NOT NULL,
  `data` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `envydevices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `switchid` int(11) NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `enablepassword` varchar(255) DEFAULT NULL,
  `custom1` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `envydata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `switchid` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `config` mediumtext,
  PRIMARY KEY (`id`),
  KEY `switchid` (`switchid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `envydevices` ADD `active` TINYINT NULL DEFAULT '1' AFTER `switchid`;

-- 1.0.5 update

CREATE TABLE IF NOT EXISTS `visor_chans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visorid` int(11) NOT NULL,
  `dvrid` int(11) NOT NULL,
  `chan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `visor_secrets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visorid` int(11) NOT NULL,
  `login` varchar(64) NOT NULL,
  `password` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `frozen_charge_days` ADD `last_freeze_charge_dt` datetime NOT NULL AFTER `freeze_days_used`;
ALTER TABLE `frozen_charge_days` ADD `last_workdays_upd_dt` datetime NOT NULL;

ALTER TABLE `visor_dvrs` ADD `camlimit` int(11) NULL DEFAULT 0 AFTER `type`;

ALTER TABLE `vservices` MODIFY `price` double NOT NULL DEFAULT 0;
ALTER TABLE `vservices` ADD `charge_period_days` tinyint(3) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `invoice_num` varchar(40) NOT NULL DEFAULT '',
  `invoice_date` datetime NOT NULL,
  `invoice_sum` double NOT NULL DEFAULT 0,
  `invoice_body` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`invoice_num`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `address_extended` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `postal_code` varchar(10) NOT NULL DEFAULT '',
  `town_district` varchar(150) NOT NULL DEFAULT '',
  `address_exten` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `payments` MODIFY `note` varchar(200) NULL DEFAULT NULL;
ALTER TABLE `paymentscorr` MODIFY `note` varchar(200) NULL DEFAULT NULL;

-- 1.0.8 update
ALTER TABLE `banksta2_presets` ADD `payment_type_id` int(11) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `pt_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(64) NOT NULL,
  `day` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `pt_tariffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tariff` varchar(40) NOT NULL,
  `fee` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `pt_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `login` varchar(64) NOT NULL,
  `tariff` varchar(40) NOT NULL,
  `day` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `ponifdesc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oltid` int(11) NOT NULL,
  `iface` varchar(64) DEFAULT NULL,
  `desc` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY (`oltid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `ponboxes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `geo` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `ponboxeslinks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `boxid` int(11) NOT NULL,
  `login` varchar(64) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `onuid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 1.0.9 update
CREATE TABLE IF NOT EXISTS `switchuplinks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `switchid` int(11) NOT NULL,
  `media` varchar(10) DEFAULT NULL,
  `port` int (11) DEFAULT NULL,
  `speed` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `switchuplinks` ADD INDEX(`switchid`); 

-- 1.1.0 update
CREATE TABLE IF NOT EXISTS `filestorage` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `scope` VARCHAR(255) NOT NULL,
  `item` VARCHAR(255) NOT NULL,
  `date` datetime NOT NULL,
  `admin` VARCHAR(40) NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `scope` (`scope`),
  KEY `item` (`item`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `swcash` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `switchid` int(11) NOT NULL,
  `placecontract` varchar(200) DEFAULT NULL,
  `placeprice` double NOT NULL DEFAULT '0',
  `powercontract` varchar(200) DEFAULT NULL,
  `powerprice` double NOT NULL DEFAULT '0',
  `transportcontract` varchar(200) DEFAULT NULL,
  `transportprice` double NOT NULL DEFAULT '0',
  `switchprice` double NOT NULL DEFAULT '0',
  `switchdate` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `switchid` (`switchid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 1.1.1 update
CREATE TABLE IF NOT EXISTS `taskstates` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `taskid` INT(11) NOT NULL,
  `state` VARCHAR(42) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `taskid` (`taskid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `op_denied` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


-- 1.1.2 update
ALTER TABLE `passportdata` ADD `pinn` VARCHAR(15) NULL DEFAULT NULL;

-- 1.1.3 update

CREATE TABLE IF NOT EXISTS `garage_cars` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `vendor` VARCHAR(40) NOT NULL,
  `model` VARCHAR(40) NOT NULL,
  `number` VARCHAR(20) DEFAULT NULL,
  `vin` VARCHAR(40) DEFAULT NULL,
  `year` INT(11) DEFAULT NULL,
  `power` INT(11) DEFAULT NULL,
  `engine` INT(11) DEFAULT NULL,
  `fuelconsumption` DOUBLE DEFAULT NULL,
  `fueltype` VARCHAR(16) DEFAULT NULL,
  `gastank` INT(11) DEFAULT NULL,
  `weight` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `garage_drivers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employeeid` INT(11) NOT NULL,
  `carid` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `garage_mileage` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` DATETIME NOT NULL,
  `carid` INT(11) NOT NULL,
  `mileage` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `garage_mapon` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `carid` INT(11) NOT NULL,
  `unitid` INT(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `banksta2_presets` ADD `col_srvidents` varchar(20) DEFAULT '' AFTER `col_contract`;
ALTER TABLE `banksta2_presets` ADD `srvidents_preffered` tinyint(3) DEFAULT 0 AFTER `guess_contract`;

CREATE TABLE IF NOT EXISTS `user_dataexport_allowed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(100) NOT NULL,
  `export_allowed` tinyint(3) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `contrahens_extinfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agentid` int(11) NOT NULL,
  `service_type` varchar(50) NOT NULL DEFAULT '',
  `internal_paysys_name` varchar(50)  NOT NULL DEFAULT '',
  `internal_paysys_id` varchar(50)  NOT NULL DEFAULT '',
  `internal_paysys_srv_id` varchar(50)  NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 1.1.4 update
ALTER TABLE `envydevices` ADD `cutstart` INT NULL DEFAULT NULL , ADD `cutend` INT NULL DEFAULT NULL ; 

ALTER TABLE `visor_dvrs` ADD `customurl` VARCHAR(255) NULL DEFAULT NULL AFTER `camlimit`;

ALTER TABLE `stickyrevelations` ADD `dayweek` INT NULL DEFAULT NULL AFTER `dayto`;

-- 1.1.5 update
ALTER TABLE `fdbarchive` ADD `datavlan` longtext NULL DEFAULT NULL AFTER `data`;
ALTER TABLE `fdbarchive` ADD `dataportdescr` longtext NULL DEFAULT NULL AFTER `datavlan`;

-- 1.1.6 update

CREATE TABLE IF NOT EXISTS `ptv_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `subscriberid` int(11) NOT NULL,
  `login` varchar(64) NOT NULL,
  `maintariff` int(11) DEFAULT NULL,
  `addtariffs` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ptv_tariffs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `serviceid` INT(11) NOT NULL,
  `main` tinyint(1) NOT NULL,
  `name` VARCHAR(64) NOT NULL,
  `chans` VARCHAR(42) DEFAULT NULL,
  `fee` DOUBLE NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `ponboxes_splitters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `boxid` int(11) NOT NULL,
  `splitter` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `ponboxes` MODIFY `name` varchar(200) NULL DEFAULT NULL;
ALTER TABLE `ponboxes` ADD `exten_info` varchar(250) NULL DEFAULT NULL AFTER `name`;

ALTER TABLE sms_history ADD INDEX (srvmsgself_id) USING BTREE;
ALTER TABLE sms_history ADD INDEX (date_statuschk) USING BTREE;


-- 1.1.7 update

CREATE TABLE IF NOT EXISTS `ins_homereq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `login` varchar(64) DEFAULT NULL,
  `address` varchar(200) NOT NULL,
  `realname` varchar(200) NOT NULL,
  `mobile` varchar(64) NOT NULL,
  `email` varchar(64) NOT NULL,
  `state` tinyint(1) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `youtv_subscribers` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `subscriberid` int(11) NOT NULL,
  `login` varchar(64) NOT NULL,
  `maintariff` int(11) DEFAULT NULL,
  `addtariffs` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `youtv_subscribers` ADD PRIMARY KEY (`id`);

ALTER TABLE `youtv_subscribers` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


CREATE TABLE `youtv_tariffs` (
  `id` int(11) NOT NULL,
  `serviceid` int(11) NOT NULL,
  `main` tinyint(1) NOT NULL,
  `name` varchar(64) NOT NULL,
  `chans` varchar(42) DEFAULT NULL,
  `fee` double NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `youtv_tariffs` ADD PRIMARY KEY (`id`);

ALTER TABLE `youtv_tariffs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `mg_credentials` (
 `id` INT(11) NOT NULL AUTO_INCREMENT,
 `isdn` VARCHAR(255) NOT NULL,
 `login` VARCHAR(255) NOT NULL,
 `email`  VARCHAR(255) NOT NULL,
 `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ipauth_denied` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 1.1.9 update

ALTER TABLE `callmeback` ADD `statedate` DATETIME NULL DEFAULT NULL AFTER `state`;
ALTER TABLE `callmeback` ADD `admin` VARCHAR(200) NULL DEFAULT NULL AFTER `statedate`;


-- 1.2.0 update

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
  `date_payment` date DEFAULT NULL,
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

ALTER TABLE `stigma` ADD INDEX(`scope`);

ALTER TABLE `stigma` ADD INDEX(`itemid`);

CREATE TABLE IF NOT EXISTS `wh_returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `outid` int(11) NOT NULL,
  `storageid` int(11) NOT NULL,
  `itemtypeid` int(11) NOT NULL,
  `count` DOUBLE NOT NULL,
  `price` DOUBLE NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(64) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `outid` (`outid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


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

ALTER TABLE `ot_users` ADD `addtariffid` INT NULL DEFAULT NULL AFTER `tariffid`; 

-- 1.2.5 update

ALTER TABLE `buildpassport` ADD `anthill` TINYINT NULL;

-- 1.2.7 update

CREATE TABLE IF NOT EXISTS `olt_qinq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `swid` int(11) NOT NULL,
  `port` int(4) NOT NULL,
  `svlan_id` int(11) NOT NULL,
  `cvlan` int(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `svlan_id` (`svlan_id`),
  KEY `cvlan` (`cvlan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `op_sms_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `login` varchar(255) NOT NULL,
  `balance` double NOT NULL DEFAULT 0,
  `summ` double NOT NULL DEFAULT 0,
  `processed` tinyint(1) UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_id` (`payment_id`),
  KEY `login` (`login`),
  KEY `date` (`date`),
  KEY `summ` (`summ`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `wh_salesreports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
   PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `wh_salesitems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reportid` int(11) NOT NULL,
  `itemtypeid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `reportid` (`reportid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 1.3.4 update

CREATE TABLE IF NOT EXISTS `discounts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(64) NOT NULL,
  `percent` DOUBLE DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `fees` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `hash` VARCHAR(42) NOT NULL,
  `login` VARCHAR(64) NOT NULL,
  `date` datetime NOT NULL,
  `admin` VARCHAR(64) DEFAULT NULL,
  `from` DOUBLE DEFAULT NULL,
  `to` DOUBLE DEFAULT NULL,
  `summ` DOUBLE DEFAULT NULL,
  `note` VARCHAR(200) DEFAULT NULL,
  `cashtype` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`),  
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 1.3.5 update

ALTER TABLE `condet` ADD `term` INT NULL AFTER `price`;

ALTER TABLE `cfitems` ADD INDEX(`login`);

ALTER TABLE `contractdates` ADD `from` DATE NULL AFTER `date`, ADD `till` DATE NULL AFTER `from`; 

ALTER TABLE `contrahens` ADD `agnameabbr` VARCHAR(255) NULL AFTER `contrname`, ADD `agsignatory` VARCHAR(255) NULL AFTER `agnameabbr`, ADD `agsignatory2` VARCHAR(255) NULL AFTER `agsignatory`, ADD `agbasis` VARCHAR(255) NULL AFTER `agsignatory2`, ADD `agmail` VARCHAR(100) NULL AFTER `agbasis`, ADD `siteurl` VARCHAR(255) NULL AFTER `agmail`; 

ALTER TABLE `corp_data` ADD `corpnameabbr` VARCHAR(255) NULL AFTER `notes`, ADD `corpsignatory` VARCHAR(255) NULL AFTER `corpnameabbr`, ADD `corpsignatory2` VARCHAR(255) NULL AFTER `corpsignatory`, ADD `corpbasis` VARCHAR(255) NULL AFTER `corpsignatory2`, ADD `corpemail` VARCHAR(100) NULL AFTER `corpbasis`;

-- 1.3.7 update

ALTER TABLE `exhorse` ADD `a_outtotalcalls` INT NULL DEFAULT NULL;
ALTER TABLE `exhorse` ADD `a_outtotalanswered` INT NULL DEFAULT NULL;
ALTER TABLE `exhorse` ADD `a_outtotalcallsduration` INT NULL DEFAULT NULL;
ALTER TABLE `exhorse` ADD `a_outaveragecallduration` INT NULL DEFAULT NULL;

-- 1.3.8 update

ALTER TABLE `visor_dvrs` ADD `apiurl` VARCHAR(255) NULL DEFAULT NULL AFTER `password`;

-- 1.4.0 update

ALTER TABLE `banksta2_presets` ADD `sum_in_coins` tinyint(3) DEFAULT 0 AFTER `col_paysum`;
ALTER TABLE `banksta2_presets` ADD `noesc_inet_srv_keywords` tinyint(3) DEFAULT 0 AFTER `inet_srv_keywords`;
ALTER TABLE `banksta2_presets` ADD `noesc_ukv_srv_keywords` tinyint(3) DEFAULT 0 AFTER `ukv_srv_keywords`;
ALTER TABLE `banksta2_presets` ADD `noesc_skip_row_keywords` tinyint(3) DEFAULT 0 AFTER `skip_row_keywords`;
ALTER TABLE `banksta2_presets` ADD `noesc_replace_keywords` tinyint(3) DEFAULT 0 AFTER `replacements_cnt`;
ALTER TABLE `banksta2_presets` ADD `noesc_remove_keywords` tinyint(3) DEFAULT 0 AFTER `strs_to_remove`;

-- 1.4.1 update

CREATE TABLE IF NOT EXISTS `crm_leads` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `address` varchar(255) NOT NULL,
  `realname` varchar(255) NOT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `mobile` varchar(32) NOT NULL,
  `extmobile` varchar(32) DEFAULT NULL,
  `email` varchar(64) DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `tariff` varchar(64) DEFAULT NULL,
  `login` varchar(64) DEFAULT NULL,
  `employeeid` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `crm_activities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `leadid` INT(11) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(64) DEFAULT NULL,
  `employeeid` int(11) DEFAULT NULL,
  `state` tinyint(1) DEFAULT 0,
  `notes` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `leadid` (`leadid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `crm_stateslog` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `admin` varchar(64) DEFAULT NULL,
  `scope` varchar(64) DEFAULT NULL,
  `itemid` varchar(128) NOT NULL,
  `action` varchar(32) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `scope` (`scope`),
   KEY `itemid` (`itemid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `stealthtariffs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tariff` varchar(64) DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `mlg_culpas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(64) NOT NULL,
  `culpa` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `login` (`login`),
   KEY `culpa` (`culpa`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 1.4.2 update

ALTER TABLE `contrahens_extinfo` ADD `paysys_token` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `contrahens_extinfo` ADD `paysys_secret_key` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `contrahens_extinfo` ADD `paysys_password` VARCHAR(255) NOT NULL DEFAULT '';

-- 1.4.3 update

ALTER TABLE `envydevices` ADD `port` INT NULL DEFAULT NULL AFTER `cutend`;

CREATE TABLE IF NOT EXISTS `ophtraff` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(50) NOT NULL,
  `month` tinyint(4) NOT NULL,
  `year` SMALLINT(6) NOT NULL,
  `U0` BIGINT(20) DEFAULT NULL,
  `D0` BIGINT(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

-- 1.4.4 update
ALTER TABLE `vservices` ADD `exclude_tags` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `vservices` ADD `archived` TINYINT(1) NOT NULL DEFAULT 0;

-- 1.4.6 update
ALTER TABLE zte_cards MODIFY COLUMN `card_name` varchar(7) NOT NULL;

-- 1.4.7 update
ALTER TABLE `callmeback` ADD `userlogin` VARCHAR(64) NULL DEFAULT NULL AFTER `admin`;
ALTER TABLE `contrahens_extinfo` ADD `paysys_callback_url` VARCHAR(255) NOT NULL DEFAULT '';

-- 1.4.9 update
ALTER TABLE `wh_out` ADD `netw` tinyint(4) NULL DEFAULT 0 AFTER `notes`;

CREATE TABLE IF NOT EXISTS`gr_strat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `useassigns` tinyint(4) NOT NULL DEFAULT '0',
  `primaryagentid` int(11) DEFAULT NULL,
  `maxamount` int(11) DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `gr_spec` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stratid` int(11) NOT NULL,
  `agentid` int(11) NOT NULL,
  `type` varchar(32) NOT NULL,
  `value` int(11) DEFAULT NULL,
  `customdata` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `gr_strat` ADD `tariff` VARCHAR(64) NULL AFTER `maxamount`; 

CREATE TABLE IF NOT EXISTS `ddt_chargeopts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tariff` VARCHAR(40) NOT NULL,
  `untilday` INT(11) DEFAULT NULL,
  `chargefee` tinyint(4) NOT NULL,
  `absolute` INT(11) DEFAULT NULL,
  `creditdays` INT(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ddt_charges` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(32) NOT NULL,
  `chargedate` DATE NOT NULL,
  `tariff` VARCHAR(40) NOT NULL,
  `summ` DOUBLE NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 1.5.0 update
ALTER TABLE `contrahens_extinfo` ADD `payment_fee_info` VARCHAR(100) NOT NULL DEFAULT '' AFTER `paysys_password`;

-- 1.5.2 update

CREATE TABLE IF NOT EXISTS `pbxcalls` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(250) DEFAULT NULL,
  `login` VARCHAR(64) DEFAULT NULL,
  `size` INT(11) DEFAULT NULL,
  `direction` VARCHAR(4) DEFAULT NULL,
  `storage` VARCHAR(4) DEFAULT NULL,
  `date` DATETIME DEFAULT NULL,
  `number` VARCHAR(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login` (`login`),
  KEY `idx_date` (`date`),
  KEY `idx_number` (`number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `switchauth` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `swid` INT(11) NOT NULL,
  `login` VARCHAR(64) DEFAULT NULL,
  `password` VARCHAR(64) DEFAULT NULL,
  `enable` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `switchid` (`swid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 1.5.3 update

CREATE TABLE IF NOT EXISTS `bgppeers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(64) DEFAULT NULL,
  `name` VARCHAR(64) DEFAULT NULL,
  `short` VARCHAR(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 1.5.4 update

CREATE TABLE IF NOT EXISTS `taxsup` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(32) NOT NULL,
  `fee` DOUBLE DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- 1.5.6 update

ALTER TABLE `ddt_options` ADD `chargeabsolute` INT(11) NULL DEFAULT 0 AFTER `tariffmove`;
ALTER TABLE `ddt_options` ADD `creditcustom` INT(11) NULL DEFAULT 0 AFTER `chargeabsolute`;

CREATE TABLE IF NOT EXISTS `katottg` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ob` VARCHAR(64) NOT NULL,
  `ra` VARCHAR(64) NOT NULL,
  `tg` VARCHAR(64) NOT NULL,
  `ci` VARCHAR(64) NOT NULL,
  `type` VARCHAR(2) DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `katottg_cities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `katid` INT(11) NOT NULL,
  `cityid` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cityid` (`cityid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `katottg_streets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `katid` INT(11) NOT NULL,
  `streetid` INT(11) NOT NULL,
  `cd` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `streetid` (`streetid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `employee` ADD `birthdate` DATE NULL AFTER `amountLimit`; 

-- 1.5.7 update

ALTER TABLE `ukv_users` ADD `tariffnmdate` VARCHAR(20) NULL DEFAULT NULL AFTER `tariffnmid`;

-- 1.5.8 update

CREATE TABLE IF NOT EXISTS `ct_auth` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `chatid` VARCHAR(40) NOT NULL,
  `login` VARCHAR(64) NOT NULL,
  `password` VARCHAR(64) NOT NULL,
  `date` DATETIME DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_chatid` (`chatid`),
  KEY `idx_login` (`login`),
  KEY `idx_active` (`active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `pononu` ADD `geo` VARCHAR(64) NULL DEFAULT NULL AFTER `login`;

CREATE TABLE IF NOT EXISTS `ub_im_pinned` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(64) NOT NULL,
  `pinned` VARCHAR(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `weblogs` ADD FULLTEXT INDEX `ft_event` (`event`);