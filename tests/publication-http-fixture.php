<?php

declare(strict_types=1);

// Read-only evidence and reversible plugin configuration on the disposable CI site only.
if (getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new RuntimeException('Disposable Joomla site required.');
}
$db = new PDO(getenv('JLMS_TEST_DSN'), getenv('JLMS_TEST_USER'), getenv('JLMS_TEST_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$operation = $argv[1] ?? '';
if ($operation === 'material-add') {
    $version = $db->query("SELECT v.id AS version_id, c.created_by FROM joomla_jlms_courses c JOIN joomla_jlms_course_versions v ON v.course_id=c.id WHERE c.alias='http-first-publication' AND v.status='draft' ORDER BY v.version_no DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$version) { throw new RuntimeException('HTTP publication draft missing for material readiness fixture'); }
    $q = $db->prepare('SELECT id FROM joomla_jlms_lessons WHERE course_version_id=? ORDER BY id DESC LIMIT 1');
    $q->execute([(int) $version['version_id']]); $lessonId = (int) $q->fetchColumn();
    if (!$lessonId) { throw new RuntimeException('HTTP publication lesson missing for material readiness fixture'); }
    $now = gmdate('Y-m-d H:i:s.u'); $key = 'readiness-fixture-' . bin2hex(random_bytes(12));
    $q = $db->prepare("INSERT INTO joomla_jlms_assets (storage,storage_key,sha256,byte_size,mime_type,original_name,asset_revision,status,scan_json,created_by,created_at,modified_by,modified_at) VALUES ('local',?,?,1,'application/pdf','readiness-fixture.pdf',1,'quarantine','{}',?,?,?,?)");
    $q->execute([$key, hash('sha256', $key), (int) $version['created_by'], $now, (int) $version['created_by'], $now]);
    $assetId = (int) $db->lastInsertId();
    $q = $db->prepare("INSERT INTO joomla_jlms_attachments (course_version_id,lesson_id,asset_id,title,description,ordering,state,preview,revision,created_by,created_at,modified_by,modified_at) VALUES (?,?,?,'Readiness test material','',0,1,0,1,?,?,?,?)");
    $q->execute([(int) $version['version_id'], $lessonId, $assetId, (int) $version['created_by'], $now, (int) $version['created_by'], $now]);
    echo json_encode(['attachment_id' => (int) $db->lastInsertId(), 'asset_id' => $assetId], JSON_THROW_ON_ERROR) . "\n"; exit;
} elseif ($operation === 'material-remove') {
    $attachmentId = (int) ($argv[2] ?? 0);
    $q = $db->prepare("SELECT a.asset_id FROM joomla_jlms_attachments a JOIN joomla_jlms_course_versions v ON v.id=a.course_version_id JOIN joomla_jlms_courses c ON c.id=v.course_id WHERE a.id=? AND c.alias='http-first-publication' AND v.status='draft'");
    $q->execute([$attachmentId]); $assetId = (int) $q->fetchColumn();
    if (!$assetId) { throw new RuntimeException('Readiness fixture material not found'); }
    $db->beginTransaction();
    try { $q=$db->prepare('DELETE FROM joomla_jlms_attachments WHERE id=?');$q->execute([$attachmentId]);$q=$db->prepare('DELETE FROM joomla_jlms_assets WHERE id=?');$q->execute([$assetId]);$db->commit(); }
    catch (Throwable $error) { $db->rollBack(); throw $error; }
    echo json_encode(['removed' => true], JSON_THROW_ON_ERROR) . "\n"; exit;
} elseif ($operation === 'plugin') {
    if (!in_array($argv[2] ?? '', ['0', '1'], true)) { throw new InvalidArgumentException('Invalid state'); }
    $q = $db->prepare("UPDATE joomla_extensions SET enabled=? WHERE type='plugin' AND folder='workflow' AND element='publishing'");
    $q->execute([(int) $argv[2]]);
} elseif ($operation !== 'snapshot') {
    throw new InvalidArgumentException('Unknown fixture operation');
}
$plugin = $db->query("SELECT enabled FROM joomla_extensions WHERE type='plugin' AND folder='workflow' AND element='publishing'")->fetchColumn();
$q = $db->query("SELECT c.id,c.state,c.current_version_id,c.revision,v.status,v.structure_hash,v.published_at,c.publish_up,c.publish_down,
    JSON_UNQUOTE(JSON_EXTRACT(v.presentation_json,'$.calendar.timezone')) AS calendar_timezone,
    JSON_UNQUOTE(JSON_EXTRACT(v.presentation_json,'$.calendar.starts_at')) AS calendar_start,
    (SELECT ends_at FROM joomla_jlms_editions e WHERE e.course_id=c.id LIMIT 1) AS edition_end,
    (SELECT COUNT(*) FROM joomla_jlms_editions e WHERE e.course_id=c.id) AS editions,
    (SELECT COUNT(*) FROM joomla_workflow_associations a WHERE a.item_id=c.id AND a.extension='com_jlms.course') AS associations
    FROM joomla_jlms_courses c LEFT JOIN joomla_jlms_course_versions v ON v.id=c.current_version_id
    WHERE c.alias='http-first-publication'");
echo json_encode(['plugin' => (int) $plugin, 'course' => $q->fetch(PDO::FETCH_ASSOC) ?: null], JSON_THROW_ON_ERROR) . "\n";
