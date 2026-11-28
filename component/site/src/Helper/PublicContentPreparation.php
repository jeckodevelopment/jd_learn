<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Helper;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Jlms\Component\Jlms\Administrator\Application\CoursePresentation;

/**
 * Prepare content that is already authorised for public display.
 * Never pass enrollment metadata or private lesson material to content plugins.
 */
final class PublicContentPreparation
{
    private const PUBLIC_CONTEXTS = ['com_jlms.course', 'com_jlms.teacher', 'com_jlms.category', 'com_jlms.pathway'];
    private const PREPARABLE_CONTEXTS = [
        'com_jlms.course',
        'com_jlms.teacher',
        'com_jlms.category',
        'com_jlms.pathway',
        'com_jlms.lesson',
    ];

    public static function html(string $context, int $id, string $html, ?object $source = null): string
    {
        if (!in_array($context, self::PUBLIC_CONTEXTS, true) || $id < 1) {
            throw new \InvalidArgumentException('COM_JLMS_RESOURCE_NOT_FOUND');
        }

        return self::prepare($context, $id, $html, $source);
    }

    public static function publicLessonPreview(int $id, string $html, ?object $source = null): string
    {
        return self::prepare('com_jlms.lesson', $id, $html, $source);
    }

    private static function prepare(string $context, int $id, string $html, ?object $source): string
    {
        if (!in_array($context, self::PREPARABLE_CONTEXTS, true) || $id < 1) {
            throw new \InvalidArgumentException('COM_JLMS_RESOURCE_NOT_FOUND');
        }

        // Both input and output must obey the LMS public-HTML allowlist. Plugins
        // may format display content, but cannot bypass the stored-content filter.
        $item = (object) ['id' => $id, 'text' => CoursePresentation::html($html)];
        if ($source !== null) {
            if ((int) ($source->id ?? 0) !== $id) {
                throw new \InvalidArgumentException('COM_JLMS_RESOURCE_NOT_FOUND');
            }
            // Core/third-party content plugins often inspect category and
            // language; pass only public editorial metadata, not the DB row.
            $item->catid = (int) ($source->catid ?? 0);
            $item->language = (string) ($source->language ?? '*');
            $item->title = (string) ($source->title ?? '');
        }
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
