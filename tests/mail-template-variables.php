<?php

declare(strict_types=1);

require_once __DIR__ . '/../component/administrator/src/Infrastructure/MailTemplateVariables.php';

use Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateVariables;

function check(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException($label);
    }
}

$legacy = MailTemplateVariables::withAliases([
    'NAME' => 'Ada Lovelace',
    'COURSE' => 'Algorithms',
    'SITENAME' => 'Academy',
    'COURSE_HTML' => '&lt;Course&gt;',
]);
check($legacy['USER_FULLNAME'] === 'Ada Lovelace', 'recipient alias');
check($legacy['COURSE_TITLE'] === 'Algorithms', 'course alias');
check($legacy['SITE_NAME'] === 'Academy', 'site alias');
check($legacy['COURSE_TITLE_HTML'] === '&lt;Course&gt;', 'HTML alias');

$canonical = MailTemplateVariables::withAliases([
    'USER_FULLNAME' => 'Grace',
    'COURSE_TITLE' => 'Systems',
    'SITE_NAME' => 'Learning',
]);
check($canonical['NAME'] === 'Grace', 'legacy recipient');
check($canonical['COURSE'] === 'Systems', 'legacy course');
check($canonical['SITENAME'] === 'Learning', 'legacy site');

$existing = MailTemplateVariables::withAliases(['COURSE' => 'Preserved', 'COURSE_TITLE' => 'Canonical']);
check($existing['COURSE'] === 'Preserved' && $existing['COURSE_TITLE'] === 'Canonical', 'do not overwrite');
check(MailTemplateVariables::withAliases([]) === [], 'no invented data');
check(MailTemplateVariables::withAliases($canonical) === $canonical, 'idempotent');

echo "Mail template variable compatibility checks passed\n";
