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
echo json_encode(['unit_assertions' => $passed, 'status' => 'passed'], JSON_THROW_ON_ERROR) . PHP_EOL;
