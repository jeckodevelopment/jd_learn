<?php
declare(strict_types=1);

require __DIR__ . '/../component/administrator/src/Infrastructure/SchedulerHealth.php';
require __DIR__ . '/../component/administrator/src/Application/DashboardProviderStatus.php';
require __DIR__ . '/../component/administrator/src/Application/ConfigurationDiagnostics.php';

use Jlms\Component\Jlms\Administrator\Application\ConfigurationDiagnostics;

$states = ['missing', 'invalid_rules', 'disabled', 'duplicates', 'failed', 'overdue', 'waiting', 'operational', 'unknown'];
$scheduler = [];
foreach ($states as $state) {
    $scheduler[] = ['label' => 'COM_JLMS_SCHEDULER_MAIL', 'state' => $state, 'duplicates' => false];
}
$scheduler[] = ['label' => 'COM_JLMS_SCHEDULER_MAIL', 'state' => 'operational', 'duplicates' => true];
$providerStates = [
    'COM_JLMS_PROVIDER_NOT_INSTALLED', 'COM_JLMS_PROVIDER_DISABLED',
    'COM_JLMS_PROVIDER_UNAVAILABLE', 'COM_JLMS_NOT_CONFIGURED',
    'COM_JLMS_READY', 'unexpected',
];
$providers = array_map(
    static fn (string $state): array => ['labelKey' => 'COM_JLMS_PROVIDER_YOUTUBE', 'stateKey' => $state],
    $providerStates
);
$result = ConfigurationDiagnostics::summarize($scheduler, $providers);
if (count($result) !== count($scheduler) + count($providers)) {
    throw new RuntimeException('Configuration checks lost or added an unrecognized check');
}
foreach ($result as $check) {
    if (!in_array($check['severity'], ['ok', 'warning', 'error'], true)
        || !preg_match('/^COM_JLMS_[A-Z0-9_]+$/', $check['labelKey'])
        || !preg_match('/^COM_JLMS_[A-Z0-9_]+$/', $check['stateKey'])
        || !preg_match('/^COM_JLMS_[A-Z0-9_]+$/', $check['guidanceKey'])) {
        throw new RuntimeException('A configuration diagnostic contains unsafe raw data');
    }
    if (array_diff(array_keys($check), ['kind', 'labelKey', 'severity', 'guidanceKey', 'stateKey'])) {
        throw new RuntimeException('A configuration diagnostic exposed unexpected fields');
    }
}
if ($result[3]['severity'] !== 'error' || $result[9]['severity'] !== 'error'
    || $result[7]['severity'] !== 'ok'
    || $result[count($scheduler) + 2]['severity'] !== 'error'
    || $result[count($scheduler) + 4]['severity'] !== 'ok'
    || $result[count($scheduler) + 5]['severity'] !== 'warning') {
    throw new RuntimeException('Unsafe severity classification or duplicate precedence');
}
if ($result[9]['guidanceKey'] !== 'COM_JLMS_SCHEDULER_GUIDANCE_DUPLICATES') {
    throw new RuntimeException('Duplicate scheduler task did not override healthy status');
}
$untrusted = ConfigurationDiagnostics::summarize([], [['labelKey' => '<script>secret</script>', 'stateKey' => 'unexpected']]);
if ($untrusted[0]['labelKey'] !== 'COM_JLMS_CONFIG_DIAGNOSTICS_UNKNOWN_LABEL'
    || $untrusted[0]['stateKey'] !== 'COM_JLMS_PROVIDER_GUIDANCE_UNKNOWN') {
    throw new RuntimeException('Unknown provider labels or states must fail closed');
}
$tmpBase = sys_get_temp_dir() . '/jdlearn-scr16-' . bin2hex(random_bytes(5));
if (!mkdir($tmpBase, 0700) || !mkdir($tmpBase . '/public', 0700) || !mkdir($tmpBase . '/private', 0700)) {
    throw new RuntimeException('Could not prepare disposable storage diagnostic fixture');
}
try {
    $private = ConfigurationDiagnostics::storage($tmpBase . '/private', $tmpBase . '/public', $tmpBase . '/public');
    $public = ConfigurationDiagnostics::storage($tmpBase . '/public', $tmpBase . '/public', $tmpBase . '/public');
    $missing = ConfigurationDiagnostics::storage($tmpBase . '/missing', $tmpBase . '/public', $tmpBase . '/public');
    $managed = ConfigurationDiagnostics::storage('', $tmpBase . '/public', $tmpBase . '/public');
    foreach ([$private, $public, $missing, $managed] as $result) {
        if (array_keys($result) !== ['kind', 'labelKey', 'severity', 'stateKey', 'guidanceKey']
            || !in_array($result['severity'], ['ok', 'warning', 'error'], true)
            || str_contains(json_encode($result, JSON_THROW_ON_ERROR), $tmpBase)) {
            throw new RuntimeException('SCR-16 storage diagnostic leaks paths or unexpected fields');
        }
    }
    if ($private['severity'] !== 'ok' || $public['severity'] !== 'error'
        || $missing['severity'] !== 'error' || $managed['severity'] !== 'warning') {
        throw new RuntimeException('SCR-16 private/public/unavailable/managed classification failed');
    }
} finally {
    rmdir($tmpBase . '/private');
    rmdir($tmpBase . '/public');
    rmdir($tmpBase);
}
$requiredTemplates = ['com_jlms.course.completed', 'com_jlms.enrollment.confirmed'];
$registeredTemplates = [
    ['template_id' => 'com_jlms.course.completed', 'language' => '*'],
    ['template_id' => 'com_jlms.enrollment.confirmed', 'language' => '*'],
    ['template_id' => 'com_jlms.course.completed', 'language' => 'it-IT'],
];
$readyTemplates = ConfigurationDiagnostics::mailTemplates($requiredTemplates, $registeredTemplates);
$missingTemplates = ConfigurationDiagnostics::mailTemplates($requiredTemplates, [$registeredTemplates[0]]);
$translatedOnly = ConfigurationDiagnostics::mailTemplates($requiredTemplates, [
    ['template_id' => 'com_jlms.course.completed', 'language' => 'it-IT'],
    ['template_id' => 'com_jlms.enrollment.confirmed', 'language' => 'en-GB'],
]);
$none = ConfigurationDiagnostics::mailTemplates($requiredTemplates, []);
foreach ([$readyTemplates, $missingTemplates, $translatedOnly, $none] as $diagnostic) {
    if (array_keys($diagnostic) !== ['kind', 'labelKey', 'severity', 'stateKey', 'guidanceKey']
        || str_contains(json_encode($diagnostic, JSON_THROW_ON_ERROR), 'com_jlms.course.completed')) {
        throw new RuntimeException('SCR-16 mail diagnostics disclose identifiers or unexpected fields');
    }
}
if ($readyTemplates['severity'] !== 'ok' || $missingTemplates['severity'] !== 'error'
    || $translatedOnly['severity'] !== 'error' || $none['severity'] !== 'error') {
    throw new RuntimeException('SCR-16 mail default-template detection failed');
}
echo "DOC-07: scheduler/provider actionable statuses and privacy-safe projections OK\n";
