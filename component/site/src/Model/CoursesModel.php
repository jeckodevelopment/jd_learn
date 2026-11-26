<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Jlms\Component\Jlms\Site\Helper\CatalogOptions;
use Jlms\Component\Jlms\Site\Helper\CatalogSearch;
use Jlms\Component\Jlms\Administrator\Application\TeacherProfiles;

class CoursesModel extends ListModel
{
    /** @var array<int, object>|null */
    private ?array $visibleCatalogCategories = null;

    private ?string $visibleCatalogCategoriesKey = null;

    protected function populateState($ordering = 'c.id', $direction = 'DESC')
    {
        parent::populateState($ordering, $direction);
        // Pagination is entirely URL-driven: a GET filter submit omits limitstart
        // and must start again at page one, never restore a previous user-state page.
        // Category subcategory paging uses its own independent prefixed parameter.
        $this->setState('list.start', max(0, Factory::getApplication()->getInput()->getInt('limitstart', 0)));
        $this->setState('list.limit', (int) CatalogOptions::get('catalog_page_size'));
        $this->setState('filter.category_id', max(0, Factory::getApplication()->getInput()->getInt('catid', 0)));
        // A hidden filter form must not allow stale or handcrafted GET filters.
        $filter = CatalogOptions::enabled('catalog_show_filters')
            ? Factory::getApplication()->getInput()->get('filter', [], 'array') : [];
        $categoryChoice = $filter['category_id'] ?? 0;
        $this->setState('filter.category_choice', CatalogOptions::enabled('catalog_show_category')
            && is_scalar($categoryChoice) ? max(0, (int) $categoryChoice) : 0);
        $search = $filter['search'] ?? '';
        $this->setState('filter.search', is_string($search) ? trim(mb_substr($search, 0, 100)) : '');
        $defaultOrder = CatalogOptions::get('catalog_default_order');
        $order = CatalogOptions::enabled('catalog_show_order') ? ($filter['order'] ?? $defaultOrder) : $defaultOrder;
        $this->setState('filter.order', is_string($order) && in_array($order, [
            'editorial', 'recent', 'title', 'duration', 'published'], true) ? $order : $defaultOrder);
        $tagId = $filter['tag_id'] ?? 0;
        $this->setState('filter.tag_id', max(0, is_scalar($tagId) ? (int) $tagId : 0));
        $difficulty = $filter['difficulty'] ?? '';
        $this->setState('filter.difficulty', is_string($difficulty)
            && in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true) ? $difficulty : '');
        $duration = $filter['duration'] ?? '';
        $this->setState('filter.duration', is_string($duration)
            && in_array($duration, ['under-30', '30-60', '61-120', 'over-120'], true) ? $duration : '');
        $available = $filter['available'] ?? '';
        // A promotional listing remains visible unless the visitor opts in.
        $this->setState('filter.available', $available === '1' ? '1' : '');
        $layout = $filter['layout'] ?? 'grid';
        $this->setState('filter.layout', is_string($layout) && in_array($layout, [
            'grid', 'list'], true) ? $layout : 'grid');
        $this->setState('filter.teacher_id', max(0, is_scalar($filter['teacher_id']
            ?? 0) ? (int) ($filter['teacher_id'] ?? 0) : 0));
        $this->setState('filter.pathway_id', max(0, is_scalar($filter['pathway_id']
            ?? 0) ? (int) ($filter['pathway_id'] ?? 0) : 0));
    }

    protected function getListQuery()
    {
        $app = Factory::getApplication();
        $component = $app->bootComponent('com_jlms');
        $teacherId = (int) $this->getState('filter.teacher_id');
        if ($teacherId && !isset($this->getTeachers()[$teacherId])) {
            $teacherId = -1;
        }
        $pathwayId = (int) $this->getState('filter.pathway_id');
        if ($pathwayId && !isset($this->getPathways()[$pathwayId])) {
            $pathwayId = -1;
        }
        $query = $component->getCatalogService()->query(
            $app->getIdentity(),
            $app->getLanguage()->getTag(),
            (int) $this->getState('filter.category_id'),
            (int) $this->getState('filter.tag_id'),
            $teacherId,
            $pathwayId
        );
        // Only public presentation fields from the published course version.
        $query->select('v.presentation_json AS catalog_presentation_json');
        $categoryChoice = (int) $this->getState('filter.category_choice');
        if ($categoryChoice > 0) {
            if (!isset($this->getCategories()[$categoryChoice])) {
                // No category enumeration from a forged URL, even under a scoped catalogue.
                $query->where('1 = 0');
            } else {
                $query->where('c.catid = :catalogCategoryChoice')
                    ->bind(':catalogCategoryChoice', $categoryChoice, \Joomla\Database\ParameterType::INTEGER);
            }
        }
        $search = (string) $this->getState('filter.search');
        if ($search !== '') {
            $titleSearch = CatalogSearch::pattern($search);
            $summarySearch = $titleSearch;
            // SQL wildcards in visitor text must be matched literally.
            $query->where(("(c.title LIKE :catalogSearchTitle ESCAPE '!' OR c.summary LIKE "
                . ":catalogSearchSummary ESCAPE '!')"))
                ->bind(':catalogSearchTitle', $titleSearch)
                ->bind(':catalogSearchSummary', $summarySearch);
        }
        $difficulty = (string) $this->getState('filter.difficulty');
        if ($difficulty !== '') {
            $query->where("JSON_UNQUOTE(JSON_EXTRACT(c.params_json, '$.presentation.difficulty')) = :catalogDifficulty")
                ->bind(':catalogDifficulty', $difficulty);
        }
        $duration = (string) $this->getState('filter.duration');
        if ($duration !== '') {
            $minutes = ("CAST(JSON_UNQUOTE(JSON_EXTRACT(c.params_json, "
                . "'$.presentation.estimated_minutes')) AS UNSIGNED)");
            match ($duration) {
                'under-30' => $query->where("$minutes BETWEEN 1 AND 29"),
                '30-60' => $query->where("$minutes BETWEEN 30 AND 60"),
                '61-120' => $query->where("$minutes BETWEEN 61 AND 120"),
                'over-120' => $query->where("$minutes > 120"),
            };
        }
        if ($this->getState('filter.available') === '1') {
            // Do not confuse public catalogue visibility with live delivery/admissions.
            // Existing course/category/language/ACL/publication restrictions still apply.
            $query->where('c.admissions_open = 1')->where('c.delivery_enabled = 1');
        }
        $order = (string) $this->getState('filter.order');
        if ($order === 'editorial') {
            // Positive editorial ranks first, unassigned last; stable tie-break.
            $position = ("COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(c.params_json, "
                . "'$.catalog_position')) AS UNSIGNED), 0)");
            return $query->order("($position = 0) ASC, $position ASC, c.id DESC");
        }
        if ($order === 'duration') {
            // Sort by the immutable PUBLISHED snapshot displayed on each card, not the editable draft.
            // Unknown durations come last, and ID makes pagination deterministic.
            $minutes = ("CAST(JSON_UNQUOTE(JSON_EXTRACT(v.presentation_json, "
                . "'$.details.estimated_minutes')) AS UNSIGNED)");
            return $query->order("($minutes IS NULL) ASC, $minutes ASC, c.id ASC");
        }
        if ($order === 'published') {
            // The current published version has its own UTC publication timestamp.
            // Never sort by mutable draft modification dates; stable ID tie-break.
            return $query->order('v.published_at DESC, c.id DESC');
        }
        return $query->order($order === 'title' ? 'c.title ASC, c.id ASC' : 'c.id DESC');
    }

    /** Never reuse language- or identity-sensitive catalogue lists in a different context. */
    protected function getStoreId($id = '')
    {
        $app = Factory::getApplication();
        return parent::getStoreId($id . ':actor:' . (int) $app->getIdentity()->id
            . ':locale:' . $app->getLanguage()->getTag());
    }

    /** Visible frozen teacher names for the currently paginated catalogue courses only. */
    public function getCardTeachers(array $items): array
    {
        $app = Factory::getApplication();
        $actor = $app->getIdentity();
        $language = $app->getLanguage()->getTag();
        $db = $this->getDatabase();
        $result = [];
        foreach ($items as $course) {
            $id = (int) $course->id;
            try {
                $result[$id] = TeacherProfiles::visible(
                    $db,
                    $actor,
                    $language,
                    (string) ($course->catalog_presentation_json ?? '')
                );
            } catch (\JsonException | \RuntimeException | \TypeError $error) {
                // A malformed historical snapshot must not break the public catalogue.
                $result[$id] = [];
            }
        }
        return $result;
    }

    /** Owner-only enrollment state, using the same validated projection as My Courses. */
    public function getCardEnrollmentStates(array $items): array
    {
        $app = Factory::getApplication();
        $identity = $app->getIdentity();
        if (!(int) $identity->id || $identity->guest || $identity->block || !$items) {
            return [];
        }
        $ids = array_map(static fn (object $item): int => (int) $item->id, $items);
        return $app->bootComponent('com_jlms')->getDashboardService()->catalogStates(
            $identity,
            $app->getLanguage()->getTag(),
            $ids
        );
    }

    /**
     * Pending approval requests for this visible catalogue page and current
     * published version only. One bounded owner-scoped query, no N+1 requests.
     *
     * @return array<int, true>
     */
    public function getCardPendingApprovals(array $items, array $enrollmentStates): array
    {
        if (!$items) {
            return [];
        }
        $app = Factory::getApplication();
        $identity = $app->getIdentity();
        if (!(int) $identity->id || $identity->guest) {
            return [];
        }
        // Re-check the persisted Joomla account, rather than trusting an old
        // session after the account is blocked or removed.
        try {
            $actor = $app->bootComponent('com_jlms')->getLearningService()->freshActor($identity);
        } catch (\RuntimeException $error) {
            // A revoked session must not expose owner-only request state or
            // prevent an otherwise public promotional catalogue from loading.
            return [];
        }
        $courseIds = [];
        foreach ($items as $item) {
            $courseId = (int) $item->id;
            if (
                $courseId > 0 && (string) $item->enrollment_mode === 'approval'
                && !isset($enrollmentStates[$courseId])
            ) {
                $courseIds[] = $courseId;
            }
        }
        $courseIds = array_values(array_unique($courseIds));
        if (!$courseIds) {
            return [];
        }
        if (count($courseIds) > 50) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
        $query = $this->getDatabase()->createQuery()
            ->select('DISTINCT edition.course_id')
            ->from('#__jlms_enrollment_requests AS request')
            ->innerJoin('#__jlms_editions AS edition ON edition.id = request.edition_id')
            ->innerJoin('#__jlms_courses AS course ON course.id = edition.course_id')
            ->where('request.user_id = :catalogPendingOwner')
            ->where("request.status = 'pending'")
            ->where('edition.course_version_id = course.current_version_id')
            ->whereIn('edition.course_id', $courseIds)
            ->bind(':catalogPendingOwner', (int) $actor->id, \Joomla\Database\ParameterType::INTEGER);
        $result = [];
        foreach ($this->getDatabase()->setQuery($query)->loadColumn() as $courseId) {
            $result[(int) $courseId] = true;
        }
        return $result;
    }

    public function getCategoryChoice(): int
    {
        return (int) $this->getState('filter.category_choice');
    }

    /** Categories containing at least one course visible to this user. */
    public function getCategories(): array
    {
        $app = Factory::getApplication();
        $actorId = (int) $app->getIdentity()->id;
        $language = $app->getLanguage()->getTag();
        $categoryId = (int) $this->getState('filter.category_id');
        $cacheKey = $actorId . ':' . $language . ':' . $categoryId;

        if ($this->visibleCatalogCategories === null || $this->visibleCatalogCategoriesKey !== $cacheKey) {
            $this->visibleCatalogCategories = $app->bootComponent('com_jlms')
                ->getCatalogService()->publicCourseCategories(
                    $app->getIdentity(),
                    $language,
                    $categoryId
                );
            $this->visibleCatalogCategoriesKey = $cacheKey;
        }
        return $this->visibleCatalogCategories;
    }

    public function getSearch(): string
    {
        return (string) $this->getState('filter.search');
    }

    public function getOrder(): string
    {
        return (string) $this->getState('filter.order');
    }

    public function getTagId(): int
    {
        return (int) $this->getState('filter.tag_id');
    }

    public function getTags(): array
    {
        $app = Factory::getApplication();
        return $app->bootComponent('com_jlms')->getCatalogService()->publicTags(
            $app->getIdentity(),
            $app->getLanguage()->getTag(),
            (int) $this->getState('filter.category_id')
        );
    }

    public function getDifficulty(): string
    {
        return (string) $this->getState('filter.difficulty');
    }

    public function getDuration(): string
    {
        return (string) $this->getState('filter.duration');
    }

    public function getAvailable(): string
    {
        return (string) $this->getState('filter.available');
    }

    public function getLayout(): string
    {
        return (string) $this->getState('filter.layout');
    }

    public function getTeacherId(): int
    {
        return (int) $this->getState('filter.teacher_id');
    }
    public function getPathwayId(): int
    {
        return (int) $this->getState('filter.pathway_id');
    }

    public function getTeachers(): array
    {
        $app = Factory::getApplication();
        return $app->bootComponent('com_jlms')->getCatalogService()->publicTeachers(
            $app->getIdentity(),
            $app->getLanguage()->getTag(),
            (int) $this->getState('filter.category_id')
        );
    }

    public function getPathways(): array
    {
        $app = Factory::getApplication();
        $component = $app->bootComponent('com_jlms');
        $ids = $component->getCatalogService()->publicPathwayIds(
            $app->getIdentity(),
            $app->getLanguage()->getTag(),
            (int) $this->getState('filter.category_id')
        );
        if (!$ids) {
            return [];
        }
        $query = $component->getPathwayService()->query($app->getIdentity(), $app->getLanguage()->getTag());
        $query->whereIn('p.id', $ids);
        return $this->getDatabase()->setQuery($query, 0, 500)->loadObjectList('id');
    }
}
