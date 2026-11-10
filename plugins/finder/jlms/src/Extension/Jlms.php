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

defined('_JEXEC') or die;

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
        ]);
    }

    public function onFinderAfterSave(FinderEvent\AfterSaveEvent $event): void
    {
        if ($event->getContext() === 'com_jlms.course') {
            $this->reindex((int) $event->getItem()->id);
            return;
        }
        if ($event->getContext() === 'com_categories.category'
            && ($event->getItem()->extension ?? '') === 'com_jlms.course') {
            $this->reindexAllCourses();
        }
    }

    public function onFinderAfterDelete(FinderEvent\AfterDeleteEvent $event): void
    {
        if ($event->getContext() === 'com_jlms.course') {
            $this->remove((int) $event->getItem()->id);
        } elseif ($event->getContext() === 'com_categories.category'
            && ($event->getItem()->extension ?? '') === 'com_jlms.course') {
            $this->reindexAllCourses();
        }
    }

    public function onFinderChangeState(FinderEvent\AfterChangeStateEvent $event): void
    {
        if ($event->getContext() === 'com_jlms.course') {
            foreach ($event->getPks() as $id) { $this->reindex((int) $id); }
        } elseif ($event->getContext() === 'com_categories.category') {
            $ids = array_map('intval', $event->getPks());
            if ($ids) {
                $db = $this->getDatabase();
                $q = $db->createQuery()->select('COUNT(*)')->from($db->quoteName('#__categories'))
                    ->where('id IN (' . implode(',', $ids) . ')')->where('extension = ' . $db->quote('com_jlms.course'));
                if ((int) $db->setQuery($q)->loadResult() > 0) { $this->reindexAllCourses(); }
            }
        } elseif ($event->getContext() === 'com_plugins.plugin' && (int) $event->getValue() === 0) {
            $this->pluginDisable($event->getPks());
        }
    }

    private function reindexAllCourses(): void
    {
        $q = $this->getDatabase()->createQuery()->select('a.id')->from($this->getDatabase()->quoteName($this->table, 'a'));
        foreach ($this->getDatabase()->setQuery($q)->loadColumn() as $id) { $this->reindex((int) $id); }
    }

    protected function index(Result $item): void
    {
        if (!ComponentHelper::isEnabled($this->extension)) { return; }
        $item->url = $this->getUrl($item->id, $this->extension, $this->layout);
        $item->route = $item->url;
        $item->access = 1;
        $item->state = (int) $item->state === 1 ? 1 : 0;
        $item->addTaxonomy('Type', $this->type_title);
        $item->addTaxonomy('Category', (string) $item->category_title);
        if ($item->language !== '*') { $item->addTaxonomy('Language', (string) $item->language); }
        $this->indexer->index($item);
    }

    protected function setup(): bool { return true; }

    protected function getListQuery($query = null): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $query instanceof QueryInterface ? $query : $db->createQuery();
        $all = '*';
        $extension = 'com_jlms.course';
        $query->select($db->quoteName([
                'a.id', 'a.title', 'a.summary', 'a.state', 'a.language', 'a.catid', 'a.catalog_access',
                'a.current_version_id', 'a.publish_up', 'a.publish_down', 'cat.title', 'cat.lft', 'cat.rgt',
            ], ['id', 'title', 'summary', 'state', 'language', 'catid', 'catalog_access', 'current_version_id', 'publish_up', 'publish_down', 'category_title', 'category_lft', 'category_rgt']))
            ->from($db->quoteName($this->table, 'a'))
            ->innerJoin($db->quoteName('#__categories', 'cat') . ' ON cat.id = a.catid AND cat.extension = ' . $db->quote($extension))
            ->innerJoin($db->quoteName('#__jlms_course_versions', 'v') . ' ON v.id = a.current_version_id AND v.course_id = a.id AND v.status = ' . $db->quote('published'))
            ->where('a.state = 1')->where('a.catalog_access = 1')->where('cat.published = 1')->where('cat.access = 1')
            ->where('(cat.language = ' . $db->quote($all) . ' OR cat.language = a.language)')
            // Scheduled publication boundaries cannot be maintained by a static search index.
            ->where('(a.publish_up IS NULL OR a.publish_up = ' . $db->quote('0000-00-00 00:00:00') . ')')
            ->where('(a.publish_down IS NULL OR a.publish_down = ' . $db->quote('0000-00-00 00:00:00') . ')');
        $ancestor = $db->createQuery()->select('1')->from($db->quoteName('#__categories', 'ancestor'))
            ->where('ancestor.lft <= cat.lft AND ancestor.rgt >= cat.rgt AND ancestor.id <> 1')
            ->where('(ancestor.extension <> ' . $db->quote($extension) . ' OR ancestor.published <> 1 OR ancestor.access <> 1'
                . ' OR (ancestor.language <> ' . $db->quote($all) . ' AND ancestor.language <> a.language))');
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
