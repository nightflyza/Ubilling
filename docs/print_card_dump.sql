-- -----------------------------------------------------
-- Table `print_card`
-- -----------------------------------------------------
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
