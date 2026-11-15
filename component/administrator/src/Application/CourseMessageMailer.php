<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Factory;
use Joomla\CMS\Mail\MailTemplate;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateVariableCatalog;

/**
 * Renders an immutable per-job communication snapshot with Joomla's own
 * placeholder replacement, and uses Joomla's configured transport.
 * No template row is overwritten and each recipient gets a separate mailer.
 */
final class CourseMessageMailer extends MailTemplate
{
    public function __construct()
    {
        parent::__construct(CourseMessageTemplate::TEMPLATE_ID, '*');
        $this->addUnsafeTags(MailTemplateVariableCatalog::tagsRequiringHtmlEscape(CourseMessageTemplate::TEMPLATE_ID));
    }

    /** @param array<string, scalar> $data */
    public function render(string $part, array $data, bool $html = false): string
    {
        return $this->replaceTags($part, $data, $html);
    }

    /** @param array<string, mixed> $snapshot
     *  @param array<string, scalar> $data
     */
    public static function deliver(array $snapshot, array $data, string $recipient, string $name): bool
    {
        $template = CourseMessageTemplate::validate($snapshot, $data);
        $renderer = new self();
        $subject = $renderer->render($template['subject'], $data);
        $body = $renderer->render($template['body'], $data);
        $html = $template['htmlbody'] !== '' ? $renderer->render($template['htmlbody'], $data, true) : '';

        if (preg_match('/[\x00-\x1f\x7f]/', $subject) || trim($subject) === '') {
            throw new \RuntimeException('Invalid rendered course communication subject.');
        }

        $mailer = Factory::getMailer();
        $mailer->addRecipient($recipient, $name);
        $mailer->setSubject($subject);
        if ($html !== '') {
            $mailer->isHtml(true);
            $mailer->setBody($html);
            $mailer->AltBody = $body;
        } else {
            $mailer->isHtml(false);
            $mailer->setBody($body);
        }

        return (bool) $mailer->send();
    }
}
