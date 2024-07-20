ALTER TABLE `fdbarchive` ADD `datavlan` longtext NULL DEFAULT NULL AFTER `data`;
ALTER TABLE `fdbarchive` ADD `dataportdescr` longtext NULL DEFAULT NULL AFTER `datavlan`;
