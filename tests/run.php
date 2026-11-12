<?php

declare(strict_types=1);


require __DIR__ . '/bootstrap.php';

use Jlms\Component\Jlms\Administrator\Domain\Coverage;
use Jlms\Component\Jlms\Administrator\Domain\QuizScorer;
use Jlms\Component\Jlms\Administrator\Domain\AccessPolicy;
use Jlms\Component\Jlms\Administrator\Domain\DecimalId;
use Jlms\Component\Jlms\Administrator\Domain\MaterialAvailability;
use Jlms\Component\Jlms\Administrator\Domain\LessonAvailability;
use Jlms\Contracts\Video\SourceInput;
use Jlms\Contracts\Video\SourceNormalizer;
use Jlms\Contracts\Video\VideoProviderRegistry;
use Jlms\Contracts\Video\ProviderDescriptor;
use Jlms\Contracts\Video\AbstractFoundationProvider;

$passed = 0;
function expect(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) {
        throw new \RuntimeException($message);
    }
    $passed++;
}
function rejects(callable $work, string $message): void
{
    try {
        $work();
    } catch (\DomainException | \InvalidArgumentException $expected) {
        expect(true, $message);
        return;
    }
    throw new \RuntimeException($message);
}
$mailPrefsClass = \Jlms\Component\Jlms\Administrator\Application\MailPreferences::class;
$mailParams = $mailPrefsClass::merge(['mail_enrollment' => 'disabled'], '{"unrelated_setting":"retain-me"}');
expect(!$mailPrefsClass::enabled('enrollment', true, $mailParams), 'course can disable a globally enabled enrollment email');
expect($mailPrefsClass::enabled('completion', true, $mailParams), 'unset course preference inherits the global completion email setting');
expect(!$mailPrefsClass::enabled('completion', false, $mailParams), 'inherited course setting follows the disabled global default');
$courseOverride = $mailPrefsClass::merge(['mail_completion' => 'enabled'], $mailParams);
expect($mailPrefsClass::enabled('completion', false, $courseOverride), 'explicit course preference overrides the global default');
$mailParams = $mailPrefsClass::merge(['mail_suspension' => 'disabled', 'mail_reactivation' => 'enabled'], $mailParams);
expect(!$mailPrefsClass::enabled('suspension', true, $mailParams)
    && $mailPrefsClass::enabled('reactivation', false, $mailParams), 'suspension and reactivation preferences support independent course overrides');
$mailParams = $mailPrefsClass::merge(['mail_enrollment' => 'inherit'], $mailParams);
expect($mailPrefsClass::enabled('enrollment', true, $mailParams)
    && json_decode($mailParams, true)['unrelated_setting'] === 'retain-me', 'inherit removes only the course override and retains unrelated course parameters');
rejects(static fn () => $mailPrefsClass::merge(['mail_completion' => 'yes'], '{}'), 'mail preference values are restricted to inherit, enabled or disabled');
$readinessCourse = static fn (string $description, string $summary = '', string $image = ''): object => (object) [
    'summary' => $summary,
    'params_json' => json_encode(['presentation' => ['_schema' => 1, 'description' => $description, 'image' => $image]], JSON_THROW_ON_ERROR),
];
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect($readinessCourse('  '), [], []);
expect(in_array('COM_JLMS_COURSE_DESCRIPTION_REQUIRED', $readiness['blockers'], true)
    && in_array('COM_JLMS_READINESS_MODULES_REQUIRED', $readiness['blockers'], true)
    && in_array('COM_JLMS_READINESS_REQUIRED_LESSON', $readiness['blockers'], true), 'pre-publication checklist reports description and empty programme blockers');
expect(in_array('COM_JLMS_READINESS_SUMMARY_MISSING', $readiness['warnings'], true)
    && in_array('COM_JLMS_READINESS_IMAGE_MISSING', $readiness['warnings'], true), 'optional course presentation gaps are warnings');
$pathwayProgress = \Jlms\Component\Jlms\Administrator\Application\PathwayProgress::summarize([
    ['access' => 'available', 'percent' => 50],
    ['access' => 'available', 'percent' => null],
]);
expect($pathwayProgress === ['percent' => 25, 'unavailable' => false, 'required' => 2], 'unenrolled course contributes zero to equally weighted pathway progress');
$pathwayProgress = \Jlms\Component\Jlms\Administrator\Application\PathwayProgress::summarize([['access' => 'available', 'percent' => null, 'progress_applicable' => false]]);
expect($pathwayProgress === ['percent' => null, 'unavailable' => false, 'required' => 0], 'consultation-only pathway has no progress percentage');
$pathwayProgress = \Jlms\Component\Jlms\Administrator\Application\PathwayProgress::summarize([
    ['access' => 'available', 'percent' => 80],
    ['access' => 'unavailable', 'percent' => 100],
    ['access' => 'restricted', 'percent' => 70],
]);
expect($pathwayProgress === ['percent' => 26, 'unavailable' => true, 'required' => 3], 'unavailable courses count as zero without leaking their stored progress');
expect(\Jlms\Component\Jlms\Administrator\Application\PathwayProgress::summarize([])
    === ['percent' => null, 'unavailable' => false, 'required' => 0], 'empty pathway has no completion percentage');
$pathwayProgress = \Jlms\Component\Jlms\Administrator\Application\PathwayProgress::summarize([
    ['access' => 'available', 'percent' => 150],
    ['access' => 'available', 'percent' => -5],
]);
expect($pathwayProgress['percent'] === 50, 'pathway progress is bounded to each course range');
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]],
    [(object) ['module_id' => 3, 'required' => 0, 'kind' => 'text']],
);
expect(in_array('COM_JLMS_READINESS_REQUIRED_LESSON', $readiness['blockers'], true)
    && !in_array('COM_JLMS_READINESS_MODULE_EMPTY', $readiness['blockers'], true), 'pre-publication checklist distinguishes optional lesson from empty module');
$consultationRules = ['mode' => 'consultation', 'completion_policy' => 'none', 'sequence_mode' => 'free'];
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]], [(object) ['module_id' => 3, 'required' => 0, 'kind' => 'text']], [], false, $consultationRules,
);
expect(!in_array('COM_JLMS_READINESS_REQUIRED_LESSON', $readiness['blockers'], true), 'consultation courses may contain only optional lessons');
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]], [(object) ['module_id' => 3, 'required' => 0, 'kind' => 'video', 'duration_ms' => null, 'params_json' => '{}']], [], false, $consultationRules,
);
expect(!in_array('COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED', $readiness['blockers'], true), 'consultation videos do not require tracked duration metadata');
expect(in_array('COM_JLMS_READINESS_VIDEO_DURATION_UNCONFIRMED', $readiness['warnings'], true), 'consultation videos warn when duration is not confirmed');
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]], [(object) ['module_id' => 3, 'required' => 1, 'kind' => 'text']], [], false, $consultationRules,
);
expect(in_array('COM_JLMS_CONSULTATION_COURSE_HAS_REQUIRED_LESSONS', $readiness['blockers'], true), 'consultation courses reject required lessons');
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]], [(object) ['module_id' => 3, 'required' => 0, 'kind' => 'quiz']], [], false, $consultationRules,
);
expect(in_array('COM_JLMS_CONSULTATION_QUIZ_UNSUPPORTED', $readiness['blockers'], true), 'consultation courses reject quiz lessons');
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]],
    [(object) ['module_id' => 3, 'required' => 1, 'kind' => 'text']],
);
expect($readiness === ['blockers' => [], 'warnings' => []], 'complete basic structure passes preliminary checks without notices');
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]],
    [(object) ['module_id' => 3, 'required' => 1, 'kind' => 'video', 'duration_ms' => null, 'params_json' => '{}']],
);
expect(in_array('COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED', $readiness['blockers'], true), 'pre-publication checklist blocks a video without confirmed bounded duration');
expect(!in_array('COM_JLMS_READINESS_VIDEO_DURATION_UNCONFIRMED', $readiness['warnings'], true), 'learning-mode duration remains a blocker without a duplicate warning');
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]],
    [(object) ['id' => 9, 'module_id' => 3, 'required' => 1, 'kind' => 'document']],
);
expect(in_array('COM_JLMS_DOCUMENT_MATERIAL_REQUIRED', $readiness['blockers'], true), 'pre-publication checklist requires a ready attachment for a document lesson');
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]],
    [(object) ['id' => 9, 'module_id' => 3, 'required' => 1, 'kind' => 'document']],
    [(object) ['lesson_id' => 9, 'status' => 'ready', 'state' => 1, 'preview' => 0, 'publish_up' => null, 'publish_down' => null, 'scan_json' => '{}']],
);
expect(!in_array('COM_JLMS_DOCUMENT_MATERIAL_REQUIRED', $readiness['blockers'], true), 'pre-publication checklist accepts a ready lesson attachment');
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]],
    [(object) ['module_id' => 3, 'required' => 1, 'kind' => 'video', 'duration_ms' => '60000', 'params_json' => '{"video_duration_confirmed":true}']],
);
expect(!in_array('COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED', $readiness['blockers'], true), 'pre-publication checklist accepts confirmed video duration in the publication range');
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]],
    [(object) ['module_id' => 3, 'required' => 1, 'kind' => 'text']],
    [(object) ['status' => 'quarantine', 'preview' => 0, 'publish_up' => null, 'publish_down' => null, 'scan_json' => '{}']],
);
expect(in_array('COM_JLMS_MATERIAL_NOT_READY', $readiness['blockers'], true), 'pre-publication checklist blocks a material that is still quarantined');
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]],
    [(object) ['module_id' => 3, 'required' => 1, 'kind' => 'text']],
    [(object) ['status' => 'ready', 'preview' => 0, 'publish_up' => null, 'publish_down' => null, 'scan_json' => '{"engine":"clamd","result":"clean"}']],
    true,
);
expect(!in_array('COM_JLMS_MATERIAL_NOT_READY', $readiness['blockers'], true), 'pre-publication checklist accepts a ready material with required clean scan');
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]],
    [(object) ['module_id' => 3, 'required' => 1, 'kind' => 'text']],
    [(object) ['status' => 'ready', 'preview' => 1, 'publish_up' => null, 'publish_down' => null, 'scan_json' => '{}']],
);
expect(!in_array('COM_JLMS_MATERIAL_NOT_READY', $readiness['blockers'], true), 'pre-publication checklist accepts a ready course-level public preview attachment');
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]],
    [(object) ['module_id' => 3, 'required' => 1, 'kind' => 'text']],
    [(object) ['lesson_id' => 12, 'status' => 'ready', 'preview' => 1, 'publish_up' => null, 'publish_down' => null, 'scan_json' => '{}']],
);
expect(in_array('COM_JLMS_MATERIAL_NOT_READY', $readiness['blockers'], true), 'pre-publication checklist rejects public preview on lesson attachments');
$scheduled = MaterialAvailability::normalize('2026-02-02T10:30:00', '2026-02-02T12:00:00', 'Europe/Rome');
expect($scheduled === ['publish_up' => '2026-02-02 09:30:00', 'publish_down' => '2026-02-02 11:00:00'], 'material window is stored as UTC using the course time zone');
expect(MaterialAvailability::editor($scheduled['publish_up'], 'Europe/Rome') === '2026-02-02T10:30:00', 'stored material window is shown in the course time zone');
expect(MaterialAvailability::validStored($scheduled['publish_up'], $scheduled['publish_down']), 'stored material schedule is publishable');
expect(MaterialAvailability::validStored($scheduled['publish_up'] . '.000000', $scheduled['publish_down'] . '.000000'), 'MySQL zero-microsecond DATETIME(6) values preserve valid material schedules');
rejects(static fn () => MaterialAvailability::normalize('2026-02-02T12:00:00', '2026-02-02T10:00:00', 'UTC'), 'material end must be later than its start');
$lessonWindow = LessonAvailability::normalize('2026-02-02T10:30:00', '2026-02-02T12:00:00', 'Europe/Rome');
expect($lessonWindow['publish_up'] === '2026-02-02 09:30:00' && $lessonWindow['publish_down'] === '2026-02-02 11:00:00', 'lesson availability is stored as UTC from the course time zone');
expect(LessonAvailability::editor($lessonWindow['publish_up'], 'Europe/Rome') === '2026-02-02T10:30:00', 'lesson schedule editor restores the course-local time');
expect(LessonAvailability::state('2099-01-01 00:00:00', null, null, null, new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC'))) === 'upcoming', 'future lesson start is marked upcoming');
expect(LessonAvailability::state(null, '2025-01-01 00:00:00', null, null, new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC'))) === 'closed', 'elapsed lesson end is marked closed');
expect(LessonAvailability::state(null, null, 3, null, new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC'))) === 'awaiting_start', 'relative lesson is held until enrollment learning starts');
expect(LessonAvailability::state(null, null, 3, '2026-01-01 00:00:00', new DateTimeImmutable('2026-01-03 23:59:59', new DateTimeZone('UTC'))) === 'upcoming', 'relative lesson waits for the configured number of full days');
expect(LessonAvailability::state(null, null, 3, '2026-01-01 00:00:00', new DateTimeImmutable('2026-01-04 00:00:00', new DateTimeZone('UTC'))) === 'available', 'relative lesson opens at the exact release boundary');
expect(LessonAvailability::normalizeReleaseDays('3650') === 3650 && LessonAvailability::normalizeReleaseDays('') === null, 'relative release is bounded and blank means no delay');
rejects(static fn () => LessonAvailability::normalizeReleaseDays('3651'), 'relative release beyond ten years is rejected');
expect(LessonAvailability::reachable('2026-01-02 00:00:00', null, ['publish_down' => '2026-01-03 00:00:00']), 'required lesson can open within the course window');
expect(!LessonAvailability::reachable('2026-01-04 00:00:00', null, ['publish_down' => '2026-01-03 00:00:00']), 'required lesson outside course window is unreachable');
rejects(static fn () => LessonAvailability::normalize('2026-02-02T12:00:00', '2026-02-02T10:00:00', 'UTC'), 'lesson end must be later than its start');
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]],
    [(object) ['module_id' => 3, 'required' => 1, 'kind' => 'text']],
    [(object) ['status' => 'ready', 'preview' => 0, 'publish_up' => $scheduled['publish_up'], 'publish_down' => $scheduled['publish_down'], 'scan_json' => '{}']],
);
expect(!in_array('COM_JLMS_MATERIAL_NOT_READY', $readiness['blockers'], true)
    && in_array('COM_JLMS_READINESS_MATERIAL_SCHEDULED', $readiness['warnings'], true), 'valid scheduled material warns without blocking publication');
expect(Coverage::merge([[0, 20000], [50000, 60000]], [[10000, 40000], [40000, 50000]], 100000) === [[0, 60000]], 'union across duplicates/adjacent intervals');
$replayed = Coverage::merge([[0, 60000]], [[10000, 40000]], 100000);
expect(Coverage::coveredMs($replayed) === 60000, 'replay does not double count');
expect(!Coverage::isComplete(89999, 100000), '90 percent threshold is exact');
expect(Coverage::isComplete(90000, 100000), '90 percent complete');
rejects(fn () => Coverage::merge([], [[0, 100001]], 100000), 'cannot count beyond duration');
rejects(fn () => Coverage::merge([], [[5000, 1000]], 100000), 'negative interval rejected');
rejects(fn () => Coverage::merge([], [[0, 1.5]], 100000), 'floats rejected');
expect(QuizScorer::score(['b', 'a'], ['a', 'b'], 3) === 3, 'multiple choice exact set ignores order');
expect(QuizScorer::score(['a'], ['a', 'b'], 3) === 0, 'no partial points');
expect(QuizScorer::score([], ['a'], 3) === 0, 'unanswered is zero');
expect(QuizScorer::score(['a','c'], ['a', 'b'], 3) === 0, 'wrong extra option is zero');
rejects(fn () => QuizScorer::score(['a','a'], ['a'], 1), 'duplicate options rejected');
expect(QuizScorer::passes(7, 10) && !QuizScorer::passes(69, 100), '70 percent threshold exact');
rejects(fn () => QuizScorer::passes(0, 0), 'zero denominator rejected');
$policy = new AccessPolicy();
$policy->assertCanLearn(42, true, [1,2], 2, 42, 'active', true, true, true);
expect(true, 'current access and ownership accepted');
rejects(fn () => $policy->assertCanLearn(42, true, [1], 2, 42, 'active', true, true, true), 'revocation after an earlier allowed request denies immediately');
rejects(fn () => $policy->assertCanLearn(43, true, [2], 2, 42, 'active', true, true, true), 'another student cannot access');
rejects(fn () => $policy->assertCanLearn(42, false, [2], 2, 42, 'active', true, true, true), 'blocked core account cannot access');
rejects(fn () => $policy->assertCanLearn(42, true, [2], 2, 42, 'suspended', true, true, true), 'suspended enrolment cannot access');
rejects(fn () => $policy->assertCanLearn(42, true, [2], 2, 42, 'active', true, false, true), 'expired access window cannot access');
rejects(fn () => $policy->assertCanLearn(42, true, [2], 2, 42, 'active', true, true, false), 'missing prerequisite cannot access');
expect(DecimalId::parse((string) PHP_INT_MAX) === PHP_INT_MAX, 'maximum safe PHP integer accepted from string');
foreach (['0', '-1', '1e2', '01', '9223372036854775808', '18446744073709551615'] as $bad) {
    rejects(fn () => DecimalId::parse($bad), 'unsafe ID rejected: ' . $bad);
}
expect(SourceNormalizer::normalise('youtube', new SourceInput('https://youtu.be/dQw4w9WgXcQ?t=9'))->reference === 'dQw4w9WgXcQ', 'YouTube recognised URL canonicalised');
expect(SourceNormalizer::normalise('vimeo', new SourceInput('https://vimeo.com/12345/abc123'))->options === ['privacy_hash' => 'abc123'], 'Vimeo private hash preserved');
expect(SourceNormalizer::normalise('vimeo', new SourceInput('https://player.vimeo.com/video/12345?h=abc123'))->options === ['privacy_hash' => 'abc123'], 'Vimeo player URL hash preserved');
foreach (['https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ', 'https://127.0.0.1/video', 'https://user:pass@youtube.com/watch?v=dQw4w9WgXcQ', 'https://youtube.com/playlist?list=123', 'https://youtube.com:443/watch?v=dQw4w9WgXcQ'] as $bad) {
    rejects(fn () => SourceNormalizer::normalise('youtube', new SourceInput($bad)), 'unsafe/unsupported YouTube URL rejected');
}
rejects(fn () => SourceNormalizer::normalise('html5', new SourceInput('../../private.mp4')), 'HTML5 physical paths are rejected');
rejects(fn () => SourceNormalizer::normalise('html5', new SourceInput('https://example.com/video.mp4')), 'HTML5 remote URL is rejected');
$registry = new VideoProviderRegistry();
$providerStatuses = \Jlms\Component\Jlms\Administrator\Application\DashboardProviderStatus::summarize([
    'youtube' => (object) ['enabled' => 1, 'name' => 'YouTube'],
    'vimeo' => (object) ['enabled' => 0, 'name' => 'Vimeo'],
    'cloudflarestream' => (object) ['enabled' => 1, 'name' => 'Cloudflare Stream'],
], [
    new ProviderDescriptor('youtube', 'PLUGIN_YOUTUBE', 1, true),
    new ProviderDescriptor('cloudflarestream', 'PLUGIN_CF', 1, false),
]);
$providerStates = array_column($providerStatuses, 'stateKey', 'key');
expect($providerStates['youtube'] === 'COM_JLMS_READY', 'dashboard reports configured provider as ready');
expect($providerStates['vimeo'] === 'COM_JLMS_PROVIDER_DISABLED', 'dashboard reports installed disabled provider');
expect($providerStates['cloudflarestream'] === 'COM_JLMS_NOT_CONFIGURED', 'dashboard reports provider setup status');
expect($providerStates['html5'] === 'COM_JLMS_PROVIDER_NOT_INSTALLED', 'dashboard reports missing bundled provider');
$descriptor = new ProviderDescriptor('example', 'EXAMPLE', 1, false);
$created = 0;
$registry->register($descriptor, static function () use (&$created) {
    $created++;
    return new class () extends AbstractFoundationProvider {
        protected function key(): string { return 'example'; }
    };
});
expect($created === 0, 'registration is lazy');
expect($registry->get('example') === $registry->get('example') && $created === 1, 'one adapter per request');
rejects(fn () => $registry->register($descriptor, fn () => null), 'duplicate provider rejected');
rejects(fn () => $registry->register(new ProviderDescriptor('newprovider', 'NEW', 2, true), fn () => null), 'contract major mismatch rejected');
rejects(fn () => $registry->get('missing'), 'missing provider never falls back to public source');

$state = ['issued_ms' => 100000, 'spent_wall_ms' => 0, 'last_event_id' => 0, 'playing' => false];
$event = static fn (int $id, string $type, int $position, int $time, bool $playing = true, int $rate = 1000): array =>
    ['id' => $id, 'type' => $type, 'position_ms' => $position, 'client_ms' => $time, 'playing' => $playing, 'rate_milli' => $rate];
$consume = static fn (array $events, int $now = 110000): array => \Jlms\Component\Jlms\Administrator\Domain\VideoTelemetry::consume($state, $events, 10000, $now);
expect($consume([$event(1, 'play', 0, 0), $event(2, 'position', 9000, 9000)])['intervals'] === [[0,9000]], 'continuous 90 percent coverage');
expect($consume([$event(1, 'ended', 10000, 0, false)])['intervals'] === [], 'ended alone awards no coverage');
expect($consume([$event(1, 'play', 0, 0), $event(2, 'position', 9000, 1000)])['intervals'] === [], 'seek jump does not count');
expect($consume([$event(1, 'play', 0, 0), $event(2, 'seeked', 9000, 9000)])['intervals'] === [], 'explicit seek discards interval');
expect($consume([$event(1, 'play', 0, 0), $event(2, 'position', 9000, 9000)], 100000)['intervals'] === [], 'server time prevents instantaneous fabricated coverage');
expect($consume([$event(1, 'play', 0, 0, true, 2000), $event(2, 'position', 10000, 5000, true, 2000)], 105000)['intervals'] === [[0,10000]], 'two times speed costs actual wall time');
rejects(fn () => $consume([$event(1,'play',0,0),$event(1,'position',1000,1000)]), 'duplicate event id rejected');
rejects(fn () => $consume([$event(1,'position',10001,0)]), 'out of duration sample rejected');
rejects(fn () => $consume([$event(1,'play',0,0,true,3000)]), 'unsupported playback rate rejected');
rejects(fn () => $consume(array_fill(0,101,$event(1,'play',0,0))), 'bounded event count');
require_once __DIR__ . '/../plugins/jlmsvideo/youtube/src/Provider/Provider.php';
$enabled = new \Jlms\Plugin\Jlmsvideo\Youtube\Provider\Provider(true);
$source = $enabled->normaliseSource(new SourceInput('https://youtu.be/dQw4w9WgXcQ'));
expect($enabled->getDescriptor()->configured && ($caps = $enabled->getCapabilities($source))->position && $caps->duration && $caps->seek, 'enabled embed declares minimum tracked-learning capabilities');
$supportsTrackedLearning = static fn (\Jlms\Contracts\Video\VideoCapabilities $capabilities): bool =>
    $capabilities->position && $capabilities->duration && $capabilities->seek;
$optional = new \Jlms\Contracts\Video\VideoCapabilities(true, true, true, false, false, false, false, false);
expect($supportsTrackedLearning($optional), 'speed, captions and transcripts remain optional');
expect(!$supportsTrackedLearning(new \Jlms\Contracts\Video\VideoCapabilities(true, false, true, true, true, true, false, false)), 'provider without duration cannot support tracked learning');
expect(!$supportsTrackedLearning(new \Jlms\Contracts\Video\VideoCapabilities(true, true, false, true, true, true, false, false)), 'provider without seek cannot support tracked learning');
$playback = $enabled->createPlaybackDescriptor($source, new \Jlms\Contracts\Video\PlaybackContext(1,2,3,'it-IT',0,'grant',new \DateTimeImmutable()));
expect($playback->playerOptions['reference'] === 'dQw4w9WgXcQ' && $playback->playerOptions['autoplay'] === false, 'canonical whitelisted playback options');
expect(!(new \Jlms\Plugin\Jlmsvideo\Youtube\Provider\Provider())->getDescriptor()->configured, 'embed disabled by default');
echo json_encode(['unit_assertions' => $passed, 'status' => 'passed'], JSON_THROW_ON_ERROR) . PHP_EOL;
