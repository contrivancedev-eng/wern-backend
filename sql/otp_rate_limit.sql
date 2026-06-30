-- OTP send rate limiting: max 5 sends per identifier within the window,
-- then a 15-minute block. One row per (identifier, purpose).
-- Run this once on the WERN database.
CREATE TABLE IF NOT EXISTS `otp_rate_limit` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `identifier`    VARCHAR(190) NOT NULL,       -- e.g. email address
  `purpose`       VARCHAR(40) NOT NULL,        -- verify | forgot
  `attempt_count` INT(11) NOT NULL DEFAULT 0,
  `window_start`  DATETIME NOT NULL,
  `blocked_until` DATETIME DEFAULT NULL,
  `ip_address`    VARCHAR(45) DEFAULT NULL,
  `updated_at`    DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_identifier_purpose` (`identifier`, `purpose`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
