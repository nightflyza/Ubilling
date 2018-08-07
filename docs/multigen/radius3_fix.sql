
ALTER TABLE `mlg_acct` ADD COLUMN `acctupdatetime` datetime NULL default NULL AFTER `acctstarttime`;

ALTER TABLE `mlg_acct` ADD COLUMN `acctinterval` int(12) default NULL AFTER `acctstoptime`;


