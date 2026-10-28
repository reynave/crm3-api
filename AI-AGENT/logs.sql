ALTER TABLE `crm3_opportunity`
	ADD COLUMN `id_opportunity_sales_stage` INT(1) NOT NULL AFTER `closed`;



    CREATE TABLE `crm3_opportunity_sales_stage` (
	`id` INT(5) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(250) NOT NULL DEFAULT '' COLLATE 'latin1_swedish_ci',
	`probability` TINYINT(4) NOT NULL DEFAULT '0',
	`id_opportunity_stage` SMALLINT(6) NOT NULL DEFAULT '100',
	`presence` INT(1) NOT NULL DEFAULT '1',
	`input_date` DATETIME NOT NULL DEFAULT '2019-01-01 00:00:00',
	`input_by` INT(11) NOT NULL DEFAULT '0',
	`update_date` DATETIME NOT NULL DEFAULT '2019-01-01 00:00:00',
	`update_by` INT(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`) USING BTREE
)
COLLATE='latin1_swedish_ci'
ENGINE=InnoDB
AUTO_INCREMENT=3010
;
