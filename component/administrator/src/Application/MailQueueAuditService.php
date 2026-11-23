<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\CMS\Mail\MailTemplate;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplatePlaceholderValidator;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Read-only mail outbox audit with explicit, audited reconciliation of stale SMTP claims. */
final class MailQueueAuditService
{
    private const STATES = ['pending', 'retry', 'sending', 'sent', 'failed', 'skipped'];
    private const TEMPLATES = [
        'com_jlms.teacher.assigned',
        'com_jlms.enrollment.requested',
        'com_jlms.enrollment.approved',
        'com_jlms.enrollment.rejected',
        'com_jlms.enrollment.confirmed',
        'com_jlms.course.completed',
        'com_jlms.certificate.available',
        'com_jlms.enrollment.suspended',
        'com_jlms.enrollment.reactivated',
        'com_jlms.course.message',
        'com_jlms.course.published',
        'com_jlms.course.expiring',
        'com_jlms.course.consent',
    ];
    private const STALE_MINUTES = 15;

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    public function page(User $identity, array $filters = [], int $start = 0, int $limit = 50): array
    {
        $actor = CourseEditorAccess::fresh($identity);
        // The outbox is global to all courses. Component management alone can be
        // delegated for a single course and must not expose global audit data
        // or permit reconciliation of another course's mail.
        if (
            !$actor->authorise('core.manage', 'com_jlms')
            || !$actor->authorise('jlms.audit.view', 'com_jlms')
        ) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }

        $state = (string) ($filters['state'] ?? '');
        $template = (string) ($filters['template'] ?? '');
        if ($state !== '' && !in_array($state, self::STATES, true)) {
            throw new \InvalidArgumentException('COM_JLMS_MAIL_QUEUE_FILTER_INVALID');
        }
        if ($template !== '' && !in_array($template, self::TEMPLATES, true)) {
            throw new \InvalidArgumentException('COM_JLMS_MAIL_QUEUE_FILTER_INVALID');
        }

        $start = max(0, $start);
        $limit = max(10, min(100, $limit));
        [$where, $bind] = $this->filters($state, $template);
        $count = $this->db->createQuery()
            ->select('COUNT(*)')
            ->from('#__jlms_mail_queue AS q')
            ->leftJoin('#__jlms_courses AS c ON c.id=q.course_id');
        foreach ($where as $condition) {
            $count->where($condition);
        }
        foreach ($bind as $name => [$value, $type]) {
            $count->bind($name, $value, $type);
        }
        $total = (int) $this->db->setQuery($count)->loadResult();

        $query = $this->db->createQuery()->select([
            'q.id', 'q.template_id', 'q.course_id', 'c.title AS course_title', 'q.recipient_email',
            'q.language', 'q.template_data',
            'q.state', 'q.attempts', 'q.available_at', 'q.claimed_at', 'q.sent_at',
            'q.created_at', 'q.last_error', 'q.reconciliation_action', 'q.reconciliation_by', 'q.reconciliation_at',
            'q.reconciliation_note',
            '(CASE WHEN q.state = ' . $this->db->quote('sending') . ' AND q.claimed_at <= DATE_SUB(UTC_TIMESTAMP(6), INTERVAL ' . self::STALE_MINUTES . ' MINUTE) THEN 1 ELSE 0 END) AS is_stale_sending',
        ])->from('#__jlms_mail_queue AS q')->leftJoin('#__jlms_courses AS c ON c.id=q.course_id');
        foreach ($where as $condition) {
            $query->where($condition);
        }
        foreach ($bind as $name => [$value, $type]) {
            $query->bind($name, $value, $type);
        }
        $rows = $this->db->setQuery($query->order('q.created_at DESC, q.id DESC'), $start, $limit)->loadObjectList();
        foreach ($rows as $row) {
            $row->recipient_masked = self::maskEmail((string) $row->recipient_email);
            // Only show a finite diagnostic category. Never render stored exception
            // text or legacy SMTP responses (which may contain addresses or secrets).
            $row->diagnostic_key = match ((string) ($row->last_error ?? '')) {
                'Mail template is missing or contains unavailable placeholders.' => 'COM_JLMS_MAIL_QUEUE_DIAGNOSTIC_TEMPLATE',
                'Delivery failed; transport details are withheld.' => 'COM_JLMS_MAIL_QUEUE_DIAGNOSTIC_TRANSPORT',
                default => null,
            };
            // Administrative diagnostics use only token NAMES, never SMTP details,
            // recipient addresses or substituted values from template_data.
            $row->diagnostic_missing_template = false;
            $row->diagnostic_tokens = [];
            if (
                $row->diagnostic_key === 'COM_JLMS_MAIL_QUEUE_DIAGNOSTIC_TEMPLATE'
                && in_array((string) $row->state, ['retry', 'failed'], true)
            ) {
                try {
                    $mailTemplate = MailTemplate::getTemplate((string) $row->template_id, (string) $row->language)
                        ?? MailTemplate::getTemplate((string) $row->template_id, '*');
                    if ($mailTemplate === null) {
                        $row->diagnostic_missing_template = true;
                    } else {
                        $context = json_decode((string) $row->template_data, true, 32, JSON_THROW_ON_ERROR);
                        if (is_array($context)) {
                            $unresolved = MailTemplatePlaceholderValidator::unresolved(
                                (string) $row->template_id,
                                $context,
                                (string) $mailTemplate->subject,
                                (string) $mailTemplate->body,
                                (string) $mailTemplate->htmlbody
                            );
                            // Names are uppercase ASCII tokens, never values or exception messages.
                            $row->diagnostic_tokens = array_slice($unresolved, 0, 12);
                        }
                    }
                } catch (\Throwable) {
                    // Retain the existing generic diagnosis for uninspectable rows.
                }
            }
            unset($row->recipient_email, $row->last_error);
            unset($row->template_data, $row->language);
        }

        return ['rows' => $rows, 'total' => $total, 'start' => $start, 'limit' => $limit,
            'filters' => ['state' => $state, 'template' => $template], 'stale_minutes' => self::STALE_MINUTES];
    }

    public function reconcile(User $identity, int $queueId, string $action, string $note, bool $confirmed): void
    {
        $actor = CourseEditorAccess::fresh($identity);
        // The outbox is global to all courses. Component management alone can be
        // delegated for a single course and must not expose global audit data
        // or permit reconciliation of another course's mail.
        if (
            !$actor->authorise('core.manage', 'com_jlms')
            || !$actor->authorise('jlms.audit.view', 'com_jlms')
        ) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }
        $note = trim($note);
        if ($queueId < 1 || !in_array($action, ['sent', 'retry'], true) || !$confirmed || mb_strlen($note) < 10 || mb_strlen($note) > 1000) {
            throw new \InvalidArgumentException('COM_JLMS_MAIL_QUEUE_RECONCILE_INVALID');
        }

        // A template validation failure occurs before SMTP is contacted. Only
        // this explicit case is safe for a manual failed->retry transition.
        if ($action === 'retry' && $this->retryFailedTemplate($queueId, (int) $actor->id, $note)) {
            return;
        }

        $q = $this->db->createQuery()->select('id,attempts')->from('#__jlms_mail_queue')
            ->where('id = :id')->where("state = 'sending'")
            ->where('claimed_at <= DATE_SUB(UTC_TIMESTAMP(6), INTERVAL ' . self::STALE_MINUTES . ' MINUTE)')
            ->bind(':id', $queueId, ParameterType::INTEGER);
        $row = $this->db->setQuery($q)->loadObject();
        if (!$row) {
            throw new \RuntimeException('COM_JLMS_MAIL_QUEUE_NOT_RECONCILABLE', 409);
        }
        if ($action === 'retry' && (int) $row->attempts >= 5) {
            throw new \RuntimeException('COM_JLMS_MAIL_QUEUE_RECONCILE_RETRY_LIMIT', 409);
        }

        // Joomla DatabaseQuery::bind() binds by reference: only pass stable variables.
        // Expressions here caused a PHP Error before the UPDATE could be executed.
        $newState = $action === 'sent' ? 'sent' : 'retry';
        $actorId = (int) $actor->id;
        // The free-text justification already belongs to reconciliation_note.
        // Do not duplicate potentially sensitive operator text in last_error.
        $lastError = 'Manual reconciliation recorded.';
        $query = $this->db->createQuery()->update('#__jlms_mail_queue')
            ->set('state = :state')->set('reconciliation_action = :action')
            ->set('reconciliation_by = :actor')->set('reconciliation_at = UTC_TIMESTAMP(6)')
            ->set('reconciliation_note = :note')->set('last_error = :lastError')->set('claimed_at = NULL')
            ->where('id = :id')->where("state = 'sending'")
            ->where('claimed_at <= DATE_SUB(UTC_TIMESTAMP(6), INTERVAL ' . self::STALE_MINUTES . ' MINUTE)')
            ->bind(':state', $newState)
            ->bind(':action', $action)->bind(':actor', $actorId, ParameterType::INTEGER)
            ->bind(':note', $note)->bind(':lastError', $lastError)
            ->bind(':id', $queueId, ParameterType::INTEGER);

        if ($action === 'retry') {
            $query->where('attempts < 5');
        }

        if ($action === 'sent') {
            $query->set('sent_at = COALESCE(sent_at, UTC_TIMESTAMP(6))')
                ->set("recipient_email = ''")->set("recipient_name = ''")->set("template_data = '{}'");
        } else {
            $query->set('available_at = UTC_TIMESTAMP(6)');
        }

        $this->db->setQuery($query)->execute();
        if ($this->db->getAffectedRows() !== 1) {
            throw new \RuntimeException('COM_JLMS_MAIL_QUEUE_NOT_RECONCILABLE', 409);
        }
    }

    /**
     * Requeue a pre-SMTP template failure only after the Joomla template has
     * been repaired. Never retry generic transport failures or unknown sends.
     */
    private function retryFailedTemplate(int $id, int $actorId, string $note): bool
    {
        $errorMarker = 'Mail template is missing or contains unavailable placeholders.';
        $query = $this->db->createQuery()->select(
            'id, template_id, language, template_data, attempts, recipient_email'
        )->from('#__jlms_mail_queue')->where('id = :id')
            ->where("state = 'failed'")
            ->where('last_error = :marker')->where('attempts < 5')
            ->where("template_id IN ('com_jlms.enrollment.confirmed', 'com_jlms.course.completed')")
            ->bind(':id', $id, ParameterType::INTEGER)->bind(':marker', $errorMarker);
        $row = $this->db->setQuery($query)->loadObject();
        if (!$row) {
            return false;
        }

        try {
            $template = MailTemplate::getTemplate((string) $row->template_id, (string) $row->language)
                ?? MailTemplate::getTemplate((string) $row->template_id, '*');
            $values = json_decode((string) $row->template_data, true, 32, JSON_THROW_ON_ERROR);
            if (
                $template === null || !is_array($values)
                || !filter_var((string) $row->recipient_email, FILTER_VALIDATE_EMAIL)
                || MailTemplatePlaceholderValidator::unresolved(
                    (string) $row->template_id,
                    $values,
                    (string) $template->subject,
                    (string) $template->body,
                    (string) $template->htmlbody
                ) !== []
            ) {
                throw new \RuntimeException('COM_JLMS_MAIL_QUEUE_TEMPLATE_STILL_INVALID', 409);
            }
        } catch (\Throwable) {
            throw new \RuntimeException('COM_JLMS_MAIL_QUEUE_TEMPLATE_STILL_INVALID', 409);
        }

        $retry = 'retry';
        $action = 'retry';
        // The free-text justification already belongs to reconciliation_note.
        // Do not duplicate potentially sensitive operator text in last_error.
        $lastError = 'Manual reconciliation recorded.';
        $update = $this->db->createQuery()->update('#__jlms_mail_queue')
            ->set('state = :retry')->set('available_at = UTC_TIMESTAMP(6)')
            ->set('claimed_at = NULL')->set('reconciliation_action = :action')
            ->set('reconciliation_by = :actor')->set('reconciliation_at = UTC_TIMESTAMP(6)')
            ->set('reconciliation_note = :note')->set('last_error = :lastError')
            ->where('id = :id')->where("state = 'failed'")
            ->where('last_error = :marker')->where('attempts < 5')
            ->where("template_id IN ('com_jlms.enrollment.confirmed', 'com_jlms.course.completed')")
            ->bind(':retry', $retry)->bind(':action', $action)
            ->bind(':actor', $actorId, ParameterType::INTEGER)
            ->bind(':note', $note)->bind(':lastError', $lastError)
            ->bind(':id', $id, ParameterType::INTEGER)->bind(':marker', $errorMarker);
        $this->db->setQuery($update)->execute();
        if ($this->db->getAffectedRows() !== 1) {
            throw new \RuntimeException('COM_JLMS_MAIL_QUEUE_NOT_RECONCILABLE', 409);
        }
        return true;
    }

    private function filters(string $state, string $template): array
    {
        $where = [];
        $bind = [];
        if ($state !== '') {
            $where[] = 'q.state = :state';
            $bind[':state'] = [$state, ParameterType::STRING];
        }
        if ($template !== '') {
            $where[] = 'q.template_id = :template';
            $bind[':template'] = [$template, ParameterType::STRING];
        }
        return [$where, $bind];
    }

    private static function maskEmail(string $email): string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '';
        }
        [$local, $domain] = explode('@', $email, 2);
        return mb_substr($local, 0, 1) . str_repeat('*', min(8, max(3, mb_strlen($local) - 1))) . '@' . $domain;
    }
}
