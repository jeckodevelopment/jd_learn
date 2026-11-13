<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\Language\Text;

/** Uses Joomla's native mail-template tag replacement without invoking a mail transport. */
final class CourseAnnouncementPreviewRenderer extends MailTemplate
{
    public function render(string $source, array $tags, bool $html = false): string
    {
        return $this->replaceTags(Text::_($source), $tags, $html);
    }
}
