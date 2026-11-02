<?php

declare(strict_types=1);

// Read-only evidence and reversible plugin configuration on the disposable CI site only.
if (getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new RuntimeException('Disposable Joomla site required.');
}
$db = new PDO(getenv('JLMS_TEST_DSN'), getenv('JLMS_TEST_USER'), getenv('JLMS_TEST_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$operation = $argv[1] ?? '';
if ($operation === 'plugin') {
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
