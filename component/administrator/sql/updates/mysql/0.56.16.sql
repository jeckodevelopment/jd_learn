ALTER TABLE `#__jlms_mail_queue`
  ADD COLUMN `course_id` BIGINT UNSIGNED NULL AFTER `template_id`,
  ADD COLUMN `recipient_user_id` INT UNSIGNED NULL AFTER `course_id`,
  ADD COLUMN `recipient_consent_at` DATETIME(6) NULL AFTER `recipient_user_id`,
  ADD KEY `ix_announcement_campaign` (`course_id`,`template_id`,`state`);

CREATE TABLE IF NOT EXISTS `#__jlms_course_announcement_campaigns` (
  `course_id` BIGINT UNSIGNED NOT NULL,
  `requested_by` INT UNSIGNED NOT NULL,
  `recipient_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `state` VARCHAR(16) NOT NULL DEFAULT 'queued',
  `requested_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`course_id`),
  KEY `ix_announcement_campaign_state` (`state`,`requested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
