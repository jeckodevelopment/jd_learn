-- LMS technical baseline 1.0.0. NOT an installable Joomla component.
-- Run through Joomla installer after implementation/review, with #__ prefix substitution.
-- MySQL 8.0.13+ / MariaDB 10.6+, InnoDB, utf8mb4_unicode_ci, UTC.
-- No destructive DROP, no seed Joomla users, no secrets, no JSON defaults.

CREATE TABLE IF NOT EXISTS `#__jlms_courses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `catid` INT NOT NULL,
  `alias` VARCHAR(180) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `language` VARCHAR(7) NOT NULL DEFAULT '*',
  `catalog_access` INT UNSIGNED NOT NULL,
  `content_access` INT UNSIGNED NOT NULL,
  `state` SMALLINT NOT NULL DEFAULT 0,
  `enrollment_mode` VARCHAR(16) NOT NULL DEFAULT 'auto',
  `admissions_open` TINYINT(1) NOT NULL DEFAULT 1,
  `delivery_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `current_version_id` BIGINT UNSIGNED NULL,
  `summary` MEDIUMTEXT NULL,
  `image` VARCHAR(1024) NULL,
  `metadata_json` LONGTEXT NULL,
  `params_json` LONGTEXT NOT NULL,
  `publish_up` DATETIME(6) NULL,
  `publish_down` DATETIME(6) NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME(6) NOT NULL,
  `checked_out` INT UNSIGNED NULL,
  `checked_out_time` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_alias` (`catid`,`language`,`alias`),
  KEY `ix_catalog` (`state`,`language`,`catid`,`catalog_access`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_course_versions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` BIGINT UNSIGNED NOT NULL,
  `version_no` INT UNSIGNED NOT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'draft',
  `title_snapshot` VARCHAR(255) NOT NULL,
  `rules_json` LONGTEXT NOT NULL,
  `presentation_json` LONGTEXT NOT NULL,
  `fields_snapshot_json` LONGTEXT NOT NULL,
  `structure_hash` CHAR(64) NULL,
  `published_at` DATETIME(6) NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME(6) NOT NULL,
  `checked_out` INT UNSIGNED NULL,
  `checked_out_time` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_course_version` (`course_id`,`version_no`),
  UNIQUE KEY `uq_version_owner` (`id`,`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_modules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_version_id` BIGINT UNSIGNED NOT NULL,
  `logical_key` CHAR(36) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `ordering` INT UNSIGNED NOT NULL,
  `params_json` LONGTEXT NOT NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_module_key` (`course_version_id`,`logical_key`),
  UNIQUE KEY `uq_module_owner` (`id`,`course_version_id`),
  KEY `ix_order` (`course_version_id`,`ordering`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_lessons` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_version_id` BIGINT UNSIGNED NOT NULL,
  `module_id` BIGINT UNSIGNED NOT NULL,
  `logical_key` CHAR(36) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(180) NOT NULL,
  `kind` VARCHAR(16) NOT NULL,
  `body` MEDIUMTEXT NULL,
  `ordering` INT UNSIGNED NOT NULL,
  `required` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `preview` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `completion_rule` VARCHAR(24) NOT NULL,
  `duration_ms` BIGINT UNSIGNED NULL,
  `provider_key` VARCHAR(64) NULL,
  `source_revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `source_json` LONGTEXT NULL,
  `media_asset_id` BIGINT UNSIGNED NULL,
  `params_json` LONGTEXT NOT NULL,
  `fields_snapshot_json` LONGTEXT NOT NULL,
  `publish_up` DATETIME(6) NULL,
  `publish_down` DATETIME(6) NULL,
  `release_after_days` INT UNSIGNED NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME(6) NOT NULL,
  `checked_out` INT UNSIGNED NULL,
  `checked_out_time` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lesson_key` (`course_version_id`,`logical_key`),
  UNIQUE KEY `uq_lesson_owner` (`id`,`course_version_id`),
  KEY `ix_module_order` (`module_id`,`ordering`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_prerequisites` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_version_id` BIGINT UNSIGNED NOT NULL,
  `lesson_id` BIGINT UNSIGNED NOT NULL,
  `required_lesson_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_edge` (`lesson_id`,`required_lesson_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_editions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` BIGINT UNSIGNED NOT NULL,
  `course_version_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `code` VARCHAR(64) NOT NULL,
  `starts_at` DATETIME(6) NULL,
  `ends_at` DATETIME(6) NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'open',
  `capacity` INT UNSIGNED NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_edition_code` (`course_id`,`code`),
  UNIQUE KEY `uq_edition_owner` (`id`,`course_id`,`course_version_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_teachers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `catid` INT NOT NULL,
  `asset_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `title` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(180) NOT NULL,
  `bio` MEDIUMTEXT NULL,
  `image` VARCHAR(1024) NULL,
  `language` VARCHAR(7) NOT NULL DEFAULT '*',
  `access` INT UNSIGNED NOT NULL,
  `state` SMALLINT NOT NULL DEFAULT 0,
  `params_json` LONGTEXT NOT NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME(6) NOT NULL,
  `checked_out` INT UNSIGNED NULL,
  `checked_out_time` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_teacher_alias` (`catid`,`language`,`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_course_teachers` (
  `course_id` BIGINT UNSIGNED NOT NULL,
  `teacher_id` BIGINT UNSIGNED NOT NULL,
  `ordering` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`course_id`,`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_pathways` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `catid` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `alias` VARCHAR(180) NOT NULL,
  `description` MEDIUMTEXT NULL,
  `language` VARCHAR(7) NOT NULL DEFAULT '*',
  `access` INT UNSIGNED NOT NULL,
  `state` SMALLINT NOT NULL DEFAULT 0,
  `params_json` LONGTEXT NOT NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME(6) NOT NULL,
  `checked_out` INT UNSIGNED NULL,
  `checked_out_time` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_path_alias` (`catid`,`language`,`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_pathway_courses` (
  `pathway_id` BIGINT UNSIGNED NOT NULL,
  `course_id` BIGINT UNSIGNED NOT NULL,
  `ordering` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`pathway_id`,`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_assets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `storage` VARCHAR(32) NOT NULL,
  `storage_key` VARCHAR(255) NOT NULL,
  `sha256` CHAR(64) NOT NULL,
  `byte_size` BIGINT UNSIGNED NOT NULL,
  `mime_type` VARCHAR(127) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `asset_revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `status` VARCHAR(16) NOT NULL DEFAULT 'quarantine',
  `scan_json` LONGTEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_storage_key` (`storage`,`storage_key`),
  KEY `ix_asset_hash` (`sha256`),
  KEY `ix_asset_state` (`status`,`created_at`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_attachments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_version_id` BIGINT UNSIGNED NOT NULL,
  `lesson_id` BIGINT UNSIGNED NULL,
  `asset_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `ordering` INT UNSIGNED NOT NULL,
  `state` SMALLINT NOT NULL DEFAULT 0,
  `preview` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `publish_up` DATETIME(6) NULL,
  `publish_down` DATETIME(6) NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_scope` (`course_version_id`,`lesson_id`,`state`,`ordering`,`id`),
  KEY `ix_asset_ref` (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_quizzes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lesson_id` BIGINT UNSIGNED NOT NULL,
  `course_version_id` BIGINT UNSIGNED NOT NULL,
  `pass_basis_points` INT UNSIGNED NOT NULL DEFAULT 7000,
  `max_attempts` INT UNSIGNED NOT NULL DEFAULT 3,
  `time_limit_seconds` INT UNSIGNED NULL,
  `feedback_policy` VARCHAR(24) NOT NULL DEFAULT 'pass_or_exhausted',
  `selection_json` LONGTEXT NOT NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lesson_quiz` (`lesson_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_questions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `catid` INT NOT NULL,
  `language` VARCHAR(7) NOT NULL DEFAULT '*',
  `current_revision_id` BIGINT UNSIGNED NULL,
  `state` SMALLINT NOT NULL DEFAULT 0,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME(6) NOT NULL,
  `checked_out` INT UNSIGNED NULL,
  `checked_out_time` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `ix_bank` (`catid`,`state`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_question_revisions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` BIGINT UNSIGNED NOT NULL,
  `version_no` INT UNSIGNED NOT NULL,
  `kind` VARCHAR(16) NOT NULL,
  `prompt` MEDIUMTEXT NOT NULL,
  `options_json` LONGTEXT NOT NULL,
  `grading_json` LONGTEXT NOT NULL,
  `feedback_json` LONGTEXT NOT NULL,
  `content_hash` CHAR(64) NOT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_question_version` (`question_id`,`version_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_quiz_questions` (
  `quiz_id` BIGINT UNSIGNED NOT NULL,
  `question_revision_id` BIGINT UNSIGNED NOT NULL,
  `ordering` INT UNSIGNED NOT NULL,
  `points` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`quiz_id`,`question_revision_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_enrollments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `subject_key` CHAR(36) NOT NULL,
  `course_id` BIGINT UNSIGNED NOT NULL,
  `course_version_id` BIGINT UNSIGNED NOT NULL,
  `edition_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'active',
  `origin` VARCHAR(16) NOT NULL,
  `required_total` INT UNSIGNED NOT NULL,
  `completed_total` INT UNSIGNED NOT NULL DEFAULT 0,
  `started_at` DATETIME(6) NULL,
  `completed_at` DATETIME(6) NULL,
  `last_activity_at` DATETIME(6) NULL,
  `last_lesson_id` BIGINT UNSIGNED NULL,
  `starts_at` DATETIME(6) NULL,
  `ends_at` DATETIME(6) NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enrollment` (`subject_key`,`edition_id`),
  UNIQUE KEY `uq_enrollment_version` (`id`,`course_version_id`),
  KEY `ix_dashboard` (`user_id`,`status`,`last_activity_at`,`id`),
  KEY `ix_reports` (`course_id`,`course_version_id`,`status`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_enrollment_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `subject_key` CHAR(36) NOT NULL,
  `edition_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
  `reason` TEXT NULL,
  `decided_by` INT UNSIGNED NULL,
  `decided_at` DATETIME(6) NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_request` (`subject_key`,`edition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_lesson_progress` (
  `enrollment_id` BIGINT UNSIGNED NOT NULL,
  `course_version_id` BIGINT UNSIGNED NOT NULL,
  `lesson_id` BIGINT UNSIGNED NOT NULL,
  `source_revision` INT UNSIGNED NOT NULL,
  `coverage_json` LONGTEXT NOT NULL,
  `covered_ms` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `resume_ms` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `state` VARCHAR(16) NOT NULL DEFAULT 'started',
  `completed_at` DATETIME(6) NULL,
  `last_activity_at` DATETIME(6) NOT NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`enrollment_id`,`lesson_id`),
  KEY `ix_lesson_progress` (`lesson_id`,`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_progress_sessions` (
  `id` CHAR(36) NOT NULL,
  `enrollment_id` BIGINT UNSIGNED NOT NULL,
  `lesson_id` BIGINT UNSIGNED NOT NULL,
  `source_revision` INT UNSIGNED NOT NULL,
  `sequence_no` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_batch_hash` CHAR(64) NULL,
  `anchor_media_ms` BIGINT UNSIGNED NULL,
  `anchor_at` DATETIME(6) NULL,
  `player_state_json` LONGTEXT NOT NULL,
  `expires_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_progress_expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `enrollment_id` BIGINT UNSIGNED NOT NULL,
  `course_version_id` BIGINT UNSIGNED NOT NULL,
  `quiz_id` BIGINT UNSIGNED NOT NULL,
  `attempt_no` INT UNSIGNED NOT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'open',
  `rules_snapshot_json` LONGTEXT NOT NULL,
  `max_points` INT UNSIGNED NOT NULL,
  `score_points` INT UNSIGNED NULL,
  `passed` TINYINT UNSIGNED NULL,
  `started_at` DATETIME(6) NOT NULL,
  `deadline_at` DATETIME(6) NULL,
  `submitted_at` DATETIME(6) NULL,
  `counted_for_limit` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `invalidation_reason` VARCHAR(500) NULL,
  `invalidated_by` INT UNSIGNED NULL,
  `invalidated_at` DATETIME(6) NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attempt_no` (`enrollment_id`,`quiz_id`,`attempt_no`),
  KEY `ix_attempt_deadline` (`status`,`deadline_at`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_active_attempts` (
  `enrollment_id` BIGINT UNSIGNED NOT NULL,
  `quiz_id` BIGINT UNSIGNED NOT NULL,
  `attempt_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`enrollment_id`,`quiz_id`),
  UNIQUE KEY `uq_active_attempt` (`attempt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_attempt_questions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attempt_id` BIGINT UNSIGNED NOT NULL,
  `question_revision_id` BIGINT UNSIGNED NOT NULL,
  `display_order` INT UNSIGNED NOT NULL,
  `public_snapshot_json` LONGTEXT NOT NULL,
  `grading_snapshot_json` LONGTEXT NOT NULL,
  `max_points` INT UNSIGNED NOT NULL,
  `awarded_points` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attempt_question` (`attempt_id`,`question_revision_id`),
  UNIQUE KEY `uq_attempt_order` (`attempt_id`,`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_attempt_answers` (
  `attempt_question_id` BIGINT UNSIGNED NOT NULL,
  `answer_json` LONGTEXT NOT NULL,
  `saved_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`attempt_question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_certificate_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `version_no` INT UNSIGNED NOT NULL,
  `template_json` LONGTEXT NOT NULL,
  `background_asset_id` BIGINT UNSIGNED NULL,
  `hash` CHAR(64) NOT NULL,
  `state` SMALLINT NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `modified_by` INT UNSIGNED NULL,
  `modified_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_certificates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `enrollment_id` BIGINT UNSIGNED NOT NULL,
  `course_version_id` BIGINT UNSIGNED NOT NULL,
  `template_id` BIGINT UNSIGNED NOT NULL,
  `current_document_id` BIGINT UNSIGNED NULL,
  `issued_at` DATETIME(6) NOT NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_certificate` (`enrollment_id`,`course_version_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_certificate_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `certificate_id` BIGINT UNSIGNED NOT NULL,
  `document_no` INT UNSIGNED NOT NULL,
  `replaces_document_id` BIGINT UNSIGNED NULL,
  `serial` VARCHAR(64) NOT NULL,
  `verification_hash` CHAR(64) NULL,
  `verify_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `identity_snapshot_json` LONGTEXT NOT NULL,
  `template_snapshot_json` LONGTEXT NOT NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'pending_render',
  `pdf_asset_id` BIGINT UNSIGNED NULL,
  `issued_at` DATETIME(6) NOT NULL,
  `revoked_at` DATETIME(6) NULL,
  `revocation_reason` VARCHAR(500) NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_document_number` (`certificate_id`,`document_no`),
  UNIQUE KEY `uq_document_owner` (`id`,`certificate_id`),
  UNIQUE KEY `uq_serial` (`serial`),
  UNIQUE KEY `uq_verify_hash` (`verification_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_version_equivalences` (
  `source_lesson_id` BIGINT UNSIGNED NOT NULL,
  `target_lesson_id` BIGINT UNSIGNED NOT NULL,
  `policy_json` LONGTEXT NOT NULL,
  `approved_by` INT UNSIGNED NOT NULL,
  `approved_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`source_lesson_id`,`target_lesson_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_enrollment_migrations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_enrollment_id` BIGINT UNSIGNED NOT NULL,
  `target_enrollment_id` BIGINT UNSIGNED NOT NULL,
  `job_id` BIGINT UNSIGNED NOT NULL,
  `mapping_snapshot_json` LONGTEXT NOT NULL,
  `created_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_migration` (`source_enrollment_id`,`target_enrollment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_idempotency` (
  `scope_hash` CHAR(64) NOT NULL,
  `request_hash` CHAR(64) NOT NULL,
  `state` VARCHAR(16) NOT NULL,
  `result_type` VARCHAR(64) NULL,
  `result_id` BIGINT UNSIGNED NULL,
  `result_json` LONGTEXT NULL,
  `http_status` SMALLINT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `expires_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`scope_hash`),
  KEY `ix_idempotency_expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_quiz_mutations` (
  `attempt_id` BIGINT UNSIGNED NOT NULL,
  `mutation_id` CHAR(36) NOT NULL,
  `request_hash` CHAR(64) NOT NULL,
  `result_revision` INT UNSIGNED NOT NULL,
  `created_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`attempt_id`,`mutation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kind` VARCHAR(64) NOT NULL,
  `owner_user_id` INT UNSIGNED NULL,
  `scope_json` LONGTEXT NOT NULL,
  `payload_json` LONGTEXT NOT NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'queued',
  `cursor_json` LONGTEXT NULL,
  `progress_done` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `progress_total` BIGINT UNSIGNED NULL,
  `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `available_at` DATETIME(6) NOT NULL,
  `lease_token` CHAR(64) NULL,
  `lease_until` DATETIME(6) NULL,
  `result_asset_id` BIGINT UNSIGNED NULL,
  `error_code` VARCHAR(64) NULL,
  `created_at` DATETIME(6) NOT NULL,
  `expires_at` DATETIME(6) NULL,
  `finished_at` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_job_claim` (`status`,`available_at`,`id`),
  KEY `ix_job_lease` (`status`,`lease_until`),
  KEY `ix_job_owner` (`owner_user_id`,`created_at`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_outbox` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_key` CHAR(64) NOT NULL,
  `event_type` VARCHAR(64) NOT NULL,
  `aggregate_type` VARCHAR(32) NOT NULL,
  `aggregate_id` BIGINT UNSIGNED NOT NULL,
  `payload_json` LONGTEXT NOT NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'pending',
  `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `available_at` DATETIME(6) NOT NULL,
  `lease_token` CHAR(64) NULL,
  `lease_until` DATETIME(6) NULL,
  `created_at` DATETIME(6) NOT NULL,
  `processed_at` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event` (`event_key`),
  KEY `ix_dispatch` (`status`,`available_at`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_delivery_receipts` (
  `event_key` CHAR(64) NOT NULL,
  `channel` VARCHAR(32) NOT NULL,
  `status` VARCHAR(24) NOT NULL,
  `provider_reference` VARCHAR(255) NULL,
  `last_attempt_at` DATETIME(6) NOT NULL,
  `error_code` VARCHAR(64) NULL,
  PRIMARY KEY (`event_key`,`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_anomalies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kind` VARCHAR(64) NOT NULL,
  `enrollment_id` BIGINT UNSIGNED NULL,
  `certificate_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'open',
  `details_json` LONGTEXT NOT NULL,
  `resolved_by` INT UNSIGNED NULL,
  `resolved_at` DATETIME(6) NULL,
  `created_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_anomaly` (`status`,`created_at`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jlms_assessment_revisions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attempt_id` BIGINT UNSIGNED NOT NULL,
  `revision_no` INT UNSIGNED NOT NULL,
  `job_id` BIGINT UNSIGNED NOT NULL,
  `actor_user_id` INT UNSIGNED NULL,
  `reason` VARCHAR(500) NOT NULL,
  `rules_snapshot_json` LONGTEXT NOT NULL,
  `previous_score_points` INT UNSIGNED NULL,
  `previous_passed` TINYINT UNSIGNED NULL,
  `result_score_points` INT UNSIGNED NOT NULL,
  `result_passed` TINYINT UNSIGNED NOT NULL,
  `created_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assessment_revision` (`attempt_id`,`revision_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Execute each ALTER exactly once; an installer recovery must check information_schema first.
ALTER TABLE `#__jlms_course_versions` ADD CONSTRAINT `#__jlms_fk_001` FOREIGN KEY (`course_id`) REFERENCES `#__jlms_courses` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_courses` ADD CONSTRAINT `#__jlms_fk_002` FOREIGN KEY (`current_version_id`, `id`) REFERENCES `#__jlms_course_versions` (`id`, `course_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_modules` ADD CONSTRAINT `#__jlms_fk_003` FOREIGN KEY (`course_version_id`) REFERENCES `#__jlms_course_versions` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_lessons` ADD CONSTRAINT `#__jlms_fk_004` FOREIGN KEY (`module_id`, `course_version_id`) REFERENCES `#__jlms_modules` (`id`, `course_version_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_lessons` ADD CONSTRAINT `#__jlms_fk_005` FOREIGN KEY (`media_asset_id`) REFERENCES `#__jlms_assets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_prerequisites` ADD CONSTRAINT `#__jlms_fk_006` FOREIGN KEY (`lesson_id`, `course_version_id`) REFERENCES `#__jlms_lessons` (`id`, `course_version_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_prerequisites` ADD CONSTRAINT `#__jlms_fk_007` FOREIGN KEY (`required_lesson_id`, `course_version_id`) REFERENCES `#__jlms_lessons` (`id`, `course_version_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_editions` ADD CONSTRAINT `#__jlms_fk_008` FOREIGN KEY (`course_version_id`, `course_id`) REFERENCES `#__jlms_course_versions` (`id`, `course_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_course_teachers` ADD CONSTRAINT `#__jlms_fk_009` FOREIGN KEY (`course_id`) REFERENCES `#__jlms_courses` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_course_teachers` ADD CONSTRAINT `#__jlms_fk_010` FOREIGN KEY (`teacher_id`) REFERENCES `#__jlms_teachers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_pathway_courses` ADD CONSTRAINT `#__jlms_fk_011` FOREIGN KEY (`pathway_id`) REFERENCES `#__jlms_pathways` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_pathway_courses` ADD CONSTRAINT `#__jlms_fk_012` FOREIGN KEY (`course_id`) REFERENCES `#__jlms_courses` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_attachments` ADD CONSTRAINT `#__jlms_fk_013` FOREIGN KEY (`course_version_id`) REFERENCES `#__jlms_course_versions` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_attachments` ADD CONSTRAINT `#__jlms_fk_014` FOREIGN KEY (`lesson_id`, `course_version_id`) REFERENCES `#__jlms_lessons` (`id`, `course_version_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_attachments` ADD CONSTRAINT `#__jlms_fk_015` FOREIGN KEY (`asset_id`) REFERENCES `#__jlms_assets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_quizzes` ADD CONSTRAINT `#__jlms_fk_016` FOREIGN KEY (`lesson_id`, `course_version_id`) REFERENCES `#__jlms_lessons` (`id`, `course_version_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_question_revisions` ADD CONSTRAINT `#__jlms_fk_017` FOREIGN KEY (`question_id`) REFERENCES `#__jlms_questions` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_questions` ADD CONSTRAINT `#__jlms_fk_018` FOREIGN KEY (`current_revision_id`) REFERENCES `#__jlms_question_revisions` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_quiz_questions` ADD CONSTRAINT `#__jlms_fk_019` FOREIGN KEY (`quiz_id`) REFERENCES `#__jlms_quizzes` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_quiz_questions` ADD CONSTRAINT `#__jlms_fk_020` FOREIGN KEY (`question_revision_id`) REFERENCES `#__jlms_question_revisions` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_enrollments` ADD CONSTRAINT `#__jlms_fk_021` FOREIGN KEY (`edition_id`, `course_id`, `course_version_id`) REFERENCES `#__jlms_editions` (`id`, `course_id`, `course_version_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_enrollment_requests` ADD CONSTRAINT `#__jlms_fk_022` FOREIGN KEY (`edition_id`) REFERENCES `#__jlms_editions` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_lesson_progress` ADD CONSTRAINT `#__jlms_fk_023` FOREIGN KEY (`enrollment_id`, `course_version_id`) REFERENCES `#__jlms_enrollments` (`id`, `course_version_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_lesson_progress` ADD CONSTRAINT `#__jlms_fk_024` FOREIGN KEY (`lesson_id`, `course_version_id`) REFERENCES `#__jlms_lessons` (`id`, `course_version_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_progress_sessions` ADD CONSTRAINT `#__jlms_fk_025` FOREIGN KEY (`enrollment_id`, `lesson_id`) REFERENCES `#__jlms_lesson_progress` (`enrollment_id`, `lesson_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_attempts` ADD CONSTRAINT `#__jlms_fk_026` FOREIGN KEY (`enrollment_id`, `course_version_id`) REFERENCES `#__jlms_enrollments` (`id`, `course_version_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_attempts` ADD CONSTRAINT `#__jlms_fk_027` FOREIGN KEY (`quiz_id`) REFERENCES `#__jlms_quizzes` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_active_attempts` ADD CONSTRAINT `#__jlms_fk_028` FOREIGN KEY (`attempt_id`) REFERENCES `#__jlms_attempts` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_active_attempts` ADD CONSTRAINT `#__jlms_fk_029` FOREIGN KEY (`enrollment_id`) REFERENCES `#__jlms_enrollments` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_active_attempts` ADD CONSTRAINT `#__jlms_fk_030` FOREIGN KEY (`quiz_id`) REFERENCES `#__jlms_quizzes` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_attempt_questions` ADD CONSTRAINT `#__jlms_fk_031` FOREIGN KEY (`attempt_id`) REFERENCES `#__jlms_attempts` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_attempt_questions` ADD CONSTRAINT `#__jlms_fk_032` FOREIGN KEY (`question_revision_id`) REFERENCES `#__jlms_question_revisions` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_attempt_answers` ADD CONSTRAINT `#__jlms_fk_033` FOREIGN KEY (`attempt_question_id`) REFERENCES `#__jlms_attempt_questions` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_certificate_templates` ADD CONSTRAINT `#__jlms_fk_034` FOREIGN KEY (`background_asset_id`) REFERENCES `#__jlms_assets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_certificates` ADD CONSTRAINT `#__jlms_fk_035` FOREIGN KEY (`enrollment_id`, `course_version_id`) REFERENCES `#__jlms_enrollments` (`id`, `course_version_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_certificates` ADD CONSTRAINT `#__jlms_fk_036` FOREIGN KEY (`template_id`) REFERENCES `#__jlms_certificate_templates` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_certificate_documents` ADD CONSTRAINT `#__jlms_fk_037` FOREIGN KEY (`pdf_asset_id`) REFERENCES `#__jlms_assets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_certificate_documents` ADD CONSTRAINT `#__jlms_fk_038` FOREIGN KEY (`certificate_id`) REFERENCES `#__jlms_certificates` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_certificate_documents` ADD CONSTRAINT `#__jlms_fk_039` FOREIGN KEY (`replaces_document_id`) REFERENCES `#__jlms_certificate_documents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_certificates` ADD CONSTRAINT `#__jlms_fk_040` FOREIGN KEY (`current_document_id`, `id`) REFERENCES `#__jlms_certificate_documents` (`id`, `certificate_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_version_equivalences` ADD CONSTRAINT `#__jlms_fk_041` FOREIGN KEY (`source_lesson_id`) REFERENCES `#__jlms_lessons` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_version_equivalences` ADD CONSTRAINT `#__jlms_fk_042` FOREIGN KEY (`target_lesson_id`) REFERENCES `#__jlms_lessons` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_enrollment_migrations` ADD CONSTRAINT `#__jlms_fk_043` FOREIGN KEY (`source_enrollment_id`) REFERENCES `#__jlms_enrollments` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_enrollment_migrations` ADD CONSTRAINT `#__jlms_fk_044` FOREIGN KEY (`target_enrollment_id`) REFERENCES `#__jlms_enrollments` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_enrollment_migrations` ADD CONSTRAINT `#__jlms_fk_045` FOREIGN KEY (`job_id`) REFERENCES `#__jlms_jobs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_quiz_mutations` ADD CONSTRAINT `#__jlms_fk_046` FOREIGN KEY (`attempt_id`) REFERENCES `#__jlms_attempts` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_jobs` ADD CONSTRAINT `#__jlms_fk_047` FOREIGN KEY (`result_asset_id`) REFERENCES `#__jlms_assets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_anomalies` ADD CONSTRAINT `#__jlms_fk_048` FOREIGN KEY (`enrollment_id`) REFERENCES `#__jlms_enrollments` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_anomalies` ADD CONSTRAINT `#__jlms_fk_049` FOREIGN KEY (`certificate_id`) REFERENCES `#__jlms_certificates` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_assessment_revisions` ADD CONSTRAINT `#__jlms_fk_050` FOREIGN KEY (`attempt_id`) REFERENCES `#__jlms_attempts` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_assessment_revisions` ADD CONSTRAINT `#__jlms_fk_051` FOREIGN KEY (`job_id`) REFERENCES `#__jlms_jobs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

CREATE TABLE IF NOT EXISTS `#__jlms_subjects` (
  `subject_key` CHAR(36) NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL,
  `anonymised_at` DATETIME(6) NULL,
  PRIMARY KEY (`subject_key`),
  UNIQUE KEY `uq_subject_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jlms_enrollments` ADD CONSTRAINT `#__jlms_fk_subject_enrollment` FOREIGN KEY (`subject_key`) REFERENCES `#__jlms_subjects` (`subject_key`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_enrollment_requests` ADD CONSTRAINT `#__jlms_fk_subject_request` FOREIGN KEY (`subject_key`) REFERENCES `#__jlms_subjects` (`subject_key`) ON DELETE RESTRICT ON UPDATE RESTRICT;

CREATE TABLE IF NOT EXISTS `#__jlms_quiz_time_adjustments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `enrollment_id` BIGINT UNSIGNED NOT NULL,
  `course_version_id` BIGINT UNSIGNED NOT NULL,
  `quiz_id` BIGINT UNSIGNED NOT NULL,
  `revision_no` INT UNSIGNED NOT NULL,
  `extra_seconds` INT UNSIGNED NOT NULL,
  `reason` VARCHAR(500) NOT NULL,
  `actor_user_id` INT UNSIGNED NULL,
  `mutation_id` CHAR(36) NOT NULL,
  `request_hash` CHAR(64) NOT NULL,
  `created_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_quiz_time_revision` (`enrollment_id`,`quiz_id`,`revision_no`),
  UNIQUE KEY `uq_quiz_time_mutation` (`enrollment_id`,`quiz_id`,`mutation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jlms_quiz_time_adjustments` ADD CONSTRAINT `#__jlms_fk_time_enrollment` FOREIGN KEY (`enrollment_id`, `course_version_id`) REFERENCES `#__jlms_enrollments` (`id`, `course_version_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_quiz_time_adjustments` ADD CONSTRAINT `#__jlms_fk_time_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `#__jlms_quizzes` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

CREATE TABLE IF NOT EXISTS `#__jlms_quiz_deadline_extensions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attempt_id` BIGINT UNSIGNED NOT NULL,
  `revision_no` INT UNSIGNED NOT NULL,
  `previous_deadline` DATETIME(6) NOT NULL,
  `new_deadline` DATETIME(6) NOT NULL,
  `added_seconds` INT UNSIGNED NOT NULL,
  `rules_hash` CHAR(64) NOT NULL,
  `reason` VARCHAR(500) NOT NULL,
  `actor_user_id` INT UNSIGNED NULL,
  `mutation_id` CHAR(36) NOT NULL,
  `request_hash` CHAR(64) NOT NULL,
  `created_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_deadline_revision` (`attempt_id`,`revision_no`),
  UNIQUE KEY `uq_deadline_mutation` (`attempt_id`,`mutation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jlms_quiz_deadline_extensions` ADD CONSTRAINT `#__jlms_fk_deadline_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `#__jlms_attempts` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

CREATE TABLE IF NOT EXISTS `#__jlms_enrollment_state_changes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `enrollment_id` BIGINT UNSIGNED NOT NULL,
  `course_version_id` BIGINT UNSIGNED NOT NULL,
  `revision_no` INT UNSIGNED NOT NULL,
  `enrollment_revision` INT UNSIGNED NOT NULL,
  `previous_status` VARCHAR(16) NOT NULL,
  `target_status` VARCHAR(16) NOT NULL,
  `impact_hash` CHAR(64) NOT NULL,
  `reason` VARCHAR(500) NOT NULL,
  `actor_user_id` INT UNSIGNED NULL,
  `mutation_id` CHAR(36) NOT NULL,
  `request_hash` CHAR(64) NOT NULL,
  `created_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enrollment_state_revision` (`enrollment_id`,`revision_no`),
  UNIQUE KEY `uq_enrollment_state_mutation` (`enrollment_id`,`mutation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jlms_enrollment_state_changes` ADD CONSTRAINT `#__jlms_fk_state_enrollment` FOREIGN KEY (`enrollment_id`, `course_version_id`) REFERENCES `#__jlms_enrollments` (`id`, `course_version_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

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

CREATE TABLE IF NOT EXISTS `#__jlms_manual_enrollments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `enrollment_id` BIGINT UNSIGNED NULL,
  `course_version_id` BIGINT UNSIGNED NULL,
  `actor_user_id` INT UNSIGNED NULL,
  `mutation_id` CHAR(36) NOT NULL,
  `request_hash` CHAR(64) NOT NULL,
  `claim_token` CHAR(32) NOT NULL,
  `impact_hash` CHAR(64) NOT NULL,
  `reason` VARCHAR(500) NOT NULL,
  `created_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_manual_mutation` (`mutation_id`),
  UNIQUE KEY `uq_manual_enrollment` (`enrollment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jlms_manual_enrollments` ADD CONSTRAINT `#__jlms_fk_manual_enrollment` FOREIGN KEY (`enrollment_id`, `course_version_id`) REFERENCES `#__jlms_enrollments` (`id`, `course_version_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

-- Native Joomla categories may be removed only after every JD Learn reference is moved.
ALTER TABLE `#__jlms_courses` ADD CONSTRAINT `#__jlms_fk_course_category` FOREIGN KEY (`catid`) REFERENCES `#__categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_pathways` ADD CONSTRAINT `#__jlms_fk_pathway_category` FOREIGN KEY (`catid`) REFERENCES `#__categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_teachers` ADD CONSTRAINT `#__jlms_fk_teacher_category` FOREIGN KEY (`catid`) REFERENCES `#__categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_questions` ADD CONSTRAINT `#__jlms_fk_question_category` FOREIGN KEY (`catid`) REFERENCES `#__categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

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
