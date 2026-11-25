<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Helper;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Jlms\Component\Jlms\Administrator\Application\CoursePresentation;

/**
 * Prepare only promotional content already authorised for the current visitor.
 * Never pass lessons, enrollment metadata or other private material to plugins.
 */
final class PublicContentPreparation
{
    public static function html(string $context, int $id, string $html): string
    {
        if (!in_array($context, ['com_jlms.course', 'com_jlms.teacher'], true) || $id < 1) {
            throw new \InvalidArgumentException('COM_JLMS_RESOURCE_NOT_FOUND');
        }

        // Both input and output must obey the LMS public-HTML allowlist. Plugins
        // may format display content, but cannot bypass the stored-content filter.
        $item = (object) ['id' => $id, 'text' => CoursePresentation::html($html)];
        PluginHelper::importPlugin('content');
        Factory::getApplication()->getDispatcher()->dispatch(
            'onContentPrepare',
            new ContentPrepareEvent('onContentPrepare', [
                'context' => $context,
                'subject' => $item,
                'params' => new Registry(),
                'page' => 0,
            ])
        );

        if (!is_string($item->text)) {
            throw new \UnexpectedValueException('COM_JLMS_RESOURCE_NOT_FOUND');
        }

        return CoursePresentation::html($item->text);
    }
}
