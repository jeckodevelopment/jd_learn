<?php

declare(strict_types=1);

use Jlms\Component\Jlms\Administrator\Application\CurriculumService as JdCurriculumService;
use Jlms\Plugin\Jlmsvideo\Youtube\Provider\Provider as JdProvider;
use Joomla\CMS\Component\ComponentHelper as NativeComponentHelper;
use Joomla\CMS\Language\LanguageFactoryInterface as NativeLanguageFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\VideoProviderRegistry;

function restoreExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException($message);
    }
    $checks++;
}

function restoreDenied(callable $work, string $key): void
{
    try {
        $work();
    } catch (\DomainException | \RuntimeException $e) {
        restoreExpect($e->getMessage() === $key, 'Expected ' . $key . ', got ' . $e->getMessage());
        return;
    }throw new RuntimeException('Expected rejection ' . $key);
}

function restoreRow(string $table, int $id): object
{
    global $db;
    return $db->setQuery('SELECT * FROM #__jlms_' . $table . ' WHERE id=' . $id)->loadObject();
}

function restoreLearning(): array
{
    global $db;
 // Preserve complete rows, not only counts: a restore must not change issued
 // certificates, their original enrollment/version references or PDF metadata.
    $ordering = [
    'enrollments' => 'id',
    'attempts' => 'id',
    'lesson_progress' => 'enrollment_id,lesson_id',
    'certificates' => 'id',
    'certificate_documents' => 'id',
    ];
    $snapshot = [];
    foreach ($ordering as $table => $order) {
        $snapshot[$table] = $db->setQuery('SELECT * FROM #__jlms_' . $table . ' ORDER BY ' . $order)->loadAssocList();
    }
    return $snapshot;
}

function restoreTree(int $id): array
{
    global $db;
    $out = ['version' => (array)restoreRow('course_versions', $id)];
    foreach (['modules','lessons','quizzes','attachments'] as $table) {
        $out[$table] = $db->setQuery('SELECT * FROM #__jlms_' . $table . ' WHERE course_version_id=' .
             $id . ' ORDER BY id')->loadAssocList();
    }
    $out['questions'] = $db->setQuery(('SELECT qq.* FROM #__jlms_quiz_questions AS qq JOIN #__jlms_quizzes '
        . 'AS q ON q.id=qq.quiz_id WHERE ' .
            'q.course_version_id=') . $id . ' ORDER BY qq.quiz_id,qq.ordering')->loadAssocList();
    return $out;
}
