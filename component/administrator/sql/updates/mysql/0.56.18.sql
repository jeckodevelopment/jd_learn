-- Remove historical SMTP exception text that could contain addresses or provider details.
UPDATE `#__jlms_mail_queue`
SET `last_error` = 'Delivery failed; transport details are withheld.'
WHERE `last_error` IS NOT NULL;
