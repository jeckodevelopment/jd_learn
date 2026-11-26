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
        'pathways' => [
            'table' => '#__jlms_pathways',
            'extension' => 'com_jlms.pathway',
            'label' => 'COM_JLMS_PATHWAYS'
        ],
        'teachers' => [
            'table' => '#__jlms_teachers',
            'extension' => 'com_jlms.teacher',
            'label' => 'COM_JLMS_TEACHERS'
        ],
        'questions' => [
            'table' => '#__jlms_questions',
            'extension' => 'com_jlms.question',
            'label' => 'COM_JLMS_QUESTION_BANK'
        ],
    ];

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /** @return array{category:object, references:array<string,int>, descendant_references:array<string,int>, descendant_categories:int, targets:array<string,list<object>>} */
    public function inspect(User $actor, int $categoryId): array
    {
        // Never use a stale session object's permissions to advertise destinations.
        $actor = new User((int) $actor->id);
        $actor->clearAccessRights();
        $source = $this->category($categoryId, true);
        $this->assertManage($actor, $source, 'core.delete');
        $counts = [];
        $descendantCounts = [];
        $targets = [];
        // An operator inspecting a source before a native Joomla move/delete
        // must see its entire nested subtree, not only direct catid references.
        // Child records are NOT moved implicitly by the direct reassignment task.
        $descendantQuery = $this->db->createQuery()
            ->select('COUNT(*)')
            ->from('#__categories AS nested')
            ->where('nested.extension = :extension')
            ->where('nested.lft > :leftBoundary')
            ->where('nested.rgt < :rightBoundary');
        $categoryExtension = (string) $source->extension;
        $leftBoundary = (int) $source->lft;
        $rightBoundary = (int) $source->rgt;
        $descendantQuery->bind(':extension', $categoryExtension)
            ->bind(':leftBoundary', $leftBoundary, ParameterType::INTEGER)
            ->bind(':rightBoundary', $rightBoundary, ParameterType::INTEGER);
        $descendantCategories = (int) $this->db->setQuery($descendantQuery)->loadResult();

        foreach (self::REFERENCES as $key => $reference) {
            if ($source->extension !== $reference['extension']) {
                $counts[$key] = 0;
                $descendantCounts[$key] = 0;
                $targets[$key] = [];
                continue;
            }

            $counts[$key] = $this->count($reference['table'], $categoryId);
            $descendantCounts[$key] = $this->countDescendants($reference['table'], $source);
            $query = $this->db->createQuery()
                ->select('id, title, created_user_id, checked_out, lft, rgt')
                ->from('#__categories AS candidate')
                ->where('candidate.extension = :extension')
                ->where('candidate.published <> -2')
                ->where('candidate.id <> :source')
                ->where('(candidate.lft < :sourceLft OR candidate.rgt > :sourceRgt)')
                // A live child of a trashed ancestor is not a usable destination.
                ->where('NOT EXISTS (SELECT 1 FROM #__categories AS ancestor WHERE '
                    . 'ancestor.lft <= candidate.lft AND ancestor.rgt >= candidate.rgt '
                    . 'AND ancestor.id <> 1 AND ancestor.published = -2)')
                ->order('candidate.lft, candidate.title');
            $sourceLft = (int) $source->lft;
            $sourceRgt = (int) $source->rgt;
            $query->bind(':extension', $reference['extension'])
                ->bind(':source', $categoryId, ParameterType::INTEGER)
                ->bind(':sourceLft', $sourceLft, ParameterType::INTEGER)
                ->bind(':sourceRgt', $sourceRgt, ParameterType::INTEGER);
            $this->db->setQuery($query);
            $targets[$key] = array_values(array_filter(
                $this->db->loadObjectList(),
                static function (object $target) use ($actor, $reference): bool {
                    $asset = $reference['extension'] . '.category.' . (int) $target->id;
                    return (empty($target->checked_out) || (int) $target->checked_out === (int) $actor->id)
                        && ($actor->authorise('core.edit', $asset)
                            || ((int) $target->created_user_id === (int) $actor->id && $actor
                            ->authorise('core.edit.own', $asset)));
                },
            ));
        }

        return ['category' => $source, 'references' => $counts, 'descendant_references' => $descendantCounts,
            'descendant_categories' => $descendantCategories, 'targets' => $targets];
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
            $destinationId = filter_var($destinations[$key] ?? null, FILTER_VALIDATE_INT, ['options' =>
                 ['min_range' => 2]]);
            if (!$destinationId || $destinationId === $categoryId) {
                throw new \InvalidArgumentException('COM_JLMS_CATEGORY_REASSIGN_TARGET_REQUIRED');
            }
            // Preserve the original invalid-target contract for a descendant,
            // even when its source/ancestor is already in the trash.
            $destination = $this->category((int) $destinationId, true);
            $isSourceDescendant = (int) $destination->lft > (int) $source->lft
                && (int) $destination->rgt < (int) $source->rgt;
            if ($isSourceDescendant) {
                throw new \InvalidArgumentException('COM_JLMS_CATEGORY_REASSIGN_INVALID_TARGET');
            }
            // Only non-descendants can be considered destinations: now apply
            // the complete trash/ancestor gate before permissions or writes.
            $destination = $this->category((int) $destinationId);
            if (
                $destination->extension !== $reference['extension']
                || $destination->extension !== $source->extension
            ) {
                throw new \InvalidArgumentException('COM_JLMS_CATEGORY_REASSIGN_INVALID_TARGET');
            }
            $this->assertManage($actor, $destination, 'core.edit');
            $resolved[$key] = [
                'table' => $reference['table'],
                'extension' => $reference['extension'],
                'target' => (int) $destinationId,
                'expected' => $count,
            ];
        }

        if ($resolved === []) {
            return $snapshot['references'];
        }

        // Refresh ACL after inspection: account groups or blocking can change mid-session.
        $actor = new User((int) $actor->id);
        $actor->clearAccessRights();
        $courseIds = [];
        $this->db->transactionStart();
        try {
            foreach ($resolved as $key => $item) {
                // Lock and validate *every* affected record, not only the source/destination
                // category. Explicit record ACL denies and another editor's checkout win.
                $rowsQuery = $this->db->createQuery()
                    ->select('id, created_by, checked_out')
                    ->from($this->db->quoteName($item['table']))
                    ->where('catid = :source')
                    ->order('id ASC')
                    ->bind(':source', $categoryId, ParameterType::INTEGER);
                $rowsQuery->setQuery((string) $rowsQuery . ' FOR UPDATE');
                $rows = $this->db->setQuery($rowsQuery)->loadObjectList();
                if (count($rows) !== $item['expected']) {
                    throw new \RuntimeException('COM_JLMS_CATEGORY_REASSIGN_STALE');
                }
                foreach ($rows as $row) {
                    $asset = $item['extension'] . '.' . (int) $row->id;
                    $canEdit = $actor->authorise('core.edit', $asset)
                        || ((int) $row->created_by === (int) $actor->id
                            && $actor->authorise('core.edit.own', $asset));
                    if (
                        !$actor->id || $actor->block || !$canEdit
                        || ((int) $row->checked_out > 0 && (int) $row->checked_out !== (int) $actor->id)
                    ) {
                        throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
                    }
                }
                if ($key === 'courses') {
                    $courseIds = array_map(static fn (object $row): int => (int) $row->id, $rows);
                }
                $query = $this->db->createQuery()
                    ->update($this->db->quoteName($item['table']))
                    ->set($this->db->quoteName('catid') . ' = :target')
                    ->where($this->db->quoteName('catid') . ' = :source');
                $targetId = $item['target'];
                $sourceId = $categoryId;
                $query->bind(':target', $targetId, ParameterType::INTEGER)
                    ->bind(':source', $sourceId, ParameterType::INTEGER);
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

    /** Count referenced records in nested categories only; exclude the source itself. */
    private function countDescendants(string $table, object $source): int
    {
        $query = $this->db->createQuery()->select('COUNT(*)')
            ->from($this->db->quoteName($table) . ' AS item')
            ->innerJoin('#__categories AS nested ON nested.id = item.catid')
            ->where('nested.extension = :extension')
            ->where('nested.lft > :leftBoundary')
            ->where('nested.rgt < :rightBoundary');
        $extension = (string) $source->extension;
        $leftBoundary = (int) $source->lft;
        $rightBoundary = (int) $source->rgt;
        $query->bind(':extension', $extension)
            ->bind(':leftBoundary', $leftBoundary, ParameterType::INTEGER)
            ->bind(':rightBoundary', $rightBoundary, ParameterType::INTEGER);

        return (int) $this->db->setQuery($query)->loadResult();
    }

    private function category(int $id, bool $allowTrashed = false): object
    {
        if ($id < 2) {
            throw new \InvalidArgumentException('COM_JLMS_CATEGORY_REASSIGN_INVALID_SOURCE');
        }
        $query = $this->db->createQuery()
            ->select('id, title, extension, asset_id, created_user_id, checked_out, lft, rgt')
            ->from('#__categories AS candidate')->where('candidate.id = :id');
        if (!$allowTrashed) {
            $query->where('candidate.published <> -2')
                ->where('NOT EXISTS (SELECT 1 FROM #__categories AS ancestor WHERE '
                    . 'ancestor.lft <= candidate.lft AND ancestor.rgt >= candidate.rgt '
                    . 'AND ancestor.id <> 1 AND ancestor.published = -2)');
        }
        $query->bind(':id', $id, ParameterType::INTEGER);
        $category = $this->db->setQuery($query)->loadObject();
        if (!$category || !str_starts_with((string) $category->extension, 'com_jlms.')) {
            throw new \RuntimeException('COM_JLMS_CATEGORY_REASSIGN_INVALID_SOURCE', 404);
        }
        return $category;
    }

    private function assertManage(User $actor, object $category, string $action): void
    {
        // The incoming Joomla session identity can predate an account block or
        // permission revocation. Load the current database identity for each
        // authorization check instead of trusting a cloned session snapshot.
        $actor = new User((int) $actor->id);
        $actor->clearAccessRights();
        $asset = $category->extension . '.category.' . (int) $category->id;
        $allowed = $actor->authorise($action, $asset);
        if (
            in_array($action, ['core.edit', 'core.delete'], true) && (int) $category->created_user_id
            === (int) $actor->id
        ) {
            $allowed = $allowed || $actor->authorise($action . '.own', $asset);
        }
        if (
            !$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')
            || (!empty($category->checked_out) && (int) $category->checked_out !== (int) $actor->id)
            || !$allowed
        ) {
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
            Log::add('Category reassignment Finder refresh failed; course ' .
                'count=' . count($ids), Log::WARNING, 'com_jlms');
        }
    }
}
