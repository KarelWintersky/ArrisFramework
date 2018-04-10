SET NAMES utf8;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE TABLE `phpauth_attempts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ip` INT(10) UNSIGNED NOT NULL,
  `count` INT(11) NOT NULL,
  `expiredate` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

CREATE TABLE `phpauth_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `uid` INT(11) NOT NULL,
  `token` CHAR(20) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `expire` DATETIME NOT NULL,
  `type` ENUM('activation','reset') CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `token` (`token`),
  KEY `uid` (`uid`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `phpauth_sessions`;
CREATE TABLE `phpauth_sessions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `uid` INT(11) NOT NULL,
  `hash` VARCHAR(40) NOT NULL,
  `expiredate` DATETIME NOT NULL,
  `ip` INT(10) UNSIGNED NOT NULL,
  `agent` VARCHAR(200) NOT NULL,
  `cookie_crc` VARCHAR(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `phpauth_users`;
CREATE TABLE `phpauth_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) DEFAULT NULL,
  `password` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
  `isactive` TINYINT(1) NOT NULL DEFAULT '0',
  `dt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;