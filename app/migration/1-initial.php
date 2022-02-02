<?php

$migration = "

CREATE TABLE `client_settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int unsigned NOT NULL,
  `name` varchar(30) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT '',
  `validation` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

CREATE TABLE `clients` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL,
  `type` tinyint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE `otp_number_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `number_id` int NOT NULL,
  `date` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=556 DEFAULT CHARSET=utf8;

CREATE TABLE `otp_numbers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `number` bigint NOT NULL,
  `date` int NOT NULL,
  `status` int NOT NULL DEFAULT '0',
  `code` varchar(20) DEFAULT NULL,
  `range_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=788 DEFAULT CHARSET=utf8;


CREATE TABLE `otp_number_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `number_id` int NOT NULL,
  `date` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=556 DEFAULT CHARSET=utf8;


CREATE TABLE `otp_ranges` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` int NOT NULL,
  `start` bigint NOT NULL,
  `end` bigint NOT NULL,
  `status` int NOT NULL,
  `country_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(20) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `group` tinyint(1) DEFAULT NULL,
  `client_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

INSERT INTO `client_settings` (`id`, `client_id`, `name`, `value`, `type`, `validation`) VALUES
(1, 1, 'timezone', 'Europe/Chisinau', 'timezone', ''),
(2, 1, 'service_status', '1', 'admin_only', '0/1'),
(3, 1, 'dialer_type', '0', 'superadmin_only', '0/1');

INSERT INTO `clients` (`id`, `name`, `type`) VALUES
(1, 'opt', 0);


INSERT INTO `otp_ranges` (`id`, `partner_id`, `start`, `end`, `status`, `country_id`) VALUES
(1, 1, 21650000001, 21658999999, 1, 89),
(2, 1, 258870000001, 258879999999, 1, 80),
(3, 1, 213550111111, 213562999999, 1, 58),
(4, 2, 79007897500, 79007897999, 0, 196),
(5, 2, 79025437500, 79025437999, 0, 196),
(6, 2, 79045443500, 79045443999, 0, 196),
(7, 2, 9779740000001, 9779869999999, 1, 81),
(8, 2, 9647500000001, 9647599999999, 1, 47),
(9, 1, 22601011111, 22602911111, 1, 152);

INSERT INTO `users` (`id`, `username`, `password`, `group`, `client_id`) VALUES
(1, 'ar', '$2y$10$/eZs6iSpj.JJWSz3ibM/mOeuf0XJSAMIe9o3sSV4hR2jsryspEBYC', 13, 1),
";

//user ar:J23Kydussssoi8%_976sN8sSs2

$rollback = "
drop table `client_settings`;
drop table `users`;
drop table `clients`;
drop table `otp_ranges`;
drop table `otp_numbers`;
drop table `otp_number_requests`;
";