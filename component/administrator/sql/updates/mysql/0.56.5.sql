-- Seed the immutable default template used by the certificate issuance foundation.
INSERT INTO `#__jlms_certificate_templates`
  (`title`,`version_no`,`template_json`,`background_asset_id`,`hash`,`state`,`created_by`,`created_at`,`modified_by`,`modified_at`)
SELECT 'JD Learn — Attestato standard',1,
  '{"schema":1,"title":"Attestato di completamento","subtitle":"ha completato il corso","locale":"it-IT"}',
  NULL,
  SHA2('{"schema":1,"title":"Attestato di completamento","subtitle":"ha completato il corso","locale":"it-IT"}',256),
  1,NULL,UTC_TIMESTAMP(6),NULL,UTC_TIMESTAMP(6)
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `#__jlms_certificate_templates` WHERE `state`=1);
