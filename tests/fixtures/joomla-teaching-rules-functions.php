<?php

declare(strict_types=1);

function ruleExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException($message);
    }
    $checks++;
}

function ruleDenied(callable $work, string $key): void
{
    try {
        $work();
    } catch (DomainException $error) {
        ruleExpect($error->getMessage() === $key, 'Expected ' . $key . ', got ' . $error->getMessage());
        return;
    }throw new RuntimeException('Expected ' . $key);
}

function ruleRow(string $table, int $id): object
{
    global $db;
    return $db->setQuery('SELECT * FROM #__jlms_' . $table . ' WHERE id=' . $id)->loadObject();
}

function ruleLearning(?int $course = null): array
{
    global $db;
    if ($course !== null) {
        return [$db->setQuery('SELECT * FROM #__jlms_enrollments WHERE course_id=' . $course . ' ORDER BY id')
            ->loadAssocList(),$db->setQuery(('SELECT a.* FROM #__jlms_attempts a JOIN #__jlms_enrollments e ON '
                . 'e.id=a.enrollment_id WHERE e.course_id=') . $course . ' ORDER BY a.id')->
                    loadAssocList(),$db->setQuery('SELECT p.* FROM #__jlms_lesson_progress p JOIN ' .
                        '#__jlms_enrollments e ON e.id=p.enrollment_id WHERE ' .
                        'e.course_id=' . $course . ' ORDER BY p.enrollment_id,p.lesson_id')->loadAssocList()];
    }return [$db->setQuery('SELECT * FROM #__jlms_enrollments ORDER BY id')->loadAssocList(),$db
        ->setQuery('SELECT * FROM #__jlms_attempts ORDER BY id')->loadAssocList(),$db
            ->setQuery('SELECT * FROM #__jlms_lesson_progress ORDER BY enrollment_id,lesson_id')->loadAssocList()];
}
