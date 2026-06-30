-- FCM device tokens (one row per device; a user can have multiple devices).
-- Run this once on the WERN database.
CREATE TABLE IF NOT EXISTS `user_fcm_tokens` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11) NOT NULL,
  `fcm_token`  VARCHAR(512) NOT NULL,
  `platform`   VARCHAR(20) DEFAULT NULL,      -- android | ios | web (optional)
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_fcm_token` (`fcm_token`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
