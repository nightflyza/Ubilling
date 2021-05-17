CREATE TABLE `youtv_subscribers` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `subscriberid` int(11) NOT NULL,
  `login` varchar(64) NOT NULL,
  `maintariff` int(11) DEFAULT NULL,
  `addtariffs` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `youtv_subscribers`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `youtv_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;



CREATE TABLE `youtv_tariffs` (
  `id` int(11) NOT NULL,
  `serviceid` int(11) NOT NULL,
  `main` tinyint(1) NOT NULL,
  `name` varchar(64) NOT NULL,
  `chans` varchar(42) DEFAULT NULL,
  `fee` double NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `youtv_tariffs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `youtv_tariffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

