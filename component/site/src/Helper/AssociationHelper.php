<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Live, access-filtered links consumed by Joomla Language Filter and mod_languages. */
final class AssociationHelper
{
    public static function getAssociations(int $id = 0, ?string $view = null): array
    {
        $app = Factory::getApplication();
        $view ??= $app->getInput()->getCmd('view', '');
        $id = $id ?: $app->getInput()->getInt('id', 0);
        if ($view !== 'course' || $id < 1 || !Associations::isEnabled()) {
            return [];
        }
        $actor = $app->getIdentity();
        if ((int) $actor->id) { $actor = new \Joomla\CMS\User\User((int) $actor->id); }
        $actor->clearAccessRights();
        $levels = $actor->block ? [0] : array_map('intval', $actor->getAuthorisedViewLevels());
        $catalog = $app->bootComponent('com_jlms')->getCatalogService();
        try {
            $source = $catalog->course($actor, $app->getLanguage()->getTag(), $id);
        } catch (\RuntimeException $error) {
            if ($error->getCode() !== 404) { throw $error; }
            return [];
        }
        if ($source->language === '*') { return []; }
        // Do not use the core static association cache here: changes/revocations must be current.
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $context = 'com_jlms.item';
        $query = $db->createQuery()->select('c.id, c.language')->from('#__associations AS a')
            ->innerJoin('#__associations AS b ON b.' . $db->quoteName('key') . ' = a.' . $db->quoteName('key') . ' AND b.context = a.context')
            ->innerJoin('#__jlms_courses AS c ON c.id = b.id')
            ->where('a.context = :context')->where('a.id = :id')->where('c.id <> :source')
            ->bind(':context', $context)->bind(':id', $id, ParameterType::INTEGER)->bind(':source', $id, ParameterType::INTEGER);
        $items = $db->setQuery($query)->loadObjectList();
        $languageQuery = $db->createQuery()->select('lang_code, sef')->from('#__languages')->where('published = 1')->whereIn('access', $levels ?: [0]);
        $languages = $db->setQuery($languageQuery)->loadObjectList('lang_code');
        $links = [];
        foreach ($items as $item) {
            $tag = (string) $item->language;
            if ($tag === '*' || $tag === $source->language || !isset($languages[$tag])) { continue; }
            try {
                $catalog->course($actor, $tag, (int) $item->id);
            } catch (\RuntimeException $error) {
                if ($error->getCode() !== 404) { throw $error; }
                continue;
            }
            // Never carry a source Itemid, enrollment, lesson or attempt into another language.
            $links[$tag] = 'index.php?option=com_jlms&view=course&id=' . (int) $item->id . '&lang=' . rawurlencode($languages[$tag]->sef);
        }
        return $links;
    }
}
