<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Finder\AfterSaveEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Safe, explicit relocation of JD Learn records before a category is removed. */
final class CategoryReassignmentService
{
    /** @var array<string, array{table:string, extension:string, label:string}> */
    private const REFERENCES = [
        'courses' => ['table' => '#__jlms_courses', 'extension' => 'com_jlms.course', 'label' => 'COM_JLMS_COURSES'],
        'pathways' => ['table' => '#__jlms_pathways', 'extension' => 'com_jlms.pathway', 'label' => 'COM_JLMS_PATHWAYS'],
        'teachers' => ['table' => '#__jlms_teachers', 'extension' => 'com_jlms.teacher', 'label' => 'COM_JLMS_TEACHERS'],
        'questions' => ['table' => '#__jlms_questions', 'extension' => 'com_jlms.question', 'label' => 'COM_JLMS_QUESTION_BANK'],
    ];

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /** @return array{category:object, references:array<string,int>, targets:array<string,list<object>>} */
    public function inspect(User $actor, int $categoryId): array
    {
        $source = $this->category($categoryId);
        $this->assertManage($actor, $source, 'core.delete');
        $counts = [];
        $targets = [];

        foreach (self::REFERENCES as $key => $reference) {
            if ($source->extension !== $reference['extension']) {
                $counts[$key] = 0;
                $targets[$key] = [];
                continue;
            }

            $counts[$key] = $this->count($reference['table'], $categoryId);
            $query = $this->db->createQuery()
                ->select('id, title, created_user_id, lft, rgt')
                ->from('#__categories')
                ->where('extension = :extension')
                ->where('published <> -2')
                ->where('id <> :source')
                ->where('(lft < :sourceLft OR rgt > :sourceRgt)')
                ->order('lft, title');
            $query->bind(':extension', $reference['extension'])
                ->bind(':source', $categoryId, ParameterType::INTEGER)
                ->bind(':sourceLft', (int) $source->lft, ParameterType::INTEGER)
                ->bind(':sourceRgt', (int) $source->rgt, ParameterType::INTEGER);
            $this->db->setQuery($query);
            $targets[$key] = array_values(array_filter(
                $this->db->loadObjectList(),
                static function (object $target) use ($actor, $reference): bool {
                    $asset = $reference['extension'] . '.category.' . (int) $target->id;
                    return $actor->authorise('core.edit', $asset)
                        || ((int) $target->created_user_id === (int) $actor->id && $actor->authorise('core.edit.own', $asset));
                },
            ));
        }

        return ['category' => $source, 'references' => $counts, 'targets' => $targets];
    }

    /** @param array<string, mixed> $destinations */
    public function reassign(User $actor, int $categoryId, array $destinations): array
    {
        $snapshot = $this->inspect($actor, $categoryId);
        $source = $snapshot['category'];
        $resolved = [];

        foreach (self::REFERENCES as $key => $reference) {
            $count = $snapshot['references'][$key];
            if ($count === 0) {
                continue;
            }
            $destinationId = filter_var($destinations[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2]]);
            if (!$destinationId || $destinationId === $categoryId) {
                throw new \InvalidArgumentException('COM_JLMS_CATEGORY_REASSIGN_TARGET_REQUIRED');
            }
            $destination = $this->category((int) $destinationId);
            $isSourceDescendant = (int) $destination->lft > (int) $source->lft
                && (int) $destination->rgt < (int) $source->rgt;
            if ($destination->extension !== $reference['extension'] || $destination->extension !== $source->extension
                || $isSourceDescendant) {
                throw new \InvalidArgumentException('COM_JLMS_CATEGORY_REASSIGN_INVALID_TARGET');
            }
            $this->assertManage($actor, $destination, 'core.edit');
            $resolved[$key] = ['table' => $reference['table'], 'target' => (int) $destinationId, 'expected' => $count];
        }

        if ($resolved === []) {
            return $snapshot['references'];
        }

        $courseIds = [];
        if (isset($resolved['courses'])) {
            $query = $this->db->createQuery()->select('id')->from('#__jlms_courses')->where('catid = :category');
            $query->bind(':category', $categoryId, ParameterType::INTEGER);
            $courseIds = array_map('intval', $this->db->setQuery($query)->loadColumn());
        }

        $this->db->transactionStart();
        try {
            foreach ($resolved as $item) {
                $query = $this->db->createQuery()
                    ->update($this->db->quoteName($item['table']))
                    ->set($this->db->quoteName('catid') . ' = :target')
                    ->where($this->db->quoteName('catid') . ' = :source');
                $query->bind(':target', $item['target'], ParameterType::INTEGER)
                    ->bind(':source', $categoryId, ParameterType::INTEGER);
                $this->db->setQuery($query)->execute();
                if ($this->db->getAffectedRows() !== $item['expected']) {
                    throw new \RuntimeException('COM_JLMS_CATEGORY_REASSIGN_STALE');
                }
            }
            $this->db->transactionCommit();
        } catch (\Throwable $error) {
            $this->db->transactionRollback();
            throw $error;
        }

        if (isset($resolved['courses']) && ComponentHelper::isEnabled('com_finder')) {
            $this->reindexCourses($courseIds);
        }

        return $snapshot['references'];
    }

    private function count(string $table, int $categoryId): int
    {
        $query = $this->db->createQuery()->select('COUNT(*)')->from($this->db->quoteName($table))
            ->where($this->db->quoteName('catid') . ' = :category');
        $query->bind(':category', $categoryId, ParameterType::INTEGER);
        return (int) $this->db->setQuery($query)->loadResult();
    }

    private function category(int $id): object
    {
        if ($id < 2) {
            throw new \InvalidArgumentException('COM_JLMS_CATEGORY_REASSIGN_INVALID_SOURCE');
        }
        $query = $this->db->createQuery()->select('id, title, extension, asset_id, created_user_id, checked_out, lft, rgt')
            ->from('#__categories')->where('id = :id')->where('published <> -2');
        $query->bind(':id', $id, ParameterType::INTEGER);
        $category = $this->db->setQuery($query)->loadObject();
        if (!$category || !str_starts_with((string) $category->extension, 'com_jlms.')) {
            throw new \RuntimeException('COM_JLMS_CATEGORY_REASSIGN_INVALID_SOURCE', 404);
        }
        return $category;
    }

    private function assertManage(User $actor, object $category, string $action): void
    {
        $actor = clone $actor;
        $actor->clearAccessRights();
        $asset = $category->extension . '.category.' . (int) $category->id;
        $allowed = $actor->authorise($action, $asset);
        if (in_array($action, ['core.edit', 'core.delete'], true) && (int) $category->created_user_id === (int) $actor->id) {
            $allowed = $allowed || $actor->authorise($action . '.own', $asset);
        }
        if (!$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')
            || (!empty($category->checked_out) && (int) $category->checked_out !== (int) $actor->id)
            || !$allowed) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }
    }

    private function reindexCourses(array $ids): void
    {
        try {
            PluginHelper::importPlugin('finder');
            $dispatcher = Factory::getApplication()->getDispatcher();
            foreach ($ids as $id) {
                $courseQuery = $this->db->createQuery()->select('*')->from('#__jlms_courses')->where('id = :id');
                $courseQuery->bind(':id', $id, ParameterType::INTEGER);
                $course = $this->db->setQuery($courseQuery)->loadObject();
                if ($course) {
                    $dispatcher->dispatch('onFinderAfterSave', new AfterSaveEvent('onFinderAfterSave', [
                        'context' => 'com_jlms.course', 'subject' => $course, 'isNew' => false,
                    ]));
                }
            }
        } catch (\Throwable $error) {
            Log::add('Category reassignment Finder refresh failed; course count=' . count($ids), Log::WARNING, 'com_jlms');
        }
    }
}
