<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Factory;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailQueue;

/** Read-only administration preview for the optional new-course announcement. */
final class CourseAnnouncementService
{
    private CatalogService $catalog;

    public function __construct(private readonly DatabaseInterface $db)
    {
        $this->catalog = new CatalogService($db);
    }

    public function preview(User $identity, int $courseId): array
    {
        $actor = CourseEditorAccess::fresh($identity);
        if ($courseId < 1) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }

        $q = $this->db->createQuery()->select('id,created_by,state,title,summary,language,current_version_id,content_access,params_json')
            ->from('#__jlms_courses')->where('id = :course')->bind(':course', $courseId, ParameterType::INTEGER);
        $course = $this->db->setQuery($q)->loadObject();
        if (!$course) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        if (!CourseEditorAccess::canEdit($actor, $course)) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }

        $result = [
            'eligible' => false,
            'course_id' => $courseId,
            'course_title' => (string) $course->title,
            'eligible_count' => 0,
            'language' => '',
            'subject' => '',
            'body_text' => '',
            'body_html' => '',
            'template_data' => [],
            'already_notified' => false,
        ];
        $existing = $this->db->createQuery()->select('state')->from('#__jlms_course_announcement_campaigns')
            ->where('course_id = :course')->bind(':course', $courseId, ParameterType::INTEGER);
        $result['already_notified'] = (bool) $this->db->setQuery($existing)->loadResult();
        if ((int) $course->state !== 1 || $course->current_version_id === null) {
            return $result;
        }

        $language = (string) $course->language;
        if ($language === '*') {
            $language = (string) Factory::getApplication()->getLanguage()->getTag();
        }
        if (!preg_match('/^[a-z]{2}-[A-Z]{2}$/', $language)) {
            $language = 'en-GB';
        }

        $guest = new User(0);
        $guest->clearAccessRights();
        try {
            $publicCourse = $this->catalog->course($guest, $language, $courseId);
        } catch (\Throwable) {
            return $result;
        }
        if (!in_array((int) $publicCourse->content_access, array_map('intval', $guest->getAuthorisedViewLevels()), true)) {
            return $result;
        }

        $outline = $this->catalog->outline($guest, $language, $courseId);
        $presentation = CoursePresentation::details((string) $course->params_json) ?? [];
        $introHtml = (string) ($presentation['description'] ?? '');
        if ($introHtml === '') {
            $introHtml = '<p>' . htmlspecialchars((string) $course->summary, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
        } else {
            $introHtml = CoursePresentation::html($introHtml);
        }
        $introText = trim(html_entity_decode(strip_tags($introHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $title = (string) $course->title;
        $titleHtml = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $siteName = (string) Factory::getApplication()->get('sitename');
        $siteNameHtml = htmlspecialchars($siteName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $courseUrl = Route::_('index.php?option=com_jlms&view=course&id=' . $courseId, false, 0, true);

        $lessonsText = '';
        $lessonsHtml = '';
        $publicPreviews = !empty($outline['public_previews']);
        foreach ($outline['modules'] as $module) {
            $items = $outline['lessons'][(int) $module->id] ?? [];
            if (!$items) {
                continue;
            }
            $lessonsHtml .= '<h3>' . htmlspecialchars((string) $module->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h3><ul>';
            foreach ($items as $lesson) {
                $lessonTitle = trim((string) $lesson->title);
                $lessonText = $lessonTitle;
                $lessonHtml = '<li>' . htmlspecialchars($lessonTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                if ($publicPreviews && (int) $lesson->preview === 1) {
                    try {
                        $preview = $this->catalog->publicLessonPreview($guest, $language, $courseId, (int) $lesson->id);
                        $description = trim(html_entity_decode(strip_tags((string) $preview->body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                        if ($description !== '') {
                            $lessonText .= "\n  " . $description;
                            $lessonHtml .= '<p>' . htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
                        }
                    } catch (\Throwable) {
                        // Omit a preview if its publication or visibility changed during rendering.
                    }
                }
                $lessonsText .= "• " . $lessonText . "\n";
                $lessonsHtml .= $lessonHtml . '</li>';
            }
            $lessonsHtml .= '</ul>';
        }
        $lessonsText = trim($lessonsText);

        $imageHtml = '';
        $image = (string) ($presentation['image'] ?? '');
        if ($image !== '') {
            $imageUrl = rtrim(Uri::root(), '/') . '/' . implode('/', array_map('rawurlencode', explode('/', $image)));
            if (str_starts_with(strtolower($imageUrl), 'https://')) {
                $alt = !empty($presentation['image_decorative']) ? '' : (string) ($presentation['image_alt'] ?? '');
                $imageHtml = '<p><img src="' . htmlspecialchars($imageUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" alt="'
                    . htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"></p>';
            }
        }

        $template = MailTemplate::getTemplate('com_jlms.course.published', $language)
            ?? MailTemplate::getTemplate('com_jlms.course.published', '*');
        if (!$template) {
            return $result;
        }

        $tags = [
            'NAME' => $language === 'it-IT' ? 'Studente' : 'Learner',
            'COURSE_TITLE' => $title,
            'COURSE_TITLE_HTML' => $titleHtml,
            'COURSE_IMAGE_HTML' => $imageHtml,
            'COURSE_INTRO_TEXT' => $introText,
            'COURSE_INTRO_HTML' => $introHtml,
            'COURSE_LESSONS_TEXT' => $lessonsText,
            'COURSE_LESSONS_HTML' => $lessonsHtml,
            'COURSE_URL' => $courseUrl,
            'SITE_NAME' => $siteName,
            'SITE_NAME_HTML' => $siteNameHtml,
            // A safe, non-working placeholder keeps the preview from performing an unsubscribe action.
            'UNSUBSCRIBE_URL' => 'https://example.invalid/jd-learn-preview-unsubscribe',
        ];
        $result['template_data'] = $tags;
        $renderer = new CourseAnnouncementPreviewRenderer('com_jlms.course.published', $language);
        $renderer->addUnsafeTags(['NAME', 'COURSE_TITLE', 'COURSE_INTRO_TEXT', 'COURSE_LESSONS_TEXT', 'COURSE_URL', 'SITE_NAME', 'UNSUBSCRIBE_URL']);
        $result['subject'] = $renderer->render((string) $template->subject, $tags);
        $result['body_text'] = $renderer->render((string) $template->body, $tags);
        $result['body_html'] = $renderer->render((string) $template->htmlbody, $tags, true);
        $result['eligible'] = true;
        $result['eligible_count'] = $this->confirmedRecipientCount();
        $result['language'] = $language;
        return $result;
    }


    /** Explicitly authorize and queue the first announcement for an eligible public course. */
    public function notify(User $identity, int $courseId): int
    {
        $preview = $this->preview($identity, $courseId);
        if (empty($preview['eligible'])) {
            throw new \RuntimeException('COM_JLMS_COURSE_ANNOUNCEMENT_NOT_ELIGIBLE');
        }
        if (!empty($preview['already_notified'])) {
            throw new \RuntimeException('COM_JLMS_COURSE_ANNOUNCEMENT_ALREADY_QUEUED');
        }

        $recipients = $this->confirmedRecipients();
        if (!$recipients) {
            throw new \RuntimeException('COM_JLMS_COURSE_ANNOUNCEMENT_NO_RECIPIENTS');
        }

        $actor = CourseEditorAccess::fresh($identity);
        $campaign = $this->db->createQuery()->insert('#__jlms_course_announcement_campaigns')
            ->columns(['course_id','requested_by','recipient_count','state','requested_at'])
            ->values(implode(',', [(string) $courseId, (string) (int) $actor->id, (string) count($recipients), $this->db->quote('queued'), 'UTC_TIMESTAMP(6)']));
        $queue = new MailQueue($this->db);
        $consent = new CourseAnnouncementConsent($this->db);
        $queued = 0;
        $this->db->transactionStart();
        try {
            $this->db->setQuery($campaign)->execute();
            foreach ($recipients as $recipient) {
                $unsubscribe = $consent->createUnsubscribeUrl((int) $recipient->id);
                if (!$unsubscribe) {
                    continue;
                }
                $data = $preview['template_data'];
                $name = htmlspecialchars((string) $recipient->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $data['NAME'] = $name;
                $data['UNSUBSCRIBE_URL'] = $unsubscribe;
                $data['UNSUBSCRIBE_URL_HTML'] = htmlspecialchars($unsubscribe, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $data['UNSUBSCRIBE_URL_TEXT'] = $unsubscribe;
                $profile = json_decode((string) $recipient->params, true);
                $language = is_array($profile) && in_array($profile['language'] ?? '', ['it-IT','en-GB'], true)
                    ? (string) $profile['language'] : (string) $preview['language'];
                $key = hash('sha256', 'jd-learn-course-announcement:' . $courseId . ':' . (int) $recipient->id);
                if ($queue->enqueueAnnouncement(
                    $courseId,
                    (int) $recipient->id,
                    (string) $recipient->confirmed_at,
                    $key,
                    $language,
                    (string) $recipient->email,
                    (string) $recipient->name,
                    $data
                )) {
                    $queued++;
                }
            }
            if ($queued < 1) {
                throw new \RuntimeException('COM_JLMS_COURSE_ANNOUNCEMENT_NO_RECIPIENTS');
            }
            $countUpdate = $this->db->createQuery()->update('#__jlms_course_announcement_campaigns')
                ->set('recipient_count = :count')->where('course_id = :course')
                ->bind(':count', $queued, ParameterType::INTEGER)->bind(':course', $courseId, ParameterType::INTEGER);
            $this->db->setQuery($countUpdate)->execute();
            $this->db->transactionCommit();
            return $queued;
        } catch (\Throwable $error) {
            $this->db->transactionRollback();
            if (stripos($error->getMessage(), 'Duplicate') !== false || stripos($error->getMessage(), '1062') !== false) {
                throw new \RuntimeException('COM_JLMS_COURSE_ANNOUNCEMENT_ALREADY_QUEUED');
            }
            throw $error;
        }
    }

    /** @return array<int, object> */
    private function confirmedRecipients(): array
    {
        $query = $this->db->createQuery()->select('u.id,u.name,u.email,u.params,consent.confirmed_at')
            ->from('#__jlms_mail_consents AS consent')->innerJoin('#__users AS u ON u.id = consent.user_id')
            ->where("consent.state = 'confirmed'")->where('consent.confirmed_at IS NOT NULL')
            ->where('u.block = 0')->order('u.id ASC');
        $rows = $this->db->setQuery($query)->loadObjectList();
        return array_values(array_filter($rows, static fn (object $row): bool => filter_var((string) $row->email, FILTER_VALIDATE_EMAIL)));
    }

    /** Count only confirmed, unblocked accounts with syntactically valid email addresses. */
    private function confirmedRecipientCount(): int
    {
        $count = 0;
        $after = 0;
        do {
            $q = $this->db->createQuery()->select('u.id,u.email')
                ->from('#__jlms_mail_consents AS consent')
                ->innerJoin('#__users AS u ON u.id = consent.user_id')
                ->where("consent.state = 'confirmed'")->where('u.block = 0')->where('u.id > :after')
                ->bind(':after', $after, ParameterType::INTEGER)->order('u.id');
            $rows = $this->db->setQuery($q, 0, 500)->loadObjectList();
            foreach ($rows as $row) {
                $after = (int) $row->id;
                if (filter_var((string) $row->email, FILTER_VALIDATE_EMAIL)) {
                    $count++;
                }
            }
        } while (count($rows) === 500);

        return $count;
    }
}
