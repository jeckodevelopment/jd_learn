<?php

declare(strict_types=1);

namespace Jlms\Plugin\Finder\Jlms\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Finder as FinderEvent;
use Joomla\Component\Finder\Administrator\Indexer\Adapter;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\QueryInterface;
use Joomla\Event\SubscriberInterface;

final class Jlms extends Adapter implements SubscriberInterface
{
    use DatabaseAwareTrait;

    protected $context = 'jlms';
    protected $extension = 'com_jlms';
    protected $layout = 'course';
    protected $type_title = 'Course';
    protected $table = '#__jlms_courses';
    protected $state_field = 'state';
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return array_merge(parent::getSubscribedEvents(), [
            'onFinderAfterSave' => 'onFinderAfterSave',
            'onFinderAfterDelete' => 'onFinderAfterDelete',
            'onFinderChangeState' => 'onFinderChangeState',
            'onFinderCategoryChangeState' => 'onFinderCategoryChangeState',
        ]);
    }

    public function onFinderAfterSave(FinderEvent\AfterSaveEvent $event): void
    {
        if ($event->getContext() === 'com_jlms.course') {
            $this->refreshCourse((int) $event->getItem()->id);
            return;
        }
        if (
            $event->getContext() === 'com_categories.category'
            && ($event->getItem()->extension ?? '') === 'com_jlms.course'
        ) {
            $this->reindexAllCourses();
        }
    }

    public function onFinderAfterDelete(FinderEvent\AfterDeleteEvent $event): void
    {
        if ($event->getContext() === 'com_jlms.course') {
            $this->remove((int) $event->getItem()->id);
        } elseif (
            $event->getContext() === 'com_categories.category'
            && ($event->getItem()->extension ?? '') === 'com_jlms.course'
        ) {
            $this->reindexAllCourses();
        }
    }

    public function onFinderChangeState(FinderEvent\AfterChangeStateEvent $event): void
    {
        if ($event->getContext() === 'com_jlms.course') {
            foreach ($event->getPks() as $id) {
                $this->refreshCourse((int) $id);
            }
        } elseif ($event->getContext() === 'com_categories.category') {
            $ids = array_map('intval', $event->getPks());
            if ($ids) {
                $db = $this->getDatabase();
                $q = $db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__categories'))
                    ->where('id IN (' . implode(',', $ids) . ')')->where('extension = ' . $db
                        ->quote('com_jlms.course'));
                if ((int) $db->setQuery($q)->loadResult() > 0) {
                    $this->reindexAllCourses();
                }
            }
        } elseif ($event->getContext() === 'com_plugins.plugin' && (int) $event->getValue() === 0) {
            $this->pluginDisable($event->getPks());
        }
    }

    /**
     * Joomla emits a distinct Finder category-state event for bulk publish,
     * unpublish and trash. Without this subscription, an already indexed
     * course can remain visible after its category is unpublished.
     */
    public function onFinderCategoryChangeState(FinderEvent\AfterCategoryChangeStateEvent $event): void
    {
        if (!in_array($event->getExtension(), ['com_jlms.course', 'com_jlms'], true)) {
            return;
        }

        if (!$event->getPks()) {
            return;
        }

        // The candidate query resolves all ancestors, so refreshing the
        // catalogue also invalidates descendants of a changed category.
        $this->reindexAllCourses();
    }

    /** Keep stored Finder links aligned when a course becomes noindex or inaccessible. */
    private function refreshCourse(int $id): void
    {
        if ($id < 1) {
            return;
        }
        $query = $this->getListQuery()->where('a.id = ' . $id);
        $visible = (bool) $this->getDatabase()->setQuery($query, 0, 1)->loadResult();
        if ($visible) {
            $this->reindex($id);
        } else {
            // Reindex cannot index a record filtered out by ACL/publication constraints.
            // Remove any previously published URL instead of retaining a stale search hit.
            $this->remove($id);
        }
    }

    private function reindexAllCourses(): void
    {
        $db = $this->getDatabase();
        $after = 0;
        // Reindex native category changes without loading the whole catalogue in
        // memory. Keyset pagination also avoids OFFSET shifts as rows are removed.
        do {
            $query = $db->createQuery()
                ->select($db->quoteName('a.id'))
                ->from($db->quoteName($this->table, 'a'))
                ->where($db->quoteName('a.id') . ' > :after')
                ->bind(':after', $after, \Joomla\Database\ParameterType::INTEGER)
                ->order($db->quoteName('a.id') . ' ASC');
            $ids = $db->setQuery($query, 0, 500)->loadColumn();
            foreach ($ids as $id) {
                $after = (int) $id;
                $this->refreshCourse($after);
            }
        } while (count($ids) === 500);
    }

    protected function index(Result $item): void
    {
        if (!ComponentHelper::isEnabled($this->extension)) {
            return;
        }
        $item->url = $this->getUrl($item->id, $this->extension, $this->layout);
        $item->route = $item->url;
        $item->access = 1;
        $item->state = (int) $item->state === 1 ? 1 : 0;
        $item->addTaxonomy('Type', $this->type_title);
        $item->addTaxonomy('Category', (string) $item->category_title);
        if ($item->language !== '*') {
            $item->addTaxonomy('Language', (string) $item->language);
        }
        $this->indexer->index($item);
    }

    protected function setup(): bool
    {
        return true;
    }

    protected function getListQuery($query = null): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $query instanceof QueryInterface ? $query : $db->createQuery();
        $all = '*';
        $extension = 'com_jlms.course';
        // A language-neutral course within an en-GB/it-IT category has that
        // category's effective language in Smart Search, not a global '*' hit.
        $effectiveLanguage = 'CASE WHEN a.language = ' . $db->quote($all)
            . ' THEN cat.language ELSE a.language END';
        $query->select($db->quoteName([
                'a.id', 'a.title', 'a.summary', 'a.state', 'a.catid', 'a.catalog_access',
                'a.content_access',
                'a.current_version_id', 'a.publish_up', 'a.publish_down', 'cat.title', 'cat.lft', 'cat.rgt',
            ], ['id', 'title', 'summary', 'state', 'catid', 'catalog_access', 'content_access',
            'current_version_id', 'publish_up', 'publish_down', 'category_title', 'category_lft', 'category_rgt']))
            ->select($effectiveLanguage . ' AS ' . $db->quoteName('language'))
            ->from($db->quoteName($this->table, 'a'))
            ->innerJoin($db->quoteName('#__categories', 'cat') . ' ON cat.id = a.catid AND cat.extension = '
                . $db->quote($extension))
            ->innerJoin($db->quoteName('#__jlms_course_versions', 'v')
                . ' ON v.id = a.current_version_id AND v.course_id = a.id AND v.status = ' . $db->quote('published'))
            // Finder exposes only the same fully-public courses that receive canonical/share metadata.
            // Catalog-only promotional cards with protected lesson access remain outside search indexes.
            ->where('a.state = 1')->where('a.catalog_access = 1')->where('a.content_access = 1')
                ->where('cat.published = 1')->where('cat.access = 1')
            ->where('(cat.language = ' . $db->quote($all) . ' OR cat.language = a.language'
                . ' OR a.language = ' . $db->quote($all) . ')')
            // Scheduled publication boundaries cannot be maintained by a static search index.
            ->where('(a.publish_up IS NULL OR a.publish_up = ' . $db->quote('0000-00-00 00:00:00') . ')')
            ->where('(a.publish_down IS NULL OR a.publish_down = ' . $db->quote('0000-00-00 00:00:00') . ')');
        $ancestor = $db->createQuery()->select('1')->from($db->quoteName('#__categories', 'ancestor'))
            ->where('ancestor.lft <= cat.lft AND ancestor.rgt >= cat.rgt AND ancestor.id <> 1')
            ->where('(ancestor.extension <> ' . $db->quote($extension)
                . ' OR ancestor.published <> 1 OR ancestor.access <> 1'
                . ' OR (ancestor.language <> ' . $db->quote($all)
                . ' AND ancestor.language <> ' . $effectiveLanguage . '))');
        $query->where('NOT EXISTS (' . $ancestor . ')');
        return $query;
    }

    protected function getStateQuery(): QueryInterface
    {
        $db = $this->getDatabase();
        return $db->createQuery()->select($db->quoteName(['a.id', 'a.state'], ['id', 'state']))
            ->from($db->quoteName($this->table, 'a'));
    }

    protected function translateState($item, $category = null): int
    {
        return (int) $item === 1 ? 1 : 0;
    }
}
