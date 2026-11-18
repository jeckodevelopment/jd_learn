<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Jlms\Component\Jlms\Site\Helper\CatalogOptions;

class CoursesModel extends ListModel
{
    /** @var array<int, object>|null */
    private ?array $visibleCatalogCategories = null;

    protected function populateState($ordering = 'c.id', $direction = 'DESC')
    {
        parent::populateState($ordering, $direction);
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
        $this->setState('filter.order', is_string($order) && in_array($order, ['recent', 'title', 'duration'], true) ? $order : $defaultOrder);
        $tagId = $filter['tag_id'] ?? 0;
        $this->setState('filter.tag_id', max(0, is_scalar($tagId) ? (int) $tagId : 0));
        $difficulty = $filter['difficulty'] ?? '';
        $this->setState('filter.difficulty', is_string($difficulty) && in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true) ? $difficulty : '');
        $duration = $filter['duration'] ?? '';
        $this->setState('filter.duration', is_string($duration) && in_array($duration, ['under-30', '30-60', '61-120', 'over-120'], true) ? $duration : '');
        $layout = $filter['layout'] ?? 'grid';
        $this->setState('filter.layout', is_string($layout) && in_array($layout, ['grid', 'list'], true) ? $layout : 'grid');
        $this->setState('filter.teacher_id', max(0, is_scalar($filter['teacher_id'] ?? 0) ? (int) ($filter['teacher_id'] ?? 0) : 0));
        $this->setState('filter.pathway_id', max(0, is_scalar($filter['pathway_id'] ?? 0) ? (int) ($filter['pathway_id'] ?? 0) : 0));
    }

    protected function getListQuery()
    {
        $app = Factory::getApplication();
        $component = $app->bootComponent('com_jlms');
        $teacherId = (int) $this->getState('filter.teacher_id');
        if ($teacherId && !isset($this->getTeachers()[$teacherId])) { $teacherId = -1; }
        $pathwayId = (int) $this->getState('filter.pathway_id');
        if ($pathwayId && !isset($this->getPathways()[$pathwayId])) { $pathwayId = -1; }
        $query = $component->getCatalogService()->query($app->getIdentity(), $app->getLanguage()->getTag(),
            (int) $this->getState('filter.category_id'), (int) $this->getState('filter.tag_id'), $teacherId, $pathwayId);
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
            $titleSearch = '%' . $search . '%';
            $summarySearch = '%' . $search . '%';
            $query->where('(c.title LIKE :catalogSearchTitle OR c.summary LIKE :catalogSearchSummary)')
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
            $minutes = "CAST(JSON_UNQUOTE(JSON_EXTRACT(c.params_json, '$.presentation.estimated_minutes')) AS UNSIGNED)";
            match ($duration) {
                'under-30' => $query->where("$minutes BETWEEN 1 AND 29"),
                '30-60' => $query->where("$minutes BETWEEN 30 AND 60"),
                '61-120' => $query->where("$minutes BETWEEN 61 AND 120"),
                'over-120' => $query->where("$minutes > 120"),
            };
        }
        $order = (string) $this->getState('filter.order');
        if ($order === 'duration') {
            // Sort by the immutable PUBLISHED snapshot displayed on each card, not the editable draft.
            // Unknown durations come last, and ID makes pagination deterministic.
            $minutes = "CAST(JSON_UNQUOTE(JSON_EXTRACT(v.presentation_json, '$.details.estimated_minutes')) AS UNSIGNED)";
            return $query->order("($minutes IS NULL) ASC, $minutes ASC, c.id ASC");
        }
        return $query->order($order === 'title' ? 'c.title ASC, c.id ASC' : 'c.id DESC');
    }

    public function getCategoryChoice(): int
    {
        return (int) $this->getState('filter.category_choice');
    }

    /** Categories containing at least one course visible to this user. */
    public function getCategories(): array
    {
        if ($this->visibleCatalogCategories === null) {
            $app = Factory::getApplication();
            $this->visibleCatalogCategories = $app->bootComponent('com_jlms')
                ->getCatalogService()->publicCourseCategories(
                    $app->getIdentity(), $app->getLanguage()->getTag(),
                    (int) $this->getState('filter.category_id')
                );
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
            $app->getIdentity(), $app->getLanguage()->getTag(), (int) $this->getState('filter.category_id')
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

    public function getLayout(): string
    {
        return (string) $this->getState('filter.layout');
    }

    public function getTeacherId(): int { return (int) $this->getState('filter.teacher_id'); }
    public function getPathwayId(): int { return (int) $this->getState('filter.pathway_id'); }

    public function getTeachers(): array
    {
        $app = Factory::getApplication();
        return $app->bootComponent('com_jlms')->getCatalogService()->publicTeachers(
            $app->getIdentity(), $app->getLanguage()->getTag(), (int) $this->getState('filter.category_id')
        );
    }

    public function getPathways(): array
    {
        $app = Factory::getApplication(); $component = $app->bootComponent('com_jlms');
        $ids = $component->getCatalogService()->publicPathwayIds(
            $app->getIdentity(), $app->getLanguage()->getTag(), (int) $this->getState('filter.category_id')
        );
        if (!$ids) { return []; }
        $query = $component->getPathwayService()->query($app->getIdentity(), $app->getLanguage()->getTag());
        $query->whereIn('p.id', $ids);
        return $this->getDatabase()->setQuery($query, 0, 500)->loadObjectList('id');
    }
}
