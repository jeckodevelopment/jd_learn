<?php

declare(strict_types=1);


require __DIR__ . '/bootstrap.php';

use Jlms\Component\Jlms\Administrator\Domain\Coverage;
use Jlms\Component\Jlms\Administrator\Domain\QuizScorer;
use Jlms\Component\Jlms\Administrator\Domain\AccessPolicy;
use Jlms\Component\Jlms\Administrator\Domain\DecimalId;
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
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]],
    [(object) ['module_id' => 3, 'required' => 0, 'kind' => 'text']],
);
expect(in_array('COM_JLMS_READINESS_REQUIRED_LESSON', $readiness['blockers'], true)
    && !in_array('COM_JLMS_READINESS_MODULE_EMPTY', $readiness['blockers'], true), 'pre-publication checklist distinguishes optional lesson from empty module');
$readiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]],
    [(object) ['module_id' => 3, 'required' => 1, 'kind' => 'text']],
);
expect($readiness === ['blockers' => [], 'warnings' => []], 'complete basic structure passes preliminary checks without notices');
$readiness = \\Jlms\\Component\\Jlms\\Administrator\\Domain\\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]],
    [(object) ['module_id' => 3, 'required' => 1, 'kind' => 'video', 'duration_ms' => null, 'params_json' => '{}']],
);
expect(in_array('COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED', $readiness['blockers'], true), 'pre-publication checklist blocks a video without confirmed bounded duration');
$readiness = \\Jlms\\Component\\Jlms\\Administrator\\Domain\\PublicationReadiness::inspect(
    $readinessCourse('<p>Course description</p>', 'Short summary', 'images/course.png'),
    [(object) ['id' => 3]],
    [(object) ['module_id' => 3, 'required' => 1, 'kind' => 'video', 'duration_ms' => '60000', 'params_json' => '{"video_duration_confirmed":true}']],
);
expect(!in_array('COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED', $readiness['blockers'], true), 'pre-publication checklist accepts confirmed video duration in the publication range');
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
expect($enabled->getDescriptor()->configured && $enabled->getCapabilities($source)->position, 'explicitly enabled embed advertises actual position capability');
$playback = $enabled->createPlaybackDescriptor($source, new \Jlms\Contracts\Video\PlaybackContext(1,2,3,'it-IT',0,'grant',new \DateTimeImmutable()));
expect($playback->playerOptions['reference'] === 'dQw4w9WgXcQ' && $playback->playerOptions['autoplay'] === false, 'canonical whitelisted playback options');
expect(!(new \Jlms\Plugin\Jlmsvideo\Youtube\Provider\Provider())->getDescriptor()->configured, 'embed disabled by default');
echo json_encode(['unit_assertions' => $passed, 'status' => 'passed'], JSON_THROW_ON_ERROR) . PHP_EOL;
