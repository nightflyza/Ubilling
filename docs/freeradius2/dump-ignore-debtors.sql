CREATE OR REPLACE VIEW `radius_check` (`UserName`, `Attribute`, `op`, `Value`) AS
SELECT
  CASE `radius_reassigns`.`value`
    WHEN 'ip'  THEN `nethosts`.`ip`
    WHEN 'mac' THEN `nethosts`.`mac`
  ELSE `users`.`login`
END AS `UserName`, `radius_attributes`.`Attribute`, `radius_attributes`.`op`, 
-- Обработка макросов значений
CASE 
	-- Общая информация о пользователе
	WHEN `radius_attributes`.`Value` LIKE '%{user[login]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{user[login]}',    `users`.`login`)
	WHEN `radius_attributes`.`Value` LIKE '%{user[Password]}%' THEN REPLACE(`radius_attributes`.`Value`, '{user[Password]}', `users`.`Password`)
	WHEN `radius_attributes`.`Value` LIKE '%{user[Tariff]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{user[Tariff]}',   `users`.`Tariff`)
  -- Информация IP/MAC
	WHEN `radius_attributes`.`Value` LIKE '%{nethost[ip]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{nethost[ip]}',    `nethosts`.`ip`)
	WHEN `radius_attributes`.`Value` LIKE '%{nethost[mac]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{nethost[mac]}',   `nethosts`.`mac`)
  -- Информация о сети пользователя
	WHEN `radius_attributes`.`Value` LIKE '%{network[id]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{network[id]}',    `networks`.`id`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[ip]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{network[ip]}',    SUBSTRING_INDEX(`networks`.`desc`, '/',  1))
	WHEN `radius_attributes`.`Value` LIKE '%{network[start]}%' THEN REPLACE(`radius_attributes`.`Value`, '{network[start]}', `networks`.`startip`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[end]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{network[end]}',   `networks`.`endip`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[desc]}%'  THEN REPLACE(`radius_attributes`.`Value`, '{network[desc]}',  `networks`.`desc`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[cidr]}%'  THEN REPLACE(`radius_attributes`.`Value`, '{network[cidr]}',  SUBSTRING_INDEX(`networks`.`desc`, '/', -1))
  -- Информации о принадлежности к коммутатору
	WHEN `radius_attributes`.`Value` LIKE '%{switch[ip]}%'     THEN REPLACE(`radius_attributes`.`Value`, '{switch[ip]}',     `switches`.`ip`)
	WHEN `radius_attributes`.`Value` LIKE '%{switch[port]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{switch[port]}',   `switchportassign`.`port`)
  -- Информация о скорости пользователя
	WHEN `radius_attributes`.`Value` LIKE '%{speed[up]}%'      THEN REPLACE(`radius_attributes`.`Value`, '{speed[up]}',      `speeds`.`speedup`)
	WHEN `radius_attributes`.`Value` LIKE '%{speed[down]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{speed[down]}',    `speeds`.`speeddown`)
  -- Состояние пользователя ( OFF-LINE, ON-LINE, PASSIVE или DOWN )
	WHEN `radius_attributes`.`Value` LIKE '%{user[state]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{user[state]}',   (
    CASE
      WHEN `users`.`Down`     THEN 'DOWN'
      WHEN `users`.`Passive`  THEN 'PASSIVE'
      WHEN `users`.`Cash` < -`users`.`Credit`
                              THEN 'OFF-LINE'
      ELSE 'ON-LINE'
    END
  ))
  -- Или возвращаем значание
	ELSE `radius_attributes`.`Value`
END as `Value`
-- Конец обработки макросов
 FROM `users`
 -- ...для получения IP/MAC
      JOIN `nethosts` ON `nethosts`.`ip` = `users`.`IP`
 -- ...для получения информации о сети
      JOIN `networks` ON `networks`.`id` = `nethosts`.`netid`
      JOIN `nas`      ON `nas`.`netid`   = `nethosts`.`netid`
 -- ...для сбора всех атрибутов для пользователя или всех пользователях
      JOIN `radius_attributes` ON  `radius_attributes`.`login` = `users`.`login`
                               OR (`radius_attributes`.`login` = '*' AND `radius_attributes`.`netid` = `networks`.`id` )
                               OR (`radius_attributes`.`login` = '*' AND `radius_attributes`.`nasip` = INET_ATON(`nas`.`nasip`))
 -- ...для переназначения User-Name на IP или MAC
 LEFT JOIN `radius_reassigns` ON `radius_reassigns`.`netid` = `nethosts`.`netid`
 -- ...для получения информации о коммутаторе, к которому подключен пользователь
 LEFT JOIN `switchportassign` ON `switchportassign`.`login` = `users`.`login`
 LEFT JOIN `switches` ON `switches`.`id` = `switchportassign`.`switchid`
 -- ...для получения информации о скорости по тарифному плану
 LEFT JOIN `speeds`   ON `speeds`.`tariff` = `users`.`Tariff`
WHERE `radius_attributes`.`scenario` = 'check' AND `networks`.`use_radius` = TRUE AND `users`.`Down` != '1' AND `users`.`Passive` != '1' AND `users`.`Cash` >= -`users`.`Credit`
ORDER BY `users`.`login`;

CREATE OR REPLACE VIEW `radius_reply` (`UserName`, `Attribute`, `op`, `Value`) AS
SELECT
  CASE `radius_reassigns`.`value`
    WHEN 'ip'  THEN `nethosts`.`ip`
    WHEN 'mac' THEN `nethosts`.`mac`
  ELSE `users`.`login`
END AS `UserName`, `radius_attributes`.`Attribute`, `radius_attributes`.`op`, 
-- Обработка макросов значений
CASE 
	-- Общая информация о пользователе
	WHEN `radius_attributes`.`Value` LIKE '%{user[login]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{user[login]}',    `users`.`login`)
	WHEN `radius_attributes`.`Value` LIKE '%{user[Password]}%' THEN REPLACE(`radius_attributes`.`Value`, '{user[Password]}', `users`.`Password`)
	WHEN `radius_attributes`.`Value` LIKE '%{user[Tariff]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{user[Tariff]}',   `users`.`Tariff`)
  -- Информация IP/MAC
	WHEN `radius_attributes`.`Value` LIKE '%{nethost[ip]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{nethost[ip]}',    `nethosts`.`ip`)
	WHEN `radius_attributes`.`Value` LIKE '%{nethost[mac]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{nethost[mac]}',   `nethosts`.`mac`)
  -- Информация о сети пользователя
	WHEN `radius_attributes`.`Value` LIKE '%{network[id]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{network[id]}',    `networks`.`id`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[ip]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{network[ip]}',    SUBSTRING_INDEX(`networks`.`desc`, '/',  1))
	WHEN `radius_attributes`.`Value` LIKE '%{network[start]}%' THEN REPLACE(`radius_attributes`.`Value`, '{network[start]}', `networks`.`startip`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[end]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{network[end]}',   `networks`.`endip`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[desc]}%'  THEN REPLACE(`radius_attributes`.`Value`, '{network[desc]}',  `networks`.`desc`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[cidr]}%'  THEN REPLACE(`radius_attributes`.`Value`, '{network[cidr]}',  SUBSTRING_INDEX(`networks`.`desc`, '/', -1))
  -- Информации о принадлежности к коммутатору
	WHEN `radius_attributes`.`Value` LIKE '%{switch[ip]}%'     THEN REPLACE(`radius_attributes`.`Value`, '{switch[ip]}',     `switches`.`ip`)
	WHEN `radius_attributes`.`Value` LIKE '%{switch[port]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{switch[port]}',   `switchportassign`.`port`)
  -- Информация о скорости пользователя
	WHEN `radius_attributes`.`Value` LIKE '%{speed[up]}%'      THEN REPLACE(`radius_attributes`.`Value`, '{speed[up]}',      `speeds`.`speedup`)
	WHEN `radius_attributes`.`Value` LIKE '%{speed[down]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{speed[down]}',    `speeds`.`speeddown`)
  -- Состояние пользователя ( OFF-LINE, ON-LINE, PASSIVE или DOWN )
	WHEN `radius_attributes`.`Value` LIKE '%{user[state]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{user[state]}',    (
    CASE
      WHEN `users`.`Down`    THEN 'DOWN'
      WHEN `users`.`Passive` THEN 'PASSIVE'
      WHEN `users`.`Cash` < -`users`.`Credit`
                             THEN 'OFF-LINE'
      ELSE 'ON-LINE'
    END
  ))
  -- Или возвращаем значание
	ELSE `radius_attributes`.`Value`
END AS `Value`
-- Конец обработки макросов
 FROM `users`
 -- ...для получения IP/MAC
      JOIN `nethosts` ON `nethosts`.`ip` = `users`.`IP`
 -- ...для получения информации о сети
      JOIN `networks` ON `networks`.`id` = `nethosts`.`netid`
      JOIN `nas`      ON `nas`.`netid`   = `nethosts`.`netid`
 -- ...для сбора всех атрибутов пользователя, сети, сервера доступа, группы
      JOIN `radius_attributes` ON  `radius_attributes`.`login` = `users`.`login`
                               OR (`radius_attributes`.`login` = '*' AND `radius_attributes`.`netid` = `networks`.`id` )
                               OR (`radius_attributes`.`login` = '*' AND `radius_attributes`.`nasip` = INET_ATON(`nas`.`nasip`))
 -- ...для переназначения User-Name на IP или MAC
 LEFT JOIN `radius_reassigns` ON `radius_reassigns`.`netid` = `nethosts`.`netid`
 -- ...для получения информации о коммутаторе, к которому подключен пользователь
 LEFT JOIN `switchportassign` ON `switchportassign`.`login` = `users`.`login`
 LEFT JOIN `switches` ON `switches`.`id` = `switchportassign`.`switchid`
 -- ...для получения информации о скорости по тарифному плану
 LEFT JOIN `speeds`   ON `speeds`.`tariff` = `users`.`Tariff`
WHERE `radius_attributes`.`scenario` = 'reply' AND `networks`.`use_radius` = TRUE AND `users`.`Down` != '1' AND `users`.`Passive` != '1' AND `users`.`Cash` >= -`users`.`Credit`
ORDER BY `users`.`login`;
