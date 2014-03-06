/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * Core database set up for empty tables in LWT
 */


-- Build Schema

DROP SCHEMA IF EXISTS `librewebtools`;

CREATE SCHEMA IF NOT EXISTS `librewebtools` DEFAULT CHARACTER SET utf8;

USE `librewebtools`;


-- -----------------------------------------------------
-- Table `roles` 
-- -----------------------------------------------------

DROP TABLE IF EXISTS `roles` ;

CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sortorder`   INT(11) NOT NULL ,
  `name`  VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`name` ASC)
)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `companies` 
-- -----------------------------------------------------
DROP TABLE IF EXISTS `companies` ;

CREATE TABLE IF NOT EXISTS `companies` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(100) NOT NULL,
  `license_key` VARCHAR(20) NOT NULL,
  `license_users` INT(11) NOT NULL,
  `license_date` DATETIME NOT NULL,
  `nation_code` VARCHAR(3) NULL,
  `state_code` VARCHAR(4) NULL,
  `postal_code` VARCHAR(10) NULL,
  `city` VARCHAR(100) NULL,
  `addr1` VARCHAR(100) NULL,
  `addr2` VARCHAR(100) DEFAULT NULL,
  `desc` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`name` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `users` 
-- -----------------------------------------------------
DROP TABLE IF EXISTS `users` ;

CREATE TABLE IF NOT EXISTS `users` (
  `id`  INT(11) NOT NULL AUTO_INCREMENT ,
  `company_id` INT(11) NOT NULL ,
  `login`  VARCHAR(40) NOT NULL ,
  `firstname`  VARCHAR(100) NOT NULL ,
  `lastname`  VARCHAR(100) NOT NULL ,
  `email`  VARCHAR(255) NOT NULL ,
  `notes`  TEXT NULL ,
  PRIMARY KEY (`id`) ,
  FOREIGN KEY (`company_id`) 
    REFERENCES `companies` (`id`) 
    ON DELETE NO ACTION 
    ON UPDATE CASCADE
)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `user_roles` 
-- -----------------------------------------------------
DROP TABLE IF EXISTS `user_roles` ;

CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` INT(11) NOT NULL ,
  `role_id` INT(11) NOT NULL ,
  PRIMARY KEY (`role_id`, `user_id`) ,
  FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  FOREIGN KEY (`role_id`) 
    REFERENCES `roles` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `passwords` 
-- -----------------------------------------------------
DROP TABLE IF EXISTS `passwords` ;

CREATE TABLE IF NOT EXISTS `passwords` (
  `user_id`  INT(11) NOT NULL ,
  `valid_date` DATETIME NOT NULL ,
  `expire_date` DATETIME NULL ,
  `reset` TINYINT NOT NULL DEFAULT 0,
  `reset_code` VARCHAR(255) NULL ,
  `hash` VARCHAR(255) NOT NULL ,
  `key` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`user_id`, `valid_date`) ,
  FOREIGN KEY (`user_id` )
    REFERENCES `users` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB;


