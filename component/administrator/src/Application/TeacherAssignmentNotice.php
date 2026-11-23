<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailQueue;

/** Operational notice to an actual linked Joomla teacher account, not a public teacher profile. */
final class TeacherAssignmentNotice
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /** Run inside the course edit transaction, after committing the teacher relation rows. */
    public function queueAdded(int $courseId, array $previous, array $selected): void
    {
        $added = array_values(array_diff(array_map('intval', $selected), array_map('intval', $previous)));
        if (!$added || $courseId < 1) {
            return;
        }
        $courseQ = $this->db->createQuery()->select('title, language, revision')
            ->from('#__jlms_courses')->where('id = :course')
            ->bind(':course', $courseId, ParameterType::INTEGER);
        $course = $this->db->setQuery($courseQ)->loadObject();
        if (!$course) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        $queue = new MailQueue($this->db);
        $notified = [];
        foreach ($added as $teacherId) {
            $query = $this->db->createQuery()->select('u.id, u.name, u.email, u.params, t.title AS teacher_name')
                ->from('#__jlms_course_teachers AS ct')
                ->innerJoin('#__jlms_teachers AS t ON t.id = ct.teacher_id')
                ->innerJoin('#__users AS u ON u.id = t.user_id')
                ->where('ct.course_id = :course')->where('ct.teacher_id = :teacher')
                ->where('u.block = 0')
                ->bind(':course', $courseId, ParameterType::INTEGER)
                ->bind(':teacher', $teacherId, ParameterType::INTEGER);
            $recipient = $this->db->setQuery($query)->loadObject();
            if (!$recipient || isset($notified[(int) $recipient->id])) {
                continue; // Unlinked, blocked or duplicate teacher account.
            }
            $notified[(int) $recipient->id] = true;
            if (!filter_var((string) $recipient->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $userParams = json_decode((string) ($recipient->params ?: '{}'), true);
            $language = is_array($userParams) ? (string) ($userParams['language'] ?? '') : '';
            $language = $language ?: (string) $course->language;
            $name = trim((string) $recipient->name);
            $queue->enqueue(
                $courseId,
                hash('sha256', 'teacher.assigned:' . $courseId . ':' . (int) $course->revision . ':'
                    . (int) $recipient->id),
                'com_jlms.teacher.assigned',
                $language,
                (string) $recipient->email,
                $name,
                ['USER_FULLNAME' => $name, 'COURSE_TITLE' => (string) $course->title,
                    'TEACHER_NAME' => trim((string) $recipient->teacher_name),
                    'SITE_NAME' => (string) Factory::getApplication()->get('sitename')],
                (int) $recipient->id
            );
        }
    }
}
