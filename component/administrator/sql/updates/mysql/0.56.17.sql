ALTER TABLE `#__jlms_mail_queue`
  ADD COLUMN `reconciliation_action` VARCHAR(20) NULL AFTER `recipient_consent_at`,
  ADD COLUMN `reconciliation_by` INT UNSIGNED NULL AFTER `reconciliation_action`,
  ADD COLUMN `reconciliation_at` DATETIME(6) NULL AFTER `reconciliation_by`,
  ADD COLUMN `reconciliation_note` VARCHAR(1000) NULL AFTER `reconciliation_at`;
