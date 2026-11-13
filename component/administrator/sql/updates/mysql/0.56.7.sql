CREATE TABLE IF NOT EXISTS `#__jlms_certificate_audit` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `certificate_id` BIGINT UNSIGNED NULL,
  `document_id` BIGINT UNSIGNED NULL,
  `actor_user_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(32) NOT NULL,
  `context_json` LONGTEXT NOT NULL,
  `created_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_certificate_audit_target` (`certificate_id`,`created_at`),
  KEY `ix_certificate_audit_actor` (`actor_user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jlms_certificate_audit` ADD CONSTRAINT `#__jlms_fk_certificate_audit_certificate` FOREIGN KEY (`certificate_id`) REFERENCES `#__jlms_certificates` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_certificate_audit` ADD CONSTRAINT `#__jlms_fk_certificate_audit_document` FOREIGN KEY (`document_id`) REFERENCES `#__jlms_certificate_documents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
