ALTER TABLE `#__jlms_courses`
  ADD COLUMN `admissions_open` TINYINT(1) NOT NULL DEFAULT 1 AFTER `enrollment_mode`,
  ADD COLUMN `delivery_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `admissions_open`;

CREATE TABLE IF NOT EXISTS `#__jlms_course_operations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` BIGINT UNSIGNED NOT NULL,
  `revision_no` INT UNSIGNED NOT NULL,
  `operation` VARCHAR(32) NOT NULL,
  `previous_value` TINYINT(1) NOT NULL,
  `target_value` TINYINT(1) NOT NULL,
  `impact_hash` CHAR(64) NOT NULL,
  `reason` VARCHAR(500) NOT NULL,
  `actor_user_id` INT UNSIGNED NULL,
  `mutation_id` CHAR(36) NOT NULL,
  `created_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_course_operation_revision` (`course_id`,`revision_no`),
  UNIQUE KEY `uq_course_operation_mutation` (`mutation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jlms_course_operations` ADD CONSTRAINT `#__jlms_fk_course_operation` FOREIGN KEY (`course_id`) REFERENCES `#__jlms_courses` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
