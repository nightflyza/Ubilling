ALTER TABLE `ophtraff` ADD `LAST_D` BIGINT NULL DEFAULT NULL AFTER `D0`;
ALTER TABLE `ophtraff` ADD `LAST_U` BIGINT NULL DEFAULT NULL AFTER `LAST_D`;
ALTER TABLE `gen_refuels` ADD `fulltank` TINYINT(1) NOT NULL DEFAULT 0 AFTER `price`;
ALTER TABLE op_transactions ADD INDEX processed (processed);
ALTER TABLE op_transactions ADD KEY ix_op_transactions_date(date);