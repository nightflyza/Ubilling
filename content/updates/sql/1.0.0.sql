ALTER TABLE `visor_users` ADD `primarylogin` VARCHAR(255) NULL AFTER `chargecams`, ADD INDEX (`primarylogin`);

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
