create database if not exists db_host;
use db_host;
CREATE TABLE IF NOT EXISTS `student` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `surname` varchar(30) NOT NULL,
    `firstname` varchar(30) NOT NULL,
    `class` varchar(10) NOT NULL,
    `ldap` tinyint(1) NOT NULL DEFAULT 0,
    `bool` tinyint(1) NOT NULL DEFAULT 0,
    `passage` int(5) NOT NULL DEFAULT 0,
    `absence` tinyint(1) NOT NULL DEFAULT 0,
    `noteaddition` int(100) DEFAULT NULL,
    `notetotal` int(10) DEFAULT NULL,
    `average` int(10) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;