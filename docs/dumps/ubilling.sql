CREATE TABLE `adcomments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `scope` varchar(255) NOT NULL,
  `item` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(40) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `scope` (`scope`),
  KEY `item` (`item`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `address` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(45) NOT NULL,
  `aptid` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`),
  KEY `aptid` (`aptid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `address_extended` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `postal_code` varchar(10) NOT NULL DEFAULT '',
  `town_district` varchar(150) NOT NULL DEFAULT '',
  `address_exten` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `admacquainted` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `admin` varchar(40) NOT NULL,
  `annid` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`admin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `admannouncements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `ahenassign` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ahenid` int NOT NULL,
  `streetname` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ahenassignstrict` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agentid` int NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `apt` (
  `id` int NOT NULL AUTO_INCREMENT,
  `buildid` int NOT NULL,
  `entrance` varchar(15) DEFAULT NULL,
  `floor` varchar(15) DEFAULT NULL,
  `apt` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `apt` (`apt`),
  KEY `buildid` (`buildid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `askcalls` (
  `id` int NOT NULL AUTO_INCREMENT,
  `filename` varchar(250) DEFAULT NULL,
  `login` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `banksta2` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `processed` tinyint NOT NULL,
  `canceled` tinyint NOT NULL,
  `service_type` varchar(100) NOT NULL DEFAULT '',
  `payid` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `banksta2_presets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `presetname` varchar(80) NOT NULL,
  `col_realname` varchar(20) DEFAULT '',
  `col_address` varchar(20) DEFAULT '',
  `col_paysum` varchar(20) DEFAULT '',
  `sum_in_coins` tinyint DEFAULT '0',
  `col_paypurpose` varchar(20) DEFAULT '',
  `col_paydate` varchar(20) DEFAULT '',
  `col_paytime` varchar(20) DEFAULT '',
  `col_contract` varchar(20) DEFAULT '',
  `col_srvidents` varchar(20) DEFAULT '',
  `guess_contract` tinyint DEFAULT '0',
  `srvidents_preffered` tinyint DEFAULT '0',
  `contract_delim_start` varchar(40) DEFAULT '',
  `contract_delim_end` varchar(40) DEFAULT '',
  `contract_min_len` tinyint DEFAULT '0',
  `contract_max_len` tinyint DEFAULT '0',
  `service_type` varchar(100) NOT NULL DEFAULT '',
  `inet_srv_start_delim` varchar(40) DEFAULT '',
  `inet_srv_end_delim` varchar(40) DEFAULT '',
  `inet_srv_keywords` varchar(200) DEFAULT '',
  `noesc_inet_srv_keywords` tinyint DEFAULT '0',
  `ukv_srv_start_delim` varchar(40) DEFAULT '',
  `ukv_srv_end_delim` varchar(40) DEFAULT '',
  `ukv_srv_keywords` varchar(200) DEFAULT '',
  `noesc_ukv_srv_keywords` tinyint DEFAULT '0',
  `skip_row` tinyint DEFAULT '0',
  `col_skiprow` varchar(100) DEFAULT '',
  `skip_row_keywords` varchar(200) DEFAULT '',
  `noesc_skip_row_keywords` tinyint DEFAULT '0',
  `replace_strs` tinyint DEFAULT '0',
  `col_replace_strs` varchar(100) DEFAULT '',
  `strs_to_replace` varchar(200) DEFAULT '',
  `strs_to_replace_with` varchar(200) DEFAULT '',
  `replacements_cnt` tinyint DEFAULT '1',
  `noesc_replace_keywords` tinyint DEFAULT '0',
  `remove_strs` tinyint DEFAULT '0',
  `col_remove_strs` varchar(100) DEFAULT '',
  `strs_to_remove` varchar(200) DEFAULT '',
  `noesc_remove_keywords` tinyint DEFAULT '0',
  `payment_type_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `presetname` (`presetname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `bankstamd` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `processed` tinyint NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `bankstaparsed` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hash` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `row` int NOT NULL,
  `realname` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `summ` float NOT NULL,
  `state` int NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `bankstaraw` (
  `id` int NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `rawdata` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `bgppeers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip` varchar(64) DEFAULT NULL,
  `name` varchar(64) DEFAULT NULL,
  `short` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `branches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `branchesadmins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branchid` int NOT NULL,
  `admin` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `branchescities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branchid` int NOT NULL,
  `cityid` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `branchesservices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branchid` int NOT NULL,
  `serviceid` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `branchestariffs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branchid` int NOT NULL,
  `tariff` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `branchesusers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branchid` int NOT NULL,
  `login` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `build` (
  `id` int NOT NULL AUTO_INCREMENT,
  `streetid` int NOT NULL,
  `buildnum` varchar(10) NOT NULL,
  `geo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `buildnum` (`buildnum`),
  KEY `streetid` (`streetid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `buildpassport` (
  `id` int NOT NULL AUTO_INCREMENT,
  `buildid` int NOT NULL,
  `owner` varchar(255) DEFAULT NULL,
  `ownername` varchar(255) DEFAULT NULL,
  `ownerphone` varchar(255) DEFAULT NULL,
  `ownercontact` varchar(255) DEFAULT NULL,
  `keys` tinyint DEFAULT NULL,
  `accessnotices` varchar(255) DEFAULT NULL,
  `floors` int DEFAULT NULL,
  `apts` int DEFAULT NULL,
  `entrances` int DEFAULT NULL,
  `notes` text,
  `contract` tinyint DEFAULT NULL,
  `mediator` tinyint DEFAULT NULL,
  `anthill` tinyint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `buildid` (`buildid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `callmeback` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `number` varchar(250) DEFAULT NULL,
  `state` varchar(40) DEFAULT NULL,
  `statedate` datetime DEFAULT NULL,
  `admin` varchar(200) DEFAULT NULL,
  `userlogin` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `callshist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `number` varchar(120) NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `capab` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `stateid` int NOT NULL DEFAULT '0',
  `notes` text,
  `price` varchar(255) DEFAULT NULL,
  `employeeid` int DEFAULT NULL,
  `donedate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `state` (`stateid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `capabhist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `capabid` int NOT NULL,
  `admin` varchar(40) NOT NULL,
  `date` datetime NOT NULL,
  `type` varchar(40) NOT NULL,
  `event` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `capabstates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `state` varchar(255) NOT NULL,
  `color` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `capdata` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `date` datetime DEFAULT NULL,
  `days` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `cardbank` (
  `id` int NOT NULL AUTO_INCREMENT,
  `serial` varchar(255) NOT NULL,
  `cash` varchar(45) NOT NULL,
  `admin` varchar(45) NOT NULL,
  `date` datetime NOT NULL,
  `active` tinyint(1) NOT NULL,
  `used` tinyint(1) NOT NULL,
  `usedate` datetime DEFAULT NULL,
  `usedlogin` varchar(45) NOT NULL,
  `usedip` varchar(45) DEFAULT NULL,
  `part` varchar(255) DEFAULT NULL,
  `receipt_date` datetime DEFAULT NULL,
  `selling_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `serial` (`serial`),
  KEY `part` (`part`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `cardbrute` (
  `id` int NOT NULL AUTO_INCREMENT,
  `serial` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `login` varchar(45) NOT NULL,
  `ip` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `cashtype` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cashtype` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cashtype` (`cashtype`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `cemetery` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `cfitems` (
  `id` int NOT NULL AUTO_INCREMENT,
  `typeid` int NOT NULL,
  `login` varchar(255) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `cftypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(15) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `city` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cityname` varchar(255) NOT NULL,
  `cityalias` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cityname` (`cityname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `condet` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) DEFAULT NULL,
  `seal` varchar(40) DEFAULT NULL,
  `length` varchar(40) DEFAULT NULL,
  `price` varchar(40) DEFAULT NULL,
  `term` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `contacts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `phone` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `contractdates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contract` varchar(255) NOT NULL,
  `date` date DEFAULT NULL,
  `from` date DEFAULT NULL,
  `till` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `contracts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(45) NOT NULL,
  `contract` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `contrahens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bankacc` varchar(255) DEFAULT NULL,
  `bankname` varchar(255) DEFAULT NULL,
  `bankcode` varchar(255) DEFAULT NULL,
  `edrpo` varchar(255) DEFAULT NULL,
  `ipn` varchar(255) DEFAULT NULL,
  `licensenum` varchar(255) DEFAULT NULL,
  `juraddr` varchar(255) DEFAULT NULL,
  `phisaddr` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `contrname` varchar(255) NOT NULL,
  `agnameabbr` varchar(255) DEFAULT NULL,
  `agsignatory` varchar(255) DEFAULT NULL,
  `agsignatory2` varchar(255) DEFAULT NULL,
  `agbasis` varchar(255) DEFAULT NULL,
  `agmail` varchar(100) DEFAULT NULL,
  `siteurl` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `contrahens_extinfo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agentid` int NOT NULL,
  `service_type` varchar(50) NOT NULL DEFAULT '',
  `internal_paysys_name` varchar(50) NOT NULL DEFAULT '',
  `internal_paysys_id` varchar(50) NOT NULL DEFAULT '',
  `internal_paysys_srv_id` varchar(50) NOT NULL DEFAULT '',
  `paysys_token` varchar(255) NOT NULL DEFAULT '',
  `paysys_secret_key` varchar(255) NOT NULL DEFAULT '',
  `paysys_password` varchar(255) NOT NULL DEFAULT '',
  `payment_fee_info` varchar(100) NOT NULL DEFAULT '',
  `paysys_callback_url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `corp_data` (
  `id` int NOT NULL AUTO_INCREMENT,
  `corpname` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `doctype` int DEFAULT NULL,
  `docnum` varchar(255) DEFAULT NULL,
  `docdate` date DEFAULT NULL,
  `bankacc` varchar(255) DEFAULT NULL,
  `bankname` varchar(255) DEFAULT NULL,
  `bankmfo` varchar(255) DEFAULT NULL,
  `edrpou` varchar(255) DEFAULT NULL,
  `ndstaxnum` varchar(255) DEFAULT NULL,
  `inncode` varchar(255) DEFAULT NULL,
  `taxtype` int DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `corpnameabbr` varchar(255) DEFAULT NULL,
  `corpsignatory` varchar(255) DEFAULT NULL,
  `corpsignatory2` varchar(255) DEFAULT NULL,
  `corpbasis` varchar(255) DEFAULT NULL,
  `corpemail` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `corp_persons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `corpid` int NOT NULL,
  `realname` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `im` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `appointment` varchar(255) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `corp_taxtypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `corp_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `corpid` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `cpe` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cpemodelid` int NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `desc` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `snmp` varchar(45) DEFAULT NULL,
  `netid` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `cpetypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cpemodel` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `crm_activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `leadid` int NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(64) DEFAULT NULL,
  `employeeid` int DEFAULT NULL,
  `state` tinyint(1) DEFAULT '0',
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leadid` (`leadid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `crm_leads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `address` varchar(255) NOT NULL,
  `realname` varchar(255) NOT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `mobile` varchar(32) NOT NULL,
  `extmobile` varchar(32) DEFAULT NULL,
  `email` varchar(64) DEFAULT NULL,
  `branch` int DEFAULT NULL,
  `tariff` varchar(64) DEFAULT NULL,
  `login` varchar(64) DEFAULT NULL,
  `employeeid` int DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `crm_stateslog` (
  `id` int NOT NULL AUTO_INCREMENT,
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

CREATE TABLE `ct_auth` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chatid` varchar(40) NOT NULL,
  `login` varchar(64) NOT NULL,
  `password` varchar(64) NOT NULL,
  `date` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_chatid` (`chatid`),
  KEY `idx_login` (`login`),
  KEY `idx_active` (`active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `cudiscounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `discount` double DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `days` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `custmaps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `custmapsitems` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mapid` int DEFAULT NULL,
  `type` varchar(40) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mapid` (`mapid`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ddt_chargeopts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tariff` varchar(40) NOT NULL,
  `untilday` int DEFAULT NULL,
  `chargefee` tinyint NOT NULL,
  `absolute` int DEFAULT NULL,
  `creditdays` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ddt_charges` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(32) NOT NULL,
  `chargedate` date NOT NULL,
  `tariff` varchar(40) NOT NULL,
  `summ` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ddt_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tariffname` varchar(40) NOT NULL,
  `period` varchar(10) NOT NULL,
  `startnow` tinyint NOT NULL,
  `duration` int NOT NULL,
  `chargefee` tinyint NOT NULL,
  `chargeuntilday` int DEFAULT NULL,
  `setcredit` tinyint DEFAULT NULL,
  `tariffmove` varchar(40) NOT NULL,
  `chargeabsolute` int DEFAULT '0',
  `creditcustom` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ddt_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(32) NOT NULL,
  `active` tinyint NOT NULL,
  `startdate` datetime NOT NULL,
  `curtariff` varchar(40) NOT NULL,
  `enddate` date NOT NULL,
  `nexttariff` varchar(40) NOT NULL,
  `dwiid` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `dealwithit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `login` varchar(45) NOT NULL,
  `action` varchar(45) NOT NULL,
  `param` varchar(45) DEFAULT NULL,
  `note` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `dealwithithist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `originalid` int NOT NULL,
  `mtime` datetime NOT NULL,
  `date` date NOT NULL,
  `datetimedone` datetime DEFAULT NULL,
  `login` varchar(45) NOT NULL,
  `action` varchar(45) NOT NULL,
  `param` varchar(45) DEFAULT NULL,
  `note` varchar(45) DEFAULT NULL,
  `admin` varchar(50) DEFAULT NULL,
  `done` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `deathtime` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `dhcp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `netid` int NOT NULL,
  `dhcpconfig` text,
  `confname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `directions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rulenumber` int NOT NULL,
  `rulename` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `rulenumber` (`rulenumber`),
  KEY `rulename` (`rulename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `discounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(64) NOT NULL,
  `percent` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `districtdata` (
  `id` int NOT NULL AUTO_INCREMENT,
  `districtid` int NOT NULL,
  `cityid` int DEFAULT NULL,
  `streetid` int DEFAULT NULL,
  `buildid` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `districtnames` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `docxdocuments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  `public` tinyint DEFAULT NULL,
  `templateid` int DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `public` (`public`),
  KEY `path` (`path`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `docxtemplates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `admin` varchar(255) DEFAULT NULL,
  `public` tinyint DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `path` (`path`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `dreamkas_banksta2_relations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bs2_rec_id` int NOT NULL,
  `operation_id` varchar(255) NOT NULL,
  `receipt_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bs2_rec_id` (`bs2_rec_id`),
  UNIQUE KEY `operation_id` (`operation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `dreamkas_operations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `operation_id` varchar(255) NOT NULL,
  `date_create` datetime NOT NULL,
  `date_finish` datetime NOT NULL,
  `date_resend` datetime NOT NULL,
  `status` varchar(255) NOT NULL,
  `error_code` varchar(255) NOT NULL,
  `error_message` varchar(255) NOT NULL,
  `receipt_id` varchar(255) NOT NULL,
  `operation_body` text NOT NULL,
  `repeated_fiscop_id` varchar(255) NOT NULL,
  `repeat_count` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `operation_id` (`operation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `dreamkas_services_relations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `service` varchar(42) NOT NULL,
  `goods_id` varchar(255) NOT NULL,
  `goods_name` varchar(255) NOT NULL,
  `goods_type` varchar(255) NOT NULL,
  `goods_price` double NOT NULL,
  `goods_tax` varchar(255) NOT NULL,
  `goods_vendorcode` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service` (`service`,`goods_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `dshape_time` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tariff` varchar(255) NOT NULL,
  `threshold1` time NOT NULL,
  `threshold2` time NOT NULL,
  `speed` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `emails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(45) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `employee` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `appointment` varchar(255) NOT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `telegram` varchar(40) DEFAULT NULL,
  `admlogin` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL,
  `tagid` int DEFAULT NULL,
  `amountLimit` varchar(45) NOT NULL DEFAULT '0',
  `birthdate` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `envydata` (
  `id` int NOT NULL AUTO_INCREMENT,
  `switchid` int NOT NULL,
  `date` datetime NOT NULL,
  `config` mediumtext,
  PRIMARY KEY (`id`),
  KEY `switchid` (`switchid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `envydevices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `switchid` int NOT NULL,
  `active` tinyint DEFAULT '1',
  `login` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `enablepassword` varchar(255) DEFAULT NULL,
  `custom1` varchar(255) DEFAULT NULL,
  `cutstart` int DEFAULT NULL,
  `cutend` int DEFAULT NULL,
  `port` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `envyscripts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `modelid` int NOT NULL,
  `data` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `exhorse` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `u_totalusers` int DEFAULT NULL,
  `u_activeusers` int DEFAULT NULL,
  `u_inactiveusers` int DEFAULT NULL,
  `u_frozenusers` int DEFAULT NULL,
  `u_complextotal` int DEFAULT NULL,
  `u_complexactive` int DEFAULT NULL,
  `u_complexinactive` int DEFAULT NULL,
  `u_signups` int DEFAULT NULL,
  `u_citysignups` text,
  `f_totalmoney` double DEFAULT NULL,
  `f_paymentscount` int DEFAULT NULL,
  `f_cashmoney` double DEFAULT NULL,
  `f_cashcount` int DEFAULT NULL,
  `f_arpu` double DEFAULT NULL,
  `f_arpau` double DEFAULT NULL,
  `c_totalusers` int DEFAULT NULL,
  `c_activeusers` int DEFAULT NULL,
  `c_inactiveusers` int DEFAULT NULL,
  `c_illegal` int DEFAULT NULL,
  `c_complex` int DEFAULT NULL,
  `c_social` int DEFAULT NULL,
  `c_totalmoney` double DEFAULT NULL,
  `c_paymentscount` int DEFAULT NULL,
  `c_arpu` double DEFAULT NULL,
  `c_arpau` double DEFAULT NULL,
  `c_totaldebt` double DEFAULT NULL,
  `c_signups` int DEFAULT NULL,
  `a_totalcalls` int DEFAULT NULL,
  `a_totalanswered` int DEFAULT NULL,
  `a_totalcallsduration` int DEFAULT NULL,
  `a_averagecallduration` int DEFAULT NULL,
  `e_switches` int DEFAULT NULL,
  `e_pononu` int DEFAULT NULL,
  `e_docsis` int DEFAULT NULL,
  `a_recallunsuccess` double DEFAULT NULL,
  `a_recalltrytime` int DEFAULT NULL,
  `e_deadswintervals` int DEFAULT NULL,
  `t_sigreq` int DEFAULT NULL,
  `t_tickets` int DEFAULT NULL,
  `t_tasks` int DEFAULT NULL,
  `t_capabtotal` int DEFAULT NULL,
  `t_capabundone` int DEFAULT NULL,
  `a_outtotalcalls` int DEFAULT NULL,
  `a_outtotalanswered` int DEFAULT NULL,
  `a_outtotalcallsduration` int DEFAULT NULL,
  `a_outaveragecallduration` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `extcontras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contras_id` int NOT NULL,
  `contract_id` int NOT NULL,
  `address_id` int NOT NULL,
  `period_id` int NOT NULL,
  `payday` tinyint DEFAULT NULL,
  `date_create` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contras_id` (`contras_id`),
  KEY `contract_id` (`contract_id`),
  KEY `address_id` (`address_id`),
  KEY `period_id` (`period_id`),
  KEY `payday` (`payday`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `extcontras_address` (
  `id` int NOT NULL AUTO_INCREMENT,
  `address` varchar(255) NOT NULL,
  `summ` double DEFAULT '0',
  `contract_notes` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `extcontras_contracts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contract` varchar(150) DEFAULT NULL,
  `date_start` date NOT NULL,
  `date_end` date DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `full_sum` double DEFAULT '0',
  `autoprolong` tinyint DEFAULT '1',
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract` (`contract`),
  KEY `date_start` (`date_start`),
  KEY `date_end` (`date_end`),
  KEY `subject` (`subject`),
  KEY `full_sum` (`full_sum`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `extcontras_invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contras_rec_id` int NOT NULL,
  `internal_number` varchar(150) DEFAULT '',
  `invoice_number` varchar(150) NOT NULL,
  `date` date NOT NULL,
  `summ` double DEFAULT '0',
  `summ_vat` double DEFAULT '0',
  `notes` varchar(250) DEFAULT '',
  `incoming` tinyint(1) DEFAULT '0',
  `outgoing` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `contras_rec_id` (`contras_rec_id`),
  KEY `invoice_number` (`invoice_number`),
  KEY `date` (`date`),
  KEY `summ` (`summ`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `extcontras_missed_payms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contras_rec_id` int NOT NULL,
  `profile_id` int NOT NULL,
  `contract_id` int DEFAULT NULL,
  `address_id` int DEFAULT NULL,
  `period_id` int NOT NULL,
  `payday` tinyint DEFAULT NULL,
  `date_payment` date NOT NULL,
  `date_expired` datetime NOT NULL,
  `date_payed` datetime DEFAULT NULL,
  `summ_payment` double DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `contras_rec_id` (`contras_rec_id`),
  KEY `profile_id` (`profile_id`),
  KEY `contract_id` (`contract_id`),
  KEY `address_id` (`address_id`),
  KEY `period_id` (`period_id`),
  KEY `date_payment` (`date_payment`),
  KEY `date_payed` (`date_payed`),
  KEY `summ_payment` (`summ_payment`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `extcontras_money` (
  `id` int NOT NULL AUTO_INCREMENT,
  `profile_id` int NOT NULL,
  `contract_id` int DEFAULT NULL,
  `address_id` int DEFAULT NULL,
  `accrual_id` int DEFAULT NULL,
  `invoice_id` int DEFAULT NULL,
  `purpose` varchar(255) NOT NULL DEFAULT '',
  `date` datetime NOT NULL,
  `date_edit` datetime NOT NULL,
  `summ_accrual` double DEFAULT '0',
  `summ_payment` double DEFAULT '0',
  `date_payment` date DEFAULT NULL,
  `incoming` tinyint(1) DEFAULT '0',
  `outgoing` tinyint(1) DEFAULT '0',
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `extcontras_periods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `period_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `extcontras_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `edrpo` varchar(100) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `edrpo` (`edrpo`),
  KEY `contact` (`contact`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `fdbarchive` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `devid` int DEFAULT NULL,
  `devip` varchar(64) DEFAULT NULL,
  `data` longtext,
  `datavlan` longtext,
  `dataportdescr` longtext,
  `pon` tinyint DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `devid` (`devid`,`devip`),
  KEY `pon` (`pon`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `fees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hash` varchar(42) NOT NULL,
  `login` varchar(64) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(64) DEFAULT NULL,
  `from` double DEFAULT NULL,
  `to` double DEFAULT NULL,
  `summ` double DEFAULT NULL,
  `note` varchar(200) DEFAULT NULL,
  `cashtype` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `filestorage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `scope` varchar(255) NOT NULL,
  `item` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(40) NOT NULL,
  `filename` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `scope` (`scope`),
  KEY `item` (`item`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `friendship` (
  `id` int NOT NULL AUTO_INCREMENT,
  `friend` varchar(255) NOT NULL,
  `parent` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `friend` (`friend`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `frozen_charge_days` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `freeze_days_amount` smallint NOT NULL DEFAULT '0',
  `freeze_days_used` smallint NOT NULL DEFAULT '0',
  `last_freeze_charge_dt` datetime NOT NULL,
  `work_days_restore` smallint NOT NULL DEFAULT '0',
  `days_worked` smallint NOT NULL DEFAULT '0',
  `last_workdays_upd_dt` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `garage_cars` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vendor` varchar(40) NOT NULL,
  `model` varchar(40) NOT NULL,
  `number` varchar(20) DEFAULT NULL,
  `vin` varchar(40) DEFAULT NULL,
  `year` int DEFAULT NULL,
  `power` int DEFAULT NULL,
  `engine` int DEFAULT NULL,
  `fuelconsumption` double DEFAULT NULL,
  `fueltype` varchar(16) DEFAULT NULL,
  `gastank` int DEFAULT NULL,
  `weight` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `garage_drivers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employeeid` int NOT NULL,
  `carid` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `garage_mapon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `carid` int NOT NULL,
  `unitid` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `garage_mileage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `carid` int NOT NULL,
  `mileage` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `genocide` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tariff` varchar(255) NOT NULL,
  `speed` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `gr_spec` (
  `id` int NOT NULL AUTO_INCREMENT,
  `stratid` int NOT NULL,
  `agentid` int NOT NULL,
  `type` varchar(32) NOT NULL,
  `value` int DEFAULT NULL,
  `customdata` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `gr_strat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `useassigns` tinyint NOT NULL DEFAULT '0',
  `primaryagentid` int DEFAULT NULL,
  `maxamount` int DEFAULT NULL,
  `tariff` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
CREATE TABLE `ins_homereq` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `login` varchar(64) DEFAULT NULL,
  `address` varchar(200) NOT NULL,
  `realname` varchar(200) NOT NULL,
  `mobile` varchar(64) NOT NULL,
  `email` varchar(64) NOT NULL,
  `state` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `invoice_num` varchar(40) NOT NULL DEFAULT '',
  `invoice_date` datetime NOT NULL,
  `invoice_sum` double NOT NULL DEFAULT '0',
  `invoice_body` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_num` (`invoice_num`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ipauth_denied` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `jobid` int NOT NULL,
  `workerid` int NOT NULL,
  `login` varchar(45) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `jobtypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jobname` varchar(255) NOT NULL,
  `jobcolor` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jobcolor` (`jobcolor`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `katottg` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ob` varchar(64) NOT NULL,
  `ra` varchar(64) NOT NULL,
  `tg` varchar(64) NOT NULL,
  `ci` varchar(64) NOT NULL,
  `type` varchar(2) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `katottg_cities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `katid` int NOT NULL,
  `cityid` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cityid` (`cityid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `katottg_streets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `katid` int NOT NULL,
  `streetid` int NOT NULL,
  `cd` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `streetid` (`streetid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ldap_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ldap_queue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task` varchar(255) NOT NULL,
  `param` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ldap_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `groups` text,
  `changed` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `lousytariffs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tariff` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;



CREATE TABLE `mg_credentials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `isdn` varchar(255) NOT NULL,
  `login` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `mg_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `tariffid` int NOT NULL,
  `actdate` datetime NOT NULL,
  `freeperiod` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `mg_queue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `action` varchar(45) NOT NULL,
  `tariffid` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `mg_subscribers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `tariffid` int NOT NULL,
  `actdate` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `primary` tinyint(1) NOT NULL DEFAULT '0',
  `freeperiod` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `mg_tariffs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `fee` double DEFAULT NULL,
  `serviceid` varchar(45) DEFAULT NULL,
  `primary` tinyint(1) NOT NULL DEFAULT '0',
  `freeperiod` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `mlg_culpas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(64) NOT NULL,
  `culpa` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`),
  KEY `culpa` (`culpa`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `mlg_ishimura` (
  `login` varchar(50) DEFAULT NULL,
  `month` tinyint DEFAULT NULL,
  `year` smallint DEFAULT NULL,
  `U0` bigint DEFAULT NULL,
  `D0` bigint DEFAULT NULL,
  `cash` double DEFAULT NULL,
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `mobileext` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(64) NOT NULL,
  `mobile` varchar(64) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`,`mobile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `modem_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `body` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `modems` (
  `id` int NOT NULL AUTO_INCREMENT,
  `maclan` varchar(255) NOT NULL,
  `macusb` varchar(255) NOT NULL,
  `date` date DEFAULT NULL,
  `ip` varchar(25) NOT NULL,
  `conftemplate` varchar(20) NOT NULL,
  `userbind` varchar(100) DEFAULT NULL,
  `nic` varchar(100) NOT NULL,
  `note` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `mtnasifaces` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nasid` int NOT NULL,
  `iface` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `nas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `netid` int NOT NULL,
  `nasip` varchar(255) NOT NULL,
  `nasname` varchar(255) NOT NULL,
  `nastype` varchar(45) DEFAULT NULL,
  `bandw` varchar(255) DEFAULT NULL,
  `options` text,
  PRIMARY KEY (`id`),
  KEY `netid` (`netid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `nastemplates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nasid` int NOT NULL,
  `template` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `netextips` (
  `id` int NOT NULL AUTO_INCREMENT,
  `poolid` int NOT NULL,
  `ip` varchar(40) NOT NULL,
  `nas` varchar(255) DEFAULT NULL,
  `iface` varchar(40) DEFAULT NULL,
  `mac` varchar(40) DEFAULT NULL,
  `switchid` int DEFAULT NULL,
  `port` varchar(40) DEFAULT NULL,
  `vlan` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `netextpools` (
  `id` int NOT NULL AUTO_INCREMENT,
  `netid` int NOT NULL,
  `pool` varchar(255) NOT NULL,
  `netmask` varchar(255) NOT NULL,
  `gw` varchar(255) DEFAULT NULL,
  `clientip` varchar(255) DEFAULT NULL,
  `broadcast` varchar(255) DEFAULT NULL,
  `vlan` varchar(255) DEFAULT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `nethosts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `netid` int NOT NULL,
  `ip` varchar(45) NOT NULL,
  `mac` varchar(45) DEFAULT NULL,
  `option` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `net-ip` (`netid`,`ip`),
  KEY `netid` (`netid`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `networks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `startip` varchar(45) NOT NULL,
  `endip` varchar(45) NOT NULL,
  `desc` varchar(45) NOT NULL,
  `nettype` varchar(20) NOT NULL,
  `use_radius` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `note` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `olt_qinq` (
  `id` int NOT NULL AUTO_INCREMENT,
  `swid` int NOT NULL,
  `port` int NOT NULL,
  `svlan_id` int NOT NULL,
  `cvlan` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `svlan_id` (`svlan_id`),
  KEY `cvlan` (`cvlan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `om_queue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customerid` bigint NOT NULL,
  `tariffid` int DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `om_suspend` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `om_tariffs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tariffid` int NOT NULL,
  `tariffname` varchar(255) NOT NULL,
  `type` varchar(64) NOT NULL,
  `fee` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `om_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `customerid` bigint NOT NULL,
  `basetariffid` int DEFAULT NULL,
  `bundletariffs` varchar(255) DEFAULT NULL,
  `active` int DEFAULT NULL,
  `actdate` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `op_denied` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `op_sms_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payment_id` int NOT NULL,
  `date` datetime NOT NULL,
  `login` varchar(255) NOT NULL,
  `balance` double NOT NULL DEFAULT '0',
  `summ` double NOT NULL DEFAULT '0',
  `processed` tinyint unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_id` (`payment_id`),
  KEY `login` (`login`),
  KEY `date` (`date`),
  KEY `summ` (`summ`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ophtraff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `month` tinyint NOT NULL,
  `year` smallint NOT NULL,
  `U0` bigint DEFAULT NULL,
  `D0` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ot_tariffs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `alias` varchar(128) NOT NULL,
  `fee` double NOT NULL,
  `period` varchar(8) DEFAULT NULL,
  `percent` double DEFAULT NULL,
  `main` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ot_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `remoteid` int NOT NULL,
  `login` varchar(64) NOT NULL,
  `email` varchar(64) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `code` varchar(64) DEFAULT NULL,
  `tariffid` int DEFAULT NULL,
  `addtariffid` int DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `passportdata` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `passportnum` varchar(255) DEFAULT NULL,
  `passportdate` date DEFAULT NULL,
  `passportwho` varchar(255) DEFAULT NULL,
  `pcity` varchar(255) DEFAULT NULL,
  `pstreet` varchar(255) DEFAULT NULL,
  `pbuild` varchar(10) DEFAULT NULL,
  `papt` varchar(10) DEFAULT NULL,
  `pinn` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(45) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(255) DEFAULT NULL,
  `balance` varchar(45) NOT NULL,
  `summ` varchar(45) NOT NULL,
  `cashtypeid` int NOT NULL,
  `note` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `paymentscorr` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(45) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(255) DEFAULT NULL,
  `balance` varchar(45) NOT NULL,
  `summ` varchar(45) NOT NULL,
  `cashtypeid` int NOT NULL,
  `note` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `pbxcalls` (
  `id` int NOT NULL AUTO_INCREMENT,
  `filename` varchar(250) DEFAULT NULL,
  `login` varchar(64) DEFAULT NULL,
  `size` int DEFAULT NULL,
  `direction` varchar(4) DEFAULT NULL,
  `storage` varchar(4) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `number` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login` (`login`),
  KEY `idx_date` (`date`),
  KEY `idx_number` (`number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `phones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(45) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `phone` (`phone`),
  KEY `mobile` (`mobile`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `photostorage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `scope` varchar(255) NOT NULL,
  `item` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(40) NOT NULL,
  `filename` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `scope` (`scope`),
  KEY `item` (`item`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `policedog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `mac` varchar(40) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`mac`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `policedogalerts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `mac` varchar(40) NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`mac`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `polls` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_date` datetime DEFAULT '0000-00-00 00:00:00',
  `params` text NOT NULL,
  `admin` varchar(255) NOT NULL DEFAULT '',
  `voting` varchar(255) NOT NULL DEFAULT 'Users',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `polls_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `poll_id` int NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `poll_id` (`id`,`poll_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `polls_votes` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `option_id` int NOT NULL DEFAULT '0',
  `poll_id` int NOT NULL DEFAULT '0',
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_poll` (`poll_id`,`login`) USING BTREE,
  UNIQUE KEY `login_poll_option` (`option_id`,`poll_id`,`login`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ponboxes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) DEFAULT NULL,
  `exten_info` varchar(250) DEFAULT NULL,
  `geo` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ponboxes_splitters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `boxid` int NOT NULL,
  `splitter` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ponboxeslinks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `boxid` int NOT NULL,
  `login` varchar(64) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `onuid` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ponifdesc` (
  `id` int NOT NULL AUTO_INCREMENT,
  `oltid` int NOT NULL,
  `iface` varchar(64) DEFAULT NULL,
  `desc` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oltid` (`oltid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `pononu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `onumodelid` int DEFAULT NULL,
  `oltid` int DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `mac` varchar(20) DEFAULT NULL,
  `serial` varchar(255) DEFAULT NULL,
  `login` varchar(255) DEFAULT NULL,
  `geo` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `pononuextusers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `onuid` int NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `print_card` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `field` varchar(255) NOT NULL,
  `color` varchar(255) DEFAULT '',
  `font_size` int DEFAULT NULL,
  `top` int DEFAULT NULL,
  `left` int DEFAULT NULL,
  `text` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `pt_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `login` varchar(64) NOT NULL,
  `tariff` varchar(40) NOT NULL,
  `day` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `pt_tariffs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tariff` varchar(40) NOT NULL,
  `fee` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `pt_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(64) NOT NULL,
  `day` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ptv_subscribers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `subscriberid` int NOT NULL,
  `login` varchar(64) NOT NULL,
  `maintariff` int DEFAULT NULL,
  `addtariffs` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ptv_tariffs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `serviceid` int NOT NULL,
  `main` tinyint(1) NOT NULL,
  `name` varchar(64) NOT NULL,
  `chans` varchar(42) DEFAULT NULL,
  `fee` double NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `punchscripts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `alias` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` text,
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `qinq_bindings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(45) DEFAULT NULL,
  `svlan_id` int NOT NULL,
  `cvlan` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `qinq_svlan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `realm_id` int NOT NULL,
  `svlan` int NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `realm_id` (`realm_id`),
  KEY `svlan` (`svlan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `radattr` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `attr` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `realms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `realm` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `realm` (`realm`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `realname` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(45) DEFAULT NULL,
  `realname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`),
  KEY `realname` (`realname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `salary_jobprices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jobtypeid` int NOT NULL,
  `price` double NOT NULL,
  `unit` varchar(255) NOT NULL,
  `time` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `salary_jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `taskid` int DEFAULT NULL,
  `employeeid` int NOT NULL,
  `jobtypeid` int NOT NULL,
  `factor` double DEFAULT NULL,
  `overprice` double DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `taskid` (`taskid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `salary_paid` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jobid` int NOT NULL,
  `employeeid` int NOT NULL,
  `paid` double DEFAULT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `salary_timesheets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `employeeid` int NOT NULL,
  `hours` int NOT NULL DEFAULT '0',
  `holiday` tinyint(1) NOT NULL DEFAULT '0',
  `hospital` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `salary_wages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employeeid` int NOT NULL,
  `wage` double NOT NULL,
  `bounty` double NOT NULL,
  `worktime` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `selling` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `count_cards` int DEFAULT NULL,
  `comment` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `netid` int NOT NULL,
  `desc` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `netid` (`netid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `servtariff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `serviceid` int NOT NULL,
  `tariffs` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `signup_prices_tariffs` (
  `tariff` varchar(40) NOT NULL,
  `price` double NOT NULL,
  PRIMARY KEY (`tariff`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `signup_prices_users` (
  `login` varchar(50) NOT NULL,
  `price` double NOT NULL,
  PRIMARY KEY (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `sigreq` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `state` tinyint NOT NULL,
  `ip` varchar(40) NOT NULL,
  `street` varchar(255) NOT NULL,
  `build` varchar(40) NOT NULL,
  `apt` varchar(40) NOT NULL,
  `realname` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `service` varchar(255) NOT NULL,
  `notes` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `sigreqconf` (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` varchar(255) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  KEY `key` (`key`),
  FULLTEXT KEY `value` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `sms_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `smssrvid` int NOT NULL DEFAULT '0',
  `login` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `srvmsgself_id` varchar(255) NOT NULL,
  `srvmsgpack_id` varchar(255) NOT NULL,
  `date_send` datetime NOT NULL,
  `date_statuschk` datetime NOT NULL,
  `delivered` tinyint unsigned DEFAULT '0',
  `no_statuschk` tinyint unsigned DEFAULT '0',
  `send_status` varchar(255) NOT NULL DEFAULT '',
  `msg_text` varchar(500) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `login` (`login`) USING BTREE,
  KEY `phone` (`phone`) USING BTREE,
  KEY `date_send` (`date_send`) USING BTREE,
  KEY `smssrvid` (`smssrvid`),
  KEY `srvmsgself_id` (`srvmsgself_id`) USING BTREE,
  KEY `date_statuschk` (`date_statuschk`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `sms_services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `login` varchar(255) NOT NULL,
  `passwd` varchar(255) NOT NULL,
  `url_addr` varchar(255) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `alpha_name` varchar(40) NOT NULL,
  `default_service` tinyint unsigned DEFAULT '0',
  `api_file_name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `sms_services_relations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sms_srv_id` int NOT NULL,
  `user_login` varchar(255) DEFAULT NULL,
  `employee_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_login` (`user_login`),
  UNIQUE KEY `employee_id` (`employee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `smz_excl` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mobile` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `smz_filters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `filters` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `smz_lists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `smz_nums` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numid` int NOT NULL,
  `mobile` varchar(40) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `smz_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `text` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `speeds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tariff` varchar(45) DEFAULT NULL,
  `speeddown` varchar(45) DEFAULT NULL,
  `speedup` varchar(45) DEFAULT NULL,
  `burstdownload` varchar(45) DEFAULT NULL,
  `burstupload` varchar(45) DEFAULT NULL,
  `bursttimedownload` varchar(45) DEFAULT NULL,
  `burstimetupload` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tariff` (`tariff`),
  KEY `speeddown` (`speeddown`),
  KEY `speedup` (`speedup`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `stealthtariffs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tariff` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `stickynotes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `owner` varchar(255) NOT NULL,
  `createdate` datetime NOT NULL,
  `reminddate` date DEFAULT NULL,
  `remindtime` time DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `text` text,
  PRIMARY KEY (`id`),
  KEY `owner` (`owner`),
  KEY `reminddate` (`reminddate`),
  KEY `active` (`active`),
  KEY `remindtime` (`remindtime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `stickyrevelations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `owner` varchar(255) NOT NULL,
  `showto` text,
  `createdate` datetime NOT NULL,
  `dayfrom` int DEFAULT NULL,
  `dayto` int DEFAULT NULL,
  `dayweek` int DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `text` text,
  PRIMARY KEY (`id`),
  KEY `owner` (`owner`),
  KEY `dayfrom` (`dayfrom`),
  KEY `dayto` (`dayto`),
  KEY `active` (`active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `stigma` (
  `id` int NOT NULL AUTO_INCREMENT,
  `scope` varchar(64) DEFAULT NULL,
  `itemid` varchar(128) NOT NULL,
  `state` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scope` (`scope`),
  KEY `itemid` (`itemid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `street` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cityid` int NOT NULL,
  `streetname` varchar(255) NOT NULL,
  `streetalias` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cityid` (`cityid`),
  KEY `streetname` (`streetname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `swcash` (
  `id` int NOT NULL AUTO_INCREMENT,
  `switchid` int NOT NULL,
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `switch_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupname` varchar(255) NOT NULL,
  `groupdescr` varchar(500) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupname` (`groupname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `switch_groups_relations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `switch_id` int NOT NULL,
  `sw_group_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `switch_id` (`switch_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `switch_login` (
  `id` int NOT NULL AUTO_INCREMENT,
  `swid` int DEFAULT NULL,
  `swlogin` varchar(50) DEFAULT NULL,
  `swpass` varchar(50) DEFAULT NULL,
  `method` varchar(10) DEFAULT NULL,
  `community` varchar(50) DEFAULT NULL,
  `enable` varchar(3) DEFAULT NULL,
  `snmptemplate` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `switchauth` (
  `id` int NOT NULL AUTO_INCREMENT,
  `swid` int NOT NULL,
  `login` varchar(64) DEFAULT NULL,
  `password` varchar(64) DEFAULT NULL,
  `enable` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `switchid` (`swid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `switchdeadlog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `timestamp` int NOT NULL,
  `swdead` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `switches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `modelid` int NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `desc` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `snmp` varchar(45) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  `parentid` int DEFAULT NULL,
  `swid` varchar(32) DEFAULT NULL,
  `snmpwrite` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parentid` (`parentid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `switches_qinq` (
  `switchid` int NOT NULL,
  `svlan_id` int NOT NULL,
  `cvlan` int NOT NULL,
  PRIMARY KEY (`switchid`),
  KEY `svlan_id` (`svlan_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `switchmodels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `modelname` varchar(255) NOT NULL,
  `ports` int DEFAULT NULL,
  `snmptemplate` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `switchportassign` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `switchid` int NOT NULL,
  `port` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `switchuplinks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `switchid` int NOT NULL,
  `media` varchar(10) DEFAULT NULL,
  `port` int DEFAULT NULL,
  `speed` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `switchid` (`switchid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tagid` int NOT NULL,
  `login` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `tagtypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tagname` varchar(255) NOT NULL,
  `tagcolor` varchar(15) NOT NULL,
  `tagsize` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `taskman` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `address` varchar(255) NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  `jobtype` int NOT NULL,
  `jobnote` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `phone` varchar(255) DEFAULT NULL,
  `employee` int NOT NULL,
  `employeedone` int DEFAULT NULL,
  `donenote` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `startdate` date NOT NULL,
  `starttime` time DEFAULT NULL,
  `enddate` date DEFAULT NULL,
  `admin` varchar(255) DEFAULT NULL,
  `status` int NOT NULL,
  `smsdata` text,
  `change_admin` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `login` (`login`),
  KEY `starttime` (`starttime`),
  KEY `address` (`address`),
  KEY `startdate` (`startdate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `taskmandone` (
  `id` int NOT NULL AUTO_INCREMENT,
  `taskid` int DEFAULT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `taskmanlogs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `taskid` int NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `admin` varchar(45) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `event` varchar(255) NOT NULL,
  `logs` text,
  PRIMARY KEY (`id`),
  KEY `taskid` (`taskid`) USING BTREE,
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `taskmantrack` (
  `id` int NOT NULL AUTO_INCREMENT,
  `taskid` int NOT NULL,
  `admin` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `taskid` (`taskid`,`admin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `taskstates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `taskid` int NOT NULL,
  `state` varchar(42) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `taskid` (`taskid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `taxsup` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(32) NOT NULL,
  `fee` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ticketing` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `replyid` int DEFAULT NULL,
  `status` int DEFAULT NULL,
  `from` varchar(255) DEFAULT NULL,
  `to` varchar(255) DEFAULT NULL,
  `text` text,
  `admin` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `traptypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `match` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `trinitytv_devices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) DEFAULT NULL,
  `subscriber_id` int DEFAULT NULL,
  `mac` varchar(128) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `trinitytv_subscribers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `contracttrinity` bigint DEFAULT NULL,
  `tariffid` int NOT NULL,
  `actdate` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `trinitytv_suspend` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `trinitytv_tariffs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(128) DEFAULT NULL,
  `fee` double DEFAULT '0',
  `serviceid` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ub_im` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `read` tinyint DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ub_im_pinned` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(64) NOT NULL,
  `pinned` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ubstats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` varchar(40) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `ubstorage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` varchar(255) DEFAULT NULL,
  `value` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  PRIMARY KEY (`id`),
  KEY `key` (`key`),
  FULLTEXT KEY `value` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `uhw_brute` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `password` varchar(255) NOT NULL,
  `login` varchar(255) NOT NULL,
  `mac` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `uhw_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `password` varchar(255) NOT NULL,
  `login` varchar(255) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `nhid` int NOT NULL,
  `oldmac` varchar(255) DEFAULT NULL,
  `newmac` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ukv_banksta` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `processed` tinyint NOT NULL,
  `payid` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ukv_fees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `yearmonth` varchar(42) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `yearmonth` (`yearmonth`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ukv_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userid` int NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(255) DEFAULT NULL,
  `balance` varchar(45) NOT NULL,
  `summ` varchar(45) NOT NULL,
  `visible` tinyint NOT NULL,
  `cashtypeid` int NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`,`date`,`visible`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ukv_tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tagtypeid` int NOT NULL,
  `userid` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ukv_tariffs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tariffname` varchar(255) NOT NULL,
  `price` double NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `ukv_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contract` varchar(40) DEFAULT NULL,
  `tariffid` int DEFAULT NULL,
  `tariffnmid` int DEFAULT NULL,
  `tariffnmdate` varchar(20) DEFAULT NULL,
  `cash` double NOT NULL,
  `active` tinyint NOT NULL,
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
  `cableseal` varchar(40) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract` (`contract`),
  KEY `tariffid` (`tariffid`),
  KEY `cash` (`cash`),
  KEY `active` (`active`),
  KEY `regdate` (`regdate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `user_dataexport_allowed` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(100) NOT NULL,
  `export_allowed` tinyint DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `userreg` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `admin` varchar(45) NOT NULL,
  `login` varchar(45) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `userspeeds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `speed` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `speed` (`speed`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `vcash` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `cash` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `vcashlog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(45) NOT NULL,
  `date` datetime NOT NULL,
  `balance` varchar(45) NOT NULL,
  `summ` varchar(45) NOT NULL,
  `cashtypeid` int NOT NULL,
  `note` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `visor_cams` (
  `id` int NOT NULL AUTO_INCREMENT,
  `visorid` int NOT NULL,
  `login` varchar(250) NOT NULL,
  `primary` tinyint NOT NULL,
  `camlogin` varchar(250) DEFAULT NULL,
  `campassword` varchar(250) DEFAULT NULL,
  `port` int DEFAULT NULL,
  `dvrid` int DEFAULT NULL,
  `dvrlogin` varchar(250) DEFAULT NULL,
  `dvrpassword` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `visor_chans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `visorid` int NOT NULL,
  `dvrid` int NOT NULL,
  `chan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `visor_dvrs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip` varchar(250) NOT NULL,
  `port` int DEFAULT NULL,
  `login` varchar(250) DEFAULT NULL,
  `password` varchar(250) DEFAULT NULL,
  `apiurl` varchar(255) DEFAULT NULL,
  `apikey` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `type` varchar(40) DEFAULT NULL,
  `camlimit` int DEFAULT '0',
  `customurl` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `visor_secrets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `visorid` int NOT NULL,
  `login` varchar(64) NOT NULL,
  `password` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `visor_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `regdate` datetime NOT NULL,
  `realname` varchar(250) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `chargecams` tinyint NOT NULL,
  `primarylogin` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `primarylogin` (`primarylogin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `vlan_mac_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(45) DEFAULT NULL,
  `vlan` int DEFAULT NULL,
  `mac` varchar(45) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `vlan_pools` (
  `id` int NOT NULL AUTO_INCREMENT,
  `desc` varchar(32) DEFAULT '*',
  `firstvlan` int DEFAULT NULL,
  `endvlan` int DEFAULT NULL,
  `qinq` int DEFAULT NULL,
  `svlan` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `vlan_terminators` (
  `id` int NOT NULL AUTO_INCREMENT,
  `netid` int DEFAULT NULL,
  `vlanpoolid` int DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `remote-id` varchar(50) DEFAULT NULL,
  `interface` varchar(50) DEFAULT NULL,
  `relay` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `vlanhosts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vlanpoolid` int NOT NULL,
  `login` varchar(32) DEFAULT '*',
  `vlan` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `vlanhosts_qinq` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vlanpoolid` int NOT NULL,
  `login` varchar(32) DEFAULT '*',
  `svlan` int DEFAULT NULL,
  `cvlan` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `vols_docs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(128) DEFAULT NULL,
  `date` datetime NOT NULL,
  `line_id` int DEFAULT NULL,
  `mark_id` int DEFAULT NULL,
  `path` varchar(128) NOT NULL DEFAULT '/',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `vols_lines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `point_start` varchar(255) NOT NULL,
  `point_end` varchar(255) NOT NULL,
  `fibers_amount` int NOT NULL DEFAULT '0',
  `length` double NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL,
  `employee_id` int NOT NULL,
  `param_color` varchar(32) NOT NULL,
  `param_width` int NOT NULL,
  `geo` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `vols_marks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_id` int NOT NULL,
  `number` int DEFAULT NULL,
  `placement` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `vols_marks_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon_color` varchar(255) NOT NULL DEFAULT 'blue',
  `icon_style` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `vservices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tagid` int NOT NULL,
  `price` double NOT NULL DEFAULT '0',
  `cashtype` varchar(40) NOT NULL,
  `priority` int NOT NULL,
  `fee_charge_always` tinyint(1) NOT NULL DEFAULT '1',
  `charge_period_days` tinyint NOT NULL DEFAULT '0',
  `exclude_tags` varchar(255) NOT NULL DEFAULT '',
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `watchdog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL DEFAULT '0',
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `wcpedevices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `modelid` int NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `mac` varchar(45) DEFAULT NULL,
  `snmp` varchar(45) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `bridge` tinyint NOT NULL DEFAULT '0',
  `uplinkapid` int DEFAULT NULL,
  `uplinkcpeid` int DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `wcpeusers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cpeid` int NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `wdycinfo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `missedcount` int DEFAULT NULL,
  `recallscount` int DEFAULT NULL,
  `unsucccount` int DEFAULT NULL,
  `missednumbers` text,
  `totaltrytime` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `weblogs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `admin` varchar(45) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  FULLTEXT KEY `ft_event` (`event`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `wh_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `wh_contractors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `wh_in` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `itemtypeid` int NOT NULL,
  `contractorid` int NOT NULL,
  `count` double NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `storageid` int NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `admin` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`itemtypeid`,`contractorid`,`storageid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `wh_itemtypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categoryid` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `unit` varchar(40) NOT NULL,
  `reserve` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categoryid` (`categoryid`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `wh_out` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `desttype` varchar(40) NOT NULL,
  `destparam` varchar(255) NOT NULL,
  `storageid` int NOT NULL,
  `itemtypeid` int NOT NULL,
  `count` double NOT NULL,
  `price` double DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `netw` tinyint DEFAULT '0',
  `admin` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`storageid`,`itemtypeid`),
  KEY `desttype` (`desttype`),
  KEY `destparam` (`destparam`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `wh_reserve` (
  `id` int NOT NULL AUTO_INCREMENT,
  `storageid` int NOT NULL,
  `itemtypeid` int NOT NULL,
  `count` double NOT NULL,
  `employeeid` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `storageid` (`storageid`),
  KEY `itemtypeid` (`itemtypeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `wh_reshist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resid` int DEFAULT NULL,
  `date` datetime NOT NULL,
  `type` varchar(40) NOT NULL,
  `storageid` int DEFAULT NULL,
  `itemtypeid` int DEFAULT NULL,
  `count` double DEFAULT NULL,
  `employeeid` int DEFAULT NULL,
  `admin` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`storageid`,`itemtypeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `wh_returns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `outid` int NOT NULL,
  `storageid` int NOT NULL,
  `itemtypeid` int NOT NULL,
  `count` double NOT NULL,
  `price` double NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(64) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `outid` (`outid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `wh_salesitems` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reportid` int NOT NULL,
  `itemtypeid` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `reportid` (`reportid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `wh_salesreports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `wh_storages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `whiteboard` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categoryid` int NOT NULL,
  `admin` varchar(255) NOT NULL,
  `employeeid` int DEFAULT NULL,
  `createdate` datetime NOT NULL,
  `donedate` datetime DEFAULT NULL,
  `priority` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `text` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `youtv_subscribers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `subscriberid` int NOT NULL,
  `login` varchar(64) NOT NULL,
  `maintariff` int DEFAULT NULL,
  `addtariffs` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `youtv_tariffs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `serviceid` int NOT NULL,
  `main` tinyint(1) NOT NULL,
  `name` varchar(64) NOT NULL,
  `chans` varchar(42) DEFAULT NULL,
  `fee` double NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `zbsannhist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `annid` int NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `annid` (`annid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `zbsannouncements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `public` tinyint DEFAULT '0',
  `type` varchar(20) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `public` (`public`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `zbssclog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `zte_cards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `swid` int NOT NULL,
  `slot_number` int NOT NULL,
  `card_name` varchar(7) NOT NULL,
  `chasis_number` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `swid` (`swid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `zte_qinq` (
  `id` int NOT NULL AUTO_INCREMENT,
  `swid` int NOT NULL,
  `slot_number` int NOT NULL,
  `port` int NOT NULL,
  `svlan_id` int NOT NULL,
  `cvlan` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `svlan_id` (`svlan_id`),
  KEY `cvlan` (`cvlan`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `zte_vlan_bind` (
  `id` int NOT NULL AUTO_INCREMENT,
  `swid` int NOT NULL,
  `slot_number` int NOT NULL,
  `port_number` int NOT NULL,
  `vlan` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `swid` (`swid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- after patch
INSERT INTO `cashtype` (`Id`, `cashtype`) VALUES (1, 'Cash money');
INSERT INTO `directions` (`id`, `rulenumber`, `rulename`) VALUES (1, 0, 'Internet');
INSERT INTO `qinq_svlan` VALUES (1,1,0,'Use it for untagged VLAN');
INSERT INTO `realms` VALUES (1,'default','default realm');
INSERT INTO `print_card` (`title`, `field`, `color`, `font_size`, `top`, `left`, `text`)
VALUES
    ('Serial number', 'number', '0.0.0', '12', '80', '130', 'Номер № {number}'),
    ('Serial part', 'serial', '0.0.0', '12', '80', '110', 'Серія {serial}'),
    ('Price', 'rating', '139.0.139', '16', '120', '90', 'Номінал {sum}грн. '),
    ('Phone', 'phone', '0.0.0', '8', '160', '3', '+38(096)xxx-xx-xx, +38(096)xxx-xx-xx, +38(096)xxx-xx-xx'),
('Site', 'site', '0.0.0', '10', '15', '5', 'Сайт: xxx.xxx.ua');