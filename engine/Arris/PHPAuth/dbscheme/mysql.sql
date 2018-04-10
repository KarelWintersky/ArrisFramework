SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE TABLE `attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` int(10) unsigned NOT NULL,
  `count` int(11) NOT NULL,
  `expiredate` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8;

CREATE TABLE `phpauth_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `token` char(20) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `expire` datetime NOT NULL,
  `type` enum('activation','reset') CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `token` (`token`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `hash` varchar(40) NOT NULL,
  `expiredate` datetime NOT NULL,
  `ip` int(10) unsigned NOT NULL,
  `agent` varchar(200) NOT NULL,
  `cookie_crc` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) DEFAULT NULL,
  `password` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
  `isactive` TINYINT(1) NOT NULL DEFAULT '0',
  `dt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;



