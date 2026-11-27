<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\User\User;
use Joomla\CMS\Versioning\VersionableModelInterface;
use Joomla\CMS\Versioning\VersionableModelTrait;
use Joomla\CMS\Versioning\VersionableModelInterface;
use Joomla\CMS\Versioning\VersionableModelTrait;
use Jlms\Component\Jlms\Administrator\Application\CourseArtwork;
use Joomla\Database\ParameterType;

final class PathwayModel extends AdminModel implements \Joomla\CMS\Versioning\VersionableModelInterface
{
    use \Joomla\CMS\Versioning\VersionableModelTrait {
        saveHistory as private persistHistory;
    }

    public $typeAlias = 'com_jlms.pathway';

    protected function versionHistoryEnabled(string $context): bool
    {
        return (bool) ComponentHelper::getParams('com_jlms')->get('save_history', 1);
    }

    public function saveHistory(array $data, string $context)
    {
        return true;
    }

    public function loadHistory(int $historyId)
    {
        $history = $this->getHistoryTable($historyId);
        if (!$history) {
            return false;
        }
        $data = json_decode($history->version_data, true, 64, JSON_THROW_ON_ERROR);
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1 || $history->item_id !== $this->typeAlias . '.' . $id) {
            return false;
        }
        $current = $this->getItem($id);
        if (!$current) {
            return false;
        }
        $data['revision'] = (int) $current->revision;
        $artwork = json_decode((string) ($data['params_json'] ?? '{}'), true, 16, JSON_THROW_ON_ERROR);
        $data['cover_image'] = (string) ($artwork['cover_image'] ?? '');
        $data['cover_alt'] = (string) ($artwork['cover_alt'] ?? '');
        $data['cover_decorative'] = !empty($artwork['cover_decorative']) ? '1' : '0';
        return $this->save($data);
    }


    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm(
            'com_jlms.pathway',
            JPATH_ADMINISTRATOR . '/components/com_jlms/forms/pathway.xml',
            ['control' => 'jform', 'load_data' => $loadData]
        );
    }

    protected function loadFormData()
    {
        return Factory::getApplication()->getUserState('com_jlms.edit.pathway.data', []) ?: $this->getItem();
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);
        if ($item && !empty($item->id)) {
            $item->tags = new \Joomla\CMS\Helper\TagsHelper();
            $item->tags->getTagIds((int) $item->id, $this->typeAlias);
            $pathwayId = (int) $item->id;
            $query = $this->getDatabase()->createQuery()->select('course_id, ordering')->from('#__jlms_pathway_courses')
                ->where('pathway_id = :pathway')
                    ->bind(':pathway', $pathwayId, ParameterType::INTEGER)->order('ordering, course_id');
            $item->courses = $this->getDatabase()->setQuery($query, 0, 100)->loadAssocList();
            $imageData = json_decode((string) ($item->params_json ?? '{}'), true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($imageData)) {
                throw new \RuntimeException('COM_JLMS_PATHWAY_INVALID');
            }
            $item->cover_image = (string) ($imageData['cover_image'] ?? '');
            $item->cover_alt = (string) ($imageData['cover_alt'] ?? '');
            $item->cover_decorative = !empty($imageData['cover_decorative']) ? '1' : '0';
        } else {
            $item->courses = [];
        }
        return $item;
    }

    public function save($input)
    {
        \Joomla\CMS\Plugin\PluginHelper::importPlugin('behaviour', 'taggable');
        $db = $this->getDatabase();
        $preparedAsset = 0;
        $transactionStarted = false;
        $assetTable = null;
        $stage = 'identity';
        try {
            $actor = new User((int) $this->getCurrentUser()->id);
            $actor->clearAccessRights();
            $this->setCurrentUser($actor);
            if (
                !$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')
                || !$actor->authorise('jlms.pathways.manage', 'com_jlms')
            ) {
                throw new \DomainException('JERROR_ALERTNOAUTHOR');
            }

            // Joomla's nested asset store uses table locks. Reserve a placeholder
            // before beginning the data transaction, then rename it atomically
            // after the pathway row has its primary key.
            $stage = 'prepare-asset';
            if (!(int) ($input['id'] ?? 0)) {
                $categoryId = $input['catid'] ?? null;
                if (
                    (!is_int($categoryId) && !is_string($categoryId))
                    || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $categoryId)
                ) {
                    throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
                }
                $categoryId = (int) $categoryId;
                $categoryQuery = $db->createQuery()->select('cat.id, cat.asset_id')
                    ->from('#__categories AS cat')
                    ->where("cat.extension='com_jlms.pathway' AND cat.published <> -2")
                    ->where('cat.id = :category')
                    ->where('NOT EXISTS (SELECT 1 FROM #__categories AS ancestor'
                        . ' WHERE ancestor.lft <= cat.lft AND ancestor.rgt >= cat.rgt AND ancestor.published = -2)')
                    ->bind(':category', $categoryId, ParameterType::INTEGER);
                $category = $db->setQuery($categoryQuery)->loadObject()
                    ?: throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
                if (!$actor->authorise('core.create', 'com_jlms.pathway.category.' . $categoryId)) {
                    throw new \DomainException('JERROR_ALERTNOAUTHOR');
                }
                $assetTable = new \Joomla\CMS\Table\Asset($db);
                $assetTable->setLocation((int) $category->asset_id, 'last-child');
                $assetTable->bind([
                    'name' => 'com_jlms.pathway.tmp.' . bin2hex(random_bytes(12)),
                    'title' => 'JD Learn pathway draft',
                    'rules' => '{}',
                ]);
                $stage = 'asset-check';
                if (!$assetTable->check()) {
                    throw new \RuntimeException('pathway asset check: ' . $assetTable->getError());
                }
                $stage = 'asset-store';
                if (!$assetTable->store()) {
                    throw new \RuntimeException('pathway asset store: ' . $assetTable->getError());
                }
                $preparedAsset = (int) $assetTable->id;
            }

            $stage = 'begin-transaction';
            $db->transactionStart();
            $transactionStarted = true;
            $db->setQuery("SELECT id FROM #__assets WHERE name='com_jlms' FOR UPDATE")->loadResult()
                ?: throw new \DomainException('JERROR_ALERTNOAUTHOR');

            $allowed = [
                'id',
                'revision',
                'title',
                'alias',
                'description',
                'catid',
                'access',
                'language',
                'state',
                'courses',
                'tags',
                'cover_image',
                'cover_alt',
                'cover_decorative'
            ];
            $data = array_intersect_key($input, array_flip($allowed));
            foreach (['id', 'revision', 'catid', 'access', 'state'] as $key) {
                if (
                    isset($data[$key]) && ((!is_int($data[$key]) && !is_string($data[$key]))
                    || !preg_match(
                        $key === 'state' ? '/^(?:-2|[0-9]{1,10})$/D' : '/^[0-9]{1,10}$/D',
                        (string) $data[$key]
                    ))
                ) {
                    throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
                }
            }

            $id = (int) ($data['id'] ?? 0);
            $record = null;
            if ($id) {
                $query = $db->createQuery()->select('*')->from('#__jlms_pathways')
                    ->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
                $query->setQuery((string) $query . ' FOR UPDATE');
                $record =
                    $db->setQuery($query)->loadObject() ?: throw new \DomainException('JERROR_LAYOUT_PAGE_NOT_FOUND');
                if (
                    !$actor->authorise('core.edit', 'com_jlms.pathway.' . $id)
                    || ((int) $record->checked_out > 0 && (int) $record->checked_out !== (int) $actor->id)
                ) {
                    throw new \DomainException('JERROR_ALERTNOAUTHOR');
                }
                if ((int) ($data['revision'] ?? 0) !== (int) $record->revision) {
                    throw new \DomainException('COM_JLMS_REVISION_CONFLICT');
                }
            } elseif (!$actor->authorise('core.create', 'com_jlms')) {
                throw new \DomainException('JERROR_ALERTNOAUTHOR');
            }

            $categoryId = (int) ($data['catid'] ?? $record->catid ?? 0);
            $categoryQuery = $db->createQuery()->select('cat.id')->from('#__categories AS cat')
                ->where("cat.extension='com_jlms.pathway' AND cat.published <> -2")
                ->where('cat.id = :category')
                ->where('NOT EXISTS (SELECT 1 FROM #__categories AS ancestor'
                    . ' WHERE ancestor.lft <= cat.lft AND ancestor.rgt >= cat.rgt AND ancestor.published = -2)')
                ->bind(':category', $categoryId, ParameterType::INTEGER);
            if (!$categoryId || !$db->setQuery($categoryQuery)->loadResult()) {
                throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
            }
            if (
                (!$record || $categoryId !== (int) $record->catid)
                && !$actor->authorise('core.create', 'com_jlms.pathway.category.' . $categoryId)
            ) {
                throw new \DomainException('JERROR_ALERTNOAUTHOR');
            }
            if ($record && $categoryId !== (int) $record->catid) {
                throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
            }
            if (
                $record &&
                (int) $record->state === -2 &&
                isset($data['state']) &&
                (int) $data['state'] !== -2 &&
                (int) $data['state'] !== 0
            ) {
                throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
            }
            if (
                isset($data['state']) && (int) $data['state'] !== (int) ($record->state ?? 0)
                && !$actor->authorise('core.edit.state', $id ? 'com_jlms.pathway.' . $id : 'com_jlms')
            ) {
                throw new \DomainException('JERROR_ALERTNOAUTHOR');
            }

            foreach (['title', 'alias', 'description', 'language'] as $key) {
                if (isset($data[$key]) && (!is_string($data[$key]) || preg_match('//u', $data[$key]) !== 1)) {
                    throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
                }
            }
            $data['description'] = \Jlms\Component\Jlms\Administrator\Application\CoursePresentation::html(
                ComponentHelper::filterText((string) ($data['description'] ?? $record->description ?? ''))
            );
            $data['catid'] = $categoryId;
            $data['access'] = (int) ($data['access'] ?? $record->access ?? 1);
            $data['language'] = (string) ($data['language'] ?? $record->language ?? '*');
            $data['state'] = (int) ($data['state'] ?? $record->state ?? 0);
            // Optional public artwork is stored in existing pathway params; never trust raw params_json.
            $artwork = json_decode((string) ($record->params_json ?? '{}'), true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($artwork)) {
                throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
            }
            // SECDEV-03: a forged array/object must not be silently cast to
            // "Array" or accepted as editorial artwork metadata.
            $imageInput = $data['cover_image'] ?? ($artwork['cover_image'] ?? '');
            $altInput = $data['cover_alt'] ?? ($artwork['cover_alt'] ?? '');
            if (
                !is_string($imageInput) || !is_string($altInput)
                || preg_match('//u', $altInput) !== 1
            ) {
                throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
            }
            $image = CourseArtwork::path($imageInput);
            CourseArtwork::validateLocal($image);
            $alt = trim($altInput);
            $decorative = $data['cover_decorative'] ?? ($artwork['cover_decorative'] ?? false);
            if (
                !in_array($decorative, [true, false, 0, 1, '0', '1'], true)
                || mb_strlen($alt) > 500 || preg_match('/[\x00-\x1f\x7f]/', $alt)
            ) {
                throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
            }
            $decorative = in_array($decorative, [true, 1, '1'], true);
            if ($image !== '' && !$decorative && $alt === '') {
                throw new \InvalidArgumentException('COM_JLMS_COURSE_IMAGE_ALT_REQUIRED');
            }
            $artwork['cover_image'] = $image;
            $artwork['cover_alt'] = $decorative ? '' : $alt;
            $artwork['cover_decorative'] = $decorative;
            unset($data['cover_image'], $data['cover_alt'], $data['cover_decorative']);
            $data['params_json'] = json_encode($artwork, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            // A partial editorial save must not silently erase course associations.
            // An explicitly supplied empty array still means remove all links.
            $replaceCourses = array_key_exists('courses', $data);
            $courses = $replaceCourses ? $this->normalizeCourses($data['courses']) : [];
            unset($data['courses'], $data['revision']);

            $stage = 'pathway-table';
            $table = $this->getTable('Pathway');
            if ($preparedAsset) {
                $table->usePreparedAsset($preparedAsset);
            }
            if ($record) {
                foreach ((array) $record as $key => $value) {
                    $table->$key = $value;
                }
            }
            if (array_key_exists('tags', $data)) {
                if (!is_array($data['tags'])) {
                    throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
                }
                $tagIds = [];
                foreach ($data['tags'] as $tagId) {
                    if (
                        (!is_int($tagId) && !is_string($tagId))
                        || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $tagId)
                    ) {
                        throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
                    }
                    $tagIds[] = (int) $tagId;
                }
                $tagIds = array_values(array_unique($tagIds));
            } elseif ($id) {
                $tagQuery = $db->createQuery()->select('tag_id')->from('#__contentitem_tag_map')
                    ->where('type_alias = ' . $db->quote('com_jlms.pathway'))
                    ->where('content_item_id = ' . $id)->order('tag_id');
                $tagIds = array_map('intval', $db->setQuery($tagQuery)->loadColumn());
            } else {
                $tagIds = [];
            }
            $table->newTags = $tagIds;
            if (!$table->bind($data)) {
                throw new \RuntimeException('pathway bind: ' . $table->getError());
            }
            if (!$table->check()) {
                throw new \RuntimeException('pathway check: ' . $table->getError());
            }
            if (!$table->store()) {
                throw new \RuntimeException('pathway table store: ' . $table->getError());
            }
            $pathwayId = (int) $table->id;

            if ($replaceCourses) {
                $deletePathwayId = $pathwayId;
                $db->setQuery($db->createQuery()->delete('#__jlms_pathway_courses')
                    ->where('pathway_id = :pathway')
                        ->bind(':pathway', $deletePathwayId, ParameterType::INTEGER))->execute();
                foreach ($courses as $ordering => $courseId) {
                    // SECDEV-03: explicit Joomla SQL binding even for validated
                    // integer IDs and positions. No input-derived query fragments.
                    $db->setQuery($db->createQuery()->insert('#__jlms_pathway_courses')
                        ->columns(['pathway_id', 'course_id', 'ordering'])
                        ->values(':pathwayId, :courseId, :position')
                        ->bind(':pathwayId', $pathwayId, ParameterType::INTEGER)
                        ->bind(':courseId', $courseId, ParameterType::INTEGER)
                        ->bind(':position', $ordering, ParameterType::INTEGER))->execute();
                }
            }

            $history = $table->getProperties();
            $history['courses'] = $db->setQuery($db->createQuery()->select('course_id, ordering')
                ->from('#__jlms_pathway_courses')->where('pathway_id=' . $pathwayId)->order('ordering,course_id'))
                ->loadAssocList();
            $history['tags'] = $tagIds;
            $this->persistHistory($history, $this->typeAlias);
            $db->transactionCommit();
            $transactionStarted = false;
            $preparedAsset = 0;
            return true;
        } catch (\Throwable $error) {
            if ($transactionStarted) {
                $db->transactionRollback();
            }
            if ($preparedAsset && $assetTable) {
                try {
                    $assetTable->delete($preparedAsset);
                } catch (\Throwable) {
                    // A later asset maintenance pass can remove an orphaned placeholder.
                }
            }
            $known = [
                'JERROR_ALERTNOAUTHOR',
                'JERROR_LAYOUT_PAGE_NOT_FOUND',
                'COM_JLMS_REVISION_CONFLICT',
                'COM_JLMS_PATHWAY_INVALID'
            ];
            $this->setError(Text::_(in_array(
                $error->getMessage(),
                $known,
                true
            ) ? $error->getMessage() : 'COM_JLMS_SAVE_FAILED'));
            if (getenv('JLMS_TEST_DISPOSABLE') === '1') {
                $this->setError($this->getError() . ' [' . $stage . ': ' . $error->getMessage() . ']');
            }
            return false;
        }
    }

    private function normalizeCourses(mixed $rows): array
    {
        if (!is_array($rows) || count($rows) > 100) {
            throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
        }
        $normalized = [];
        foreach ($rows as $index => $row) {
            if (
                !is_array($row) || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) ($row['course_id'] ?? ''))
                || !preg_match('/^[0-9]{1,4}$/D', (string) ($row['ordering'] ?? $index))
            ) {
                throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
            }
            $courseId = (int) $row['course_id'];
            if (isset($normalized[$courseId])) {
                throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
            }
            $normalized[$courseId] = (int) ($row['ordering'] ?? $index);
        }
        asort($normalized, SORT_NUMERIC);
        $courseIds = array_keys($normalized);
        if ($courseIds !== []) {
            $query = $this->getDatabase()->createQuery()->select('id')->from('#__jlms_courses')
                ->whereIn('id', $courseIds);
            if (count($this->getDatabase()->setQuery($query)->loadColumn()) !== count($courseIds)) {
                throw new \InvalidArgumentException('COM_JLMS_PATHWAY_INVALID');
            }
        }
        return $courseIds;
    }

    /**
     * Atomic list actions. Each transition is authorized against the pathway's
     * Joomla asset and rechecked after the database row has been locked.
     * No course association or learner history is touched.
     *
     * @param list<int> $ids
     */
    public function changeStates(array $ids, int $target): bool
    {
        if (
            !in_array($target, [-2, 0, 1, 2], true) || $ids === [] || count($ids) > 100
            || count(array_unique($ids)) !== count($ids)
            || count(array_filter($ids, static fn ($id): bool => !is_int($id) || $id < 1)) > 0
        ) {
            $this->setError(Text::_('COM_JLMS_PATHWAY_INVALID'));
            return false;
        }

        $actor = new User((int) $this->getCurrentUser()->id);
        $actor->clearAccessRights();
        if (
            !$actor->id || $actor->block
            || !$actor->authorise('core.manage', 'com_jlms')
            || !$actor->authorise('jlms.pathways.manage', 'com_jlms')
        ) {
            $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));
            return false;
        }

        $db = $this->getDatabase();
        $started = false;
        try {
            // Lock in canonical order to prevent conflicting concurrent bulk actions.
            sort($ids, SORT_NUMERIC);
            $db->transactionStart();
            $started = true;
            foreach ($ids as $id) {
                $query = $db->createQuery()->select('id, state, revision, checked_out')->from('#__jlms_pathways')
                    ->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
                $query->setQuery((string) $query . ' FOR UPDATE');
                $record = $db->setQuery($query)->loadObject()
                    ?: throw new \DomainException('JERROR_LAYOUT_PAGE_NOT_FOUND');
                if (
                    !$actor->authorise('core.edit', 'com_jlms.pathway.' . $id)
                    || !$actor->authorise('core.edit.state', 'com_jlms.pathway.' . $id)
                    || ((int) $record->checked_out > 0 && (int) $record->checked_out !== (int) $actor->id)
                ) {
                    throw new \DomainException('JERROR_ALERTNOAUTHOR');
                }
                // Restoration never silently republishes a trashed pathway.
                if ((int) $record->state === -2 && !in_array($target, [-2, 0], true)) {
                    throw new \DomainException('COM_JLMS_PATHWAY_INVALID');
                }
                if ((int) $record->state === $target) {
                    continue;
                }
                $update = (object) [
                    'id' => $id,
                    'state' => $target,
                    'revision' => (int) $record->revision + 1,
                    'modified_at' => Factory::getDate()->toSql(),
                    'modified_by' => (int) $actor->id,
                ];
                $db->updateObject('#__jlms_pathways', $update, 'id');
            }
            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            if ($started) {
                $db->transactionRollback();
            }
            $known = ['JERROR_ALERTNOAUTHOR', 'JERROR_LAYOUT_PAGE_NOT_FOUND', 'COM_JLMS_PATHWAY_INVALID'];
            $this->setError(Text::_(in_array($error->getMessage(), $known, true)
                ? $error->getMessage() : 'COM_JLMS_SAVE_FAILED'));
            return false;
        }
    }

    /**
     * Permanent deletion is intentionally single-record and conservative:
     * only an already trashed pathway with no course relationships is eligible.
     * Course links and historical learning data are NEVER cascaded.
     */
    public function purgeEmpty(int $id): bool
    {
        if ($id < 1) {
            $this->setError(Text::_('COM_JLMS_PATHWAY_INVALID'));
            return false;
        }
        $actor = new User((int) $this->getCurrentUser()->id);
        $actor->clearAccessRights();
        if (
            !$actor->id || $actor->block
            || !$actor->authorise('core.manage', 'com_jlms')
            || !$actor->authorise('jlms.pathways.manage', 'com_jlms')
            || !$actor->authorise('core.delete', 'com_jlms.pathway.' . $id)
        ) {
            $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));
            return false;
        }
        $db = $this->getDatabase();
        $started = false;
        try {
            $db->transactionStart();
            $started = true;
            $query = $db->createQuery()->select('id, state, checked_out')->from('#__jlms_pathways')
                ->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
            $query->setQuery((string) $query . ' FOR UPDATE');
            $record = $db->setQuery($query)->loadObject()
                ?: throw new \DomainException('JERROR_LAYOUT_PAGE_NOT_FOUND');
            if ((int) $record->checked_out > 0 && (int) $record->checked_out !== (int) $actor->id) {
                throw new \DomainException('JERROR_ALERTNOAUTHOR');
            }
            if ((int) $record->state !== -2) {
                throw new \DomainException('COM_JLMS_PATHWAY_INVALID');
            }
            $countQuery = $db->createQuery()->select('COUNT(*)')->from('#__jlms_pathway_courses')
                ->where('pathway_id = :id')->bind(':id', $id, ParameterType::INTEGER);
            if ((int) $db->setQuery($countQuery)->loadResult() !== 0) {
                throw new \DomainException('COM_JLMS_PATHWAY_DEPENDENCIES');
            }
            // Multilingual association rows have no JD Learn foreign key and must not become orphaned.
            $context = $this->typeAlias;
            $associations = $db->createQuery()->select('COUNT(*)')->from('#__associations')
                ->where('id = :id')->where('context = :context')
                ->bind(':id', $id, ParameterType::INTEGER)->bind(':context', $context);
            if ((int) $db->setQuery($associations)->loadResult() > 0) {
                throw new \DomainException('COM_JLMS_PATHWAY_DEPENDENCIES');
            }
            // Discover database-enforced references from installed integrations,
            // including ON DELETE CASCADE: a successful DELETE must never erase
            // third-party historical records as an unintended side effect.
            // References without a declared FK remain outside this contract.
            $physicalPathwayTable = $db->getPrefix() . 'jlms_pathways';
            $referencedColumn = 'id';
            $referencingKeys = $db->createQuery()
                ->select([$db->quoteName('TABLE_NAME'), $db->quoteName('COLUMN_NAME')])
                ->from('information_schema.KEY_COLUMN_USAGE')
                ->where('REFERENCED_TABLE_SCHEMA = DATABASE()')
                ->where('REFERENCED_TABLE_NAME = :pathwayTable')
                ->where('REFERENCED_COLUMN_NAME = :pathwayColumn')
                ->bind(':pathwayTable', $physicalPathwayTable)
                ->bind(':pathwayColumn', $referencedColumn);
            foreach ($db->setQuery($referencingKeys)->loadAssocList() as $reference) {
                $tableName = (string) $reference['TABLE_NAME'];
                $columnName = (string) $reference['COLUMN_NAME'];
                if (
                    !preg_match('/^[A-Za-z0-9_]+$/D', $tableName)
                    || !preg_match('/^[A-Za-z0-9_]+$/D', $columnName)
                ) {
                    throw new \DomainException('COM_JLMS_PATHWAY_DEPENDENCIES');
                }
                $query = $db->createQuery()->select('COUNT(*)')
                    ->from($db->quoteName($tableName))
                    ->where($db->quoteName($columnName) . ' = :pathwayId')
                    ->bind(':pathwayId', $id, ParameterType::INTEGER);
                if ((int) $db->setQuery($query)->loadResult() > 0) {
                    throw new \DomainException('COM_JLMS_PATHWAY_DEPENDENCIES');
                }
            }
            // Joomla's table deletion also removes the matching ACL asset.
            // Never call this method on a populated pathway.
            $table = $this->getTable('Pathway');
            if (!$table->load($id) || !$table->delete($id)) {
                throw new \RuntimeException('COM_JLMS_SAVE_FAILED');
            }
            $db->transactionCommit();
            return true;
        } catch (\Throwable $error) {
            if ($started) {
                $db->transactionRollback();
            }
            $known = ['JERROR_ALERTNOAUTHOR', 'JERROR_LAYOUT_PAGE_NOT_FOUND',
                'COM_JLMS_PATHWAY_INVALID', 'COM_JLMS_PATHWAY_DEPENDENCIES'];
            $this->setError(Text::_(in_array($error->getMessage(), $known, true)
                ? $error->getMessage() : 'COM_JLMS_SAVE_FAILED'));
            return false;
        }
    }

    public function delete(&$pks)
    {
        $this->setError(Text::_('COM_JLMS_DELETE_NOT_AVAILABLE'));
        return false;
    }

    public function publish(&$pks, $value = 1)
    {
        $this->setError(Text::_('COM_JLMS_DELETE_NOT_AVAILABLE'));
        return false;
    }
}
