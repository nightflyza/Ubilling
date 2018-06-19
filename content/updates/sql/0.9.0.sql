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
