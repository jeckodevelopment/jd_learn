CREATE TABLE IF NOT EXISTS `#__jlms_mail_unsubscribe_tokens` (
  `user_id` INT UNSIGNED NOT NULL,
  `consent_confirmed_at` DATETIME(6) NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `created_at` DATETIME(6) NOT NULL,
  `expires_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`token_hash`),
  KEY `ix_mail_unsubscribe_user_expiry` (`user_id`,`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jlms_mail_unsubscribe_tokens` ADD CONSTRAINT `#__jlms_fk_mail_unsubscribe_consent` FOREIGN KEY (`user_id`) REFERENCES `#__jlms_mail_consents` (`user_id`) ON DELETE CASCADE ON UPDATE RESTRICT;
