-- Tracks the devices / IPs a user has logged in from, so we can detect
-- and alert on logins from new / unrecognized devices.
-- Run this once on the WERN database.
CREATE TABLE IF NOT EXISTS `user_login_devices` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11) NOT NULL,
  `device_id`  VARCHAR(255) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `first_seen` DATETIME DEFAULT NULL,
  `last_seen`  DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_user_device` (`user_id`, `device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
