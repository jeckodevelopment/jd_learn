-- Persistent, idempotent queue for core JD Learn email notifications.
CREATE TABLE IF NOT EXISTS `#__jlms_mail_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_key` CHAR(64) NOT NULL,
  `template_id` VARCHAR(100) NOT NULL,
  `language` VARCHAR(7) NOT NULL DEFAULT '*',
  `recipient_email` VARCHAR(320) NOT NULL,
  `recipient_name` VARCHAR(255) NOT NULL DEFAULT '',
  `template_data` LONGTEXT NOT NULL,
  `state` VARCHAR(16) NOT NULL DEFAULT 'pending',
  `attempts` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `available_at` DATETIME(6) NOT NULL,
  `claimed_at` DATETIME(6) NULL,
  `sent_at` DATETIME(6) NULL,
  `last_error` VARCHAR(1000) NULL,
  `created_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_key` (`event_key`),
  KEY `ix_ready` (`state`,`available_at`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
