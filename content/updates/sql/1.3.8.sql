ALTER TABLE `visor_dvrs` ADD `apiurl` VARCHAR(255) NULL DEFAULT NULL AFTER `password`;

ALTER TABLE `banksta2_presets` ADD `sum_in_coins` tinyint(3) DEFAULT 0 AFTER `col_paysum`;
ALTER TABLE `banksta2_presets` ADD `noesc_inet_srv_keywords` tinyint(3) DEFAULT 0 AFTER `inet_srv_keywords`;
ALTER TABLE `banksta2_presets` ADD `noesc_ukv_srv_keywords` tinyint(3) DEFAULT 0 AFTER `ukv_srv_keywords`;
ALTER TABLE `banksta2_presets` ADD `noesc_skip_row_keywords` tinyint(3) DEFAULT 0 AFTER `skip_row_keywords`;
ALTER TABLE `banksta2_presets` ADD `noesc_replace_keywords` tinyint(3) DEFAULT 0 AFTER `replacements_cnt`;
ALTER TABLE `banksta2_presets` ADD `noesc_remove_keywords` tinyint(3) DEFAULT 0 AFTER `strs_to_remove`;