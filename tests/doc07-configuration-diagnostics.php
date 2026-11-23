<?php

declare(strict_types=1);

require __DIR__ . '/../component/administrator/src/Infrastructure/SchedulerHealth.php';
require __DIR__ . '/../component/administrator/src/Application/DashboardProviderStatus.php';
require __DIR__ . '/../component/administrator/src/Application/ConfigurationDiagnostics.php';
require __DIR__ . '/../component/administrator/src/Application/MailPreferences.php';

use Jlms\Component\Jlms\Administrator\Application\ConfigurationDiagnostics;

$states = ['missing', 'invalid_rules', 'disabled', 'duplicates', 'failed', 'overdue', 'waiting', 'operational', 'unknown'];
$scheduler = [];
foreach ($states as $state) {
    $scheduler[] = ['label' => 'COM_JLMS_SCHEDULER_MAIL', 'state' => $state, 'duplicates' => false];
}
$scheduler[] = ['label' => 'COM_JLMS_SCHEDULER_MAIL', 'state' => 'operational', 'duplicates' => true];
$providerStates = [
    'COM_JLMS_PROVIDER_NOT_INSTALLED', 'COM_JLMS_PROVIDER_DISABLED',
    'COM_JLMS_PROVIDER_UNAVAILABLE', 'COM_JLMS_PROVIDER_UNVERIFIED', 'COM_JLMS_NOT_CONFIGURED',
    'COM_JLMS_READY', 'unexpected',
];
$providers = array_map(
    static fn (string $state): array => ['labelKey' => 'COM_JLMS_PROVIDER_YOUTUBE', 'stateKey' => $state],
    $providerStates
);
$result = ConfigurationDiagnostics::summarize($scheduler, $providers);
$providerOffset = count($scheduler);
if ($result[$providerOffset]['labelKey'] !== 'COM_JLMS_PROVIDER_YOUTUBE') {
    throw new RuntimeException('SCR-16 lost a valid provider display label in the diagnostic chain');
}
$thirdPartyLabel = ConfigurationDiagnostics::summarize([], [
    ['labelKey' => 'PLG_JLMSVIDEO_CUSTOM_TITLE', 'stateKey' => 'COM_JLMS_READY'],
    ['labelKey' => 'PLG_JLMSVIDEO_PRIVATE_SECRET=TOKEN', 'stateKey' => 'COM_JLMS_READY'],
]);
if (
    $thirdPartyLabel[0]['labelKey'] !== 'PLG_JLMSVIDEO_CUSTOM_TITLE'
    || $thirdPartyLabel[1]['labelKey'] !== 'COM_JLMS_CONFIG_DIAGNOSTICS_UNKNOWN_LABEL'
) {
    throw new RuntimeException('SCR-16 did not preserve safe labels or redact untrusted plugin labels');
}

if (count($result) !== count($scheduler) + count($providers)) {
    throw new RuntimeException('Configuration checks lost or added an unrecognized check');
}
foreach ($result as $check) {
    if (
        !in_array($check['severity'], ['ok', 'warning', 'error'], true)
        || !preg_match('/^COM_JLMS_[A-Z0-9_]+$/', $check['labelKey'])
        || !preg_match('/^COM_JLMS_[A-Z0-9_]+$/', $check['stateKey'])
        || !preg_match('/^COM_JLMS_[A-Z0-9_]+$/', $check['guidanceKey'])
    ) {
        throw new RuntimeException('A configuration diagnostic contains unsafe raw data');
    }
    if (array_diff(array_keys($check), ['kind', 'labelKey', 'severity', 'guidanceKey', 'stateKey'])) {
        throw new RuntimeException('A configuration diagnostic exposed unexpected fields');
    }
}
if (
    $result[3]['severity'] !== 'error' || $result[9]['severity'] !== 'error'
    || $result[7]['severity'] !== 'ok'
    || $result[count($scheduler) + 2]['severity'] !== 'error'
    || $result[count($scheduler) + 3]['severity'] !== 'warning'
    || $result[count($scheduler) + 3]['stateKey'] !== 'COM_JLMS_PROVIDER_UNVERIFIED'
    || $result[count($scheduler) + 5]['severity'] !== 'ok'
    || $result[count($scheduler) + 6]['severity'] !== 'warning'
) {
    throw new RuntimeException('Unsafe severity classification or duplicate precedence');
}
if ($result[9]['guidanceKey'] !== 'COM_JLMS_SCHEDULER_GUIDANCE_DUPLICATES') {
    throw new RuntimeException('Duplicate scheduler task did not override healthy status');
}
$untrusted = ConfigurationDiagnostics::summarize([], [['labelKey' => '<script>secret</script>', 'stateKey'
    => 'unexpected']]);
if (
    $untrusted[0]['labelKey'] !== 'COM_JLMS_CONFIG_DIAGNOSTICS_UNKNOWN_LABEL'
    || $untrusted[0]['stateKey'] !== 'COM_JLMS_PROVIDER_GUIDANCE_UNKNOWN'
) {
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
        if (
            array_keys($result) !== ['kind', 'labelKey', 'severity', 'stateKey', 'guidanceKey']
            || !in_array($result['severity'], ['ok', 'warning', 'error'], true)
            || str_contains(json_encode($result, JSON_THROW_ON_ERROR), $tmpBase)
        ) {
            throw new RuntimeException('SCR-16 storage diagnostic leaks paths or unexpected fields');
        }
    }
    if (
        $private['severity'] !== 'ok' || $public['severity'] !== 'error'
        || $missing['severity'] !== 'error' || $managed['severity'] !== 'warning'
    ) {
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
    if (
        array_keys($diagnostic) !== ['kind', 'labelKey', 'severity', 'stateKey', 'guidanceKey']
        || str_contains(json_encode($diagnostic, JSON_THROW_ON_ERROR), 'com_jlms.course.completed')
    ) {
        throw new RuntimeException('SCR-16 mail diagnostics disclose identifiers or unexpected fields');
    }
}
if (
    $readyTemplates['severity'] !== 'ok' || $missingTemplates['severity'] !== 'error'
    || $translatedOnly['severity'] !== 'error' || $none['severity'] !== 'error'
) {
    throw new RuntimeException('SCR-16 mail default-template detection failed');
}
// The runtime rejects DOCUMENT_ROOT even when Joomla site/public roots differ.
$separate = sys_get_temp_dir() . '/jdlearn-scr16-roots-' . bin2hex(random_bytes(6));
mkdir($separate, 0700);
foreach (['site', 'public', 'web', 'web/private', 'private'] as $dir) {
    mkdir($separate . '/' . $dir, 0700);
}
try {
    $exposed = ConfigurationDiagnostics::storage(
        $separate . '/web/private',
        $separate . '/site',
        $separate . '/public',
        $separate . '/web'
    );
    $outside = ConfigurationDiagnostics::storage(
        $separate . '/private',
        $separate . '/site',
        $separate . '/public',
        $separate . '/web'
    );
    if ($exposed['severity'] !== 'error' || $outside['severity'] !== 'ok') {
        throw new RuntimeException('SCR-16 must honor actual web server DOCUMENT_ROOT');
    }
} finally {
    foreach (['web/private', 'web', 'private', 'public', 'site'] as $dir) {
        rmdir($separate . '/' . $dir);
    }
    rmdir($separate);
}
$settings = ConfigurationDiagnostics::operationalSettings([]);
if (
    count($settings) !== 20 || $settings[0]['stateKey'] !== 'COM_JLMS_CONFIG_SCAN_DISABLED'
    || $settings[1]['stateKey'] !== 'COM_JLMS_CONFIG_VIDEO_LOCAL_DISABLED'
    || $settings[2]['stateKey'] !== 'COM_JLMS_CONFIG_CERTIFICATES_DISABLED'
    || $settings[3]['severity'] !== 'ok' || $settings[19]['severity'] !== 'ok'
) {
    throw new RuntimeException('SCR-16 defaults do not reflect declared component configuration');
}
$settingsInvalid = ConfigurationDiagnostics::operationalSettings([
    'material_scan_required' => 1, 'material_clamd_socket' => 'tcp://secret.internal:3310',
    'video_delivery_mode' => 'php_bounded', 'certificate_enabled' => 1,
    'catalog_page_size' => '999', 'catalog_default_order' => 'unknown',
    'mail_enrollment_enabled' => 0, 'mail_completion_enabled' => 0,
]);
if (
    $settingsInvalid[0]['severity'] !== 'error' || $settingsInvalid[1]['severity'] !== 'warning'
    || $settingsInvalid[2]['severity'] !== 'ok' || $settingsInvalid[19]['severity'] !== 'warning'
) {
    throw new RuntimeException('SCR-16 operational flags or invalid configuration not classified');
}
$invalidGlobalCatalog = ConfigurationDiagnostics::operationalSettings(['catalog_show_filters' => 'unexpected']);
$validGlobalCatalog = ConfigurationDiagnostics::operationalSettings([
    'catalog_show_filters' => '0', 'catalog_show_order' => '1',
    'catalog_show_category' => '0', 'catalog_show_cover' => '1',
    'catalog_show_summary' => '0',
]);
if (
    $invalidGlobalCatalog[19]['severity'] !== 'warning'
    || $invalidGlobalCatalog[19]['stateKey'] !== 'COM_JLMS_CONFIG_CATALOG_INVALID'
    || $validGlobalCatalog[19]['severity'] !== 'ok'
) {
    throw new RuntimeException('SCR-16 must diagnose every global catalogue presentation flag');
}
$settingsConfiguredScan = ConfigurationDiagnostics::operationalSettings([
    'material_scan_required' => 1, 'material_clamd_socket' => '/run/clamav/clamd.sock',
]);
if ($settingsConfiguredScan[0]['severity'] !== 'warning') {
    throw new RuntimeException('SCR-16 must not equate configured scanner with reachable scanner');
}
if (
    $settings[4]['labelKey'] !== 'COM_JLMS_MAIL_MANAGER_REQUEST_ENABLED'
    || $settings[4]['severity'] !== 'warning'
    || $settings[11]['labelKey'] !== 'COM_JLMS_MAIL_ENROLLMENT_ENABLED'
    || $settings[11]['severity'] !== 'ok'
    || $settingsInvalid[11]['severity'] !== 'warning'
    || $settingsInvalid[12]['severity'] !== 'warning'
) {
    throw new RuntimeException('SCR-16 must expose individual inherited email notification flags');
}
foreach (array_merge($settings, $settingsInvalid, $settingsConfiguredScan, $invalidGlobalCatalog, $validGlobalCatalog) as $check) {
    if (
        array_keys($check) !== ['kind', 'labelKey', 'severity', 'stateKey', 'guidanceKey']
        || !in_array($check['severity'], ['ok', 'warning', 'error'], true)
    ) {
        throw new RuntimeException('SCR-16 configuration projection contains unexpected fields');
    }
    foreach ($check as $value) {
        if (str_contains($value, 'secret.internal') || str_contains($value, '/run/clamav')) {
            throw new RuntimeException('SCR-16 configuration projection leaks a private value');
        }
    }
}
$videoFixtures = sys_get_temp_dir() . '/jdlearn-video-diag-' . bin2hex(random_bytes(5));
mkdir($videoFixtures, 0700);
foreach (['site', 'public', 'public/media', 'private'] as $sub) {
    mkdir($videoFixtures . '/' . $sub, 0700);
}
try {
    $probe = $videoFixtures . '/probe';
    file_put_contents($probe, "#!/bin/sh\nexit 0\n");
    chmod($probe, 0700);
    $base = [$videoFixtures . '/site', $videoFixtures . '/public', $videoFixtures . '/public'];
    $inactive = ConfigurationDiagnostics::localVideoPrerequisites('disabled', '', '', ...$base);
    $missingVideo = ConfigurationDiagnostics::localVideoPrerequisites('php_bounded', '', '', ...$base);
    $publicVideo = ConfigurationDiagnostics::localVideoPrerequisites(
        'php_bounded',
        $videoFixtures . '/public/media',
        $probe,
        ...$base
    );
    $privateVideo = ConfigurationDiagnostics::localVideoPrerequisites(
        'php_bounded',
        $videoFixtures . '/private',
        $probe,
        ...$base
    );
    $noProbe = ConfigurationDiagnostics::localVideoPrerequisites(
        'php_bounded',
        $videoFixtures . '/private',
        $videoFixtures . '/missing',
        ...$base
    );
    $relative = ConfigurationDiagnostics::localVideoPrerequisites('php_bounded', 'relative', $probe, ...$base);
    if (
        $inactive !== [] || count($missingVideo) !== 2 || $missingVideo[0]['severity'] !== 'error'
        || $publicVideo[0]['severity'] !== 'error' || $privateVideo[0]['severity'] !== 'ok'
        || $privateVideo[1]['severity'] !== 'warning' || $noProbe[1]['severity'] !== 'error'
        || $relative[0]['severity'] !== 'error'
    ) {
        throw new RuntimeException('SCR-16 local video root/probe readiness incorrectly classified');
    }
    foreach ([$missingVideo, $publicVideo, $privateVideo, $noProbe, $relative] as $group) {
        foreach ($group as $check) {
            if (
                array_keys($check) !== ['kind', 'labelKey', 'severity', 'stateKey', 'guidanceKey']
                || str_contains(json_encode($check, JSON_THROW_ON_ERROR), $videoFixtures)
            ) {
                throw new RuntimeException('SCR-16 local-video prerequisites disclose raw paths');
            }
        }
    }
} finally {
    @unlink($videoFixtures . '/probe');
    foreach (['public/media', 'public', 'private', 'site'] as $sub) {
        rmdir($videoFixtures . '/' . $sub);
    }
    rmdir($videoFixtures);
}
$inheritDefault = ConfigurationDiagnostics::inheritance(['{}'], [['link'
    => 'index.php?option=com_jlms&view=courses', 'params' => '{}']], true, true);
$inheritBlankMenu = ConfigurationDiagnostics::inheritance(['{}'], [
    ['link' => 'index.php?option=com_jlms&view=courses', 'params' => ''],
    ['link' => 'index.php?option=com_jlms&view=category&id=5', 'params' => '   '],
], true, true);
$inheritWrongShape = ConfigurationDiagnostics::inheritance(['{}'], [
    ['link' => 'index.php?option=com_jlms&view=courses', 'params' => ['not-a-string']],
], true, true);
$inheritOverridden = ConfigurationDiagnostics::inheritance(
    ['{"mail_preferences":{"enrollment":"disabled","completion":"enabled"}}'],
    [['link' => 'index.php?option=com_jlms&view=category&id=5', 'params' => '{"catalog_page_size":"24"}']],
    true,
    true
);
$inheritInvalid = ConfigurationDiagnostics::inheritance(
    ['{"mail_preferences":{"completion":"untrusted"}}'],
    [['link' => 'index.php?option=com_jlms&view=courses', 'params' => '{"catalog_page_size":"secret-value"}']],
    true,
    true
);
$inheritMalformed = ConfigurationDiagnostics::inheritance(['{malformed'], [
    ['link' => 'index.php?option=com_jlms&view=courses', 'params' => '{bad']
], true, true);
$inheritOverflow = ConfigurationDiagnostics::inheritance(['{"mail_preferences":{"completion":"enabled"}}'], [
    ], false, false);
$inheritInvalidOverflow = ConfigurationDiagnostics::inheritance(
    ['{"mail_preferences":{"completion":"untrusted"}}'],
    [['link' => 'index.php?option=com_jlms&view=courses', 'params' => '{"catalog_page_size":"999"}']],
    false,
    false
);
$courseOverflowOnly = ConfigurationDiagnostics::inheritance(['{}'], [['link'
    => 'index.php?option=com_jlms&view=courses', 'params' => '{}']], false, true);
$menuOverflowOnly = ConfigurationDiagnostics::inheritance(['{}'], [['link'
    => 'index.php?option=com_jlms&view=courses', 'params' => '{}']], true, false);
if (
    $inheritBlankMenu[1]['stateKey'] !== 'COM_JLMS_CONFIG_INHERIT_INHERITED'
    || $inheritWrongShape[1]['stateKey'] !== 'COM_JLMS_CONFIG_INHERIT_INVALID'
    || $inheritDefault[0]['severity'] !== 'ok' || $inheritDefault[1]['severity'] !== 'ok'
    || $inheritOverridden[0]['stateKey'] !== 'COM_JLMS_CONFIG_INHERIT_OVERRIDES'
    || $inheritOverridden[1]['stateKey'] !== 'COM_JLMS_CONFIG_INHERIT_OVERRIDES'
    || $inheritInvalid[0]['severity'] !== 'error' || $inheritInvalid[1]['severity'] !== 'error'
    || $inheritMalformed[0]['severity'] !== 'error' || $inheritMalformed[1]['severity'] !== 'error'
    || $inheritOverflow[0]['stateKey'] !== 'COM_JLMS_CONFIG_INHERIT_INCOMPLETE'
    || $inheritOverflow[1]['stateKey'] !== 'COM_JLMS_CONFIG_INHERIT_INCOMPLETE'
    || $inheritInvalidOverflow[0]['stateKey'] !== 'COM_JLMS_CONFIG_INHERIT_INVALID'
    || $inheritInvalidOverflow[1]['stateKey'] !== 'COM_JLMS_CONFIG_INHERIT_INVALID'
    || $inheritInvalidOverflow[0]['severity'] !== 'error'
    || $inheritInvalidOverflow[1]['severity'] !== 'error'
    || $courseOverflowOnly[0]['stateKey'] !== 'COM_JLMS_CONFIG_INHERIT_INCOMPLETE'
    || $courseOverflowOnly[1]['stateKey'] !== 'COM_JLMS_CONFIG_INHERIT_INHERITED'
    || $menuOverflowOnly[0]['stateKey'] !== 'COM_JLMS_CONFIG_INHERIT_INHERITED'
    || $menuOverflowOnly[1]['stateKey'] !== 'COM_JLMS_CONFIG_INHERIT_INCOMPLETE'
) {
    throw new RuntimeException('SCR-16 course/menu inheritance classification regression');
}
foreach ([$inheritDefault, $inheritBlankMenu, $inheritWrongShape, $inheritOverridden, $inheritInvalid, $inheritMalformed, $inheritOverflow, $inheritInvalidOverflow, $courseOverflowOnly, $menuOverflowOnly] as $checks) {
    foreach ($checks as $check) {
        if (
            array_keys($check) !== ['kind', 'labelKey', 'severity', 'stateKey', 'guidanceKey']
            || str_contains(json_encode($check, JSON_THROW_ON_ERROR), 'secret-value')
            || str_contains(json_encode($check, JSON_THROW_ON_ERROR), 'enrollment')
        ) {
            throw new RuntimeException('SCR-16 inheritance diagnostic exposes raw context');
        }
    }
}
echo "DOC-07: scheduler/provider actionable statuses and privacy-safe projections OK\n";
