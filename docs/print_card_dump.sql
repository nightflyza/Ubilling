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

INSERT INTO
    `print_card` (`title`, `field`, `color`, `font_size`, `top`, `left`, `text`)
VALUES
    ('Serial number', 'number', '0.0.0', '12', '80', '130', 'Номер № {number}'),
    ('Serial part', 'serial', '0.0.0', '12', '80', '110', 'Серия {serial}'),
    ('Price', 'rating', '139.0.139', '16', '120', '90', 'Номинал {sum}грн. '),
    ('Phone', 'phone', '0.0.0', '8', '160', '3', '+38(096)xxx-xx-xx, +38(096)xxx-xx-xx, +38(096)xxx-xx-xx'),
    ('Site', 'site', '0.0.0', '10', '15', '5', 'Сайт: xxx.xxx.ua');
