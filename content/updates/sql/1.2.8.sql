CREATE TABLE IF NOT EXISTS `mlg_nascustom` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(32) NOT NULL,
  `name` varchar(64) NOT NULL,
  `secret` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE OR REPLACE VIEW `mlg_clients` (`nasname`, `shortname`, `type`, `ports`, `secret`, `server`) AS
SELECT DISTINCT 
  COALESCE(mlg_nascustom.ip, nas.`nasip`, NULL) AS `nasname`,
  COALESCE(mlg_nascustom.name, nas.`nasname`, NULL) AS `shortname`,
  'other' AS `type`,
  NULL AS `ports`,
  COALESCE(mlg_nascustom.secret, left(md5(inet_aton(nas.`nasip`)),12), NULL) AS `secret`,
  NULL AS `server` 
from `nas` 
left join mlg_nascustom on (nas.nasip = mlg_nascustom.ip) 
GROUP BY nasname
UNION SELECT DISTINCT 
  `ip` AS `nasname`, 
  `name` AS `shortname`, 
  'other' AS `type`, 
  NULL AS `ports`, 
  `secret` as `secret`, 
  NULL as `server` 
from `mlg_nascustom` 
LEFT JOIN nas ON (mlg_nascustom.ip = nas.nasip) 
where nasname is null
GROUP BY `ip`;