<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

class CoursesModel extends ListModel
{
    protected function populateState($ordering = 'c.id', $direction = 'DESC')
    {
        parent::populateState($ordering, $direction);
        $this->setState('list.limit', max(1, min(100, (int) $this->state->get('list.limit', 20))));
        $this->setState('filter.category_id', max(0, Factory::getApplication()->getInput()->getInt('catid', 0)));
        $filter = Factory::getApplication()->getInput()->get('filter', [], 'array');
        $search = $filter['search'] ?? '';
        $this->setState('filter.search', is_string($search) ? trim(mb_substr($search, 0, 100)) : '');
        $order = $filter['order'] ?? 'recent';
        $this->setState('filter.order', is_string($order) && in_array($order, ['recent', 'title'], true) ? $order : 'recent');
        $tagId = $filter['tag_id'] ?? 0;
        $this->setState('filter.tag_id', max(0, is_scalar($tagId) ? (int) $tagId : 0));
        $difficulty = $filter['difficulty'] ?? '';
        $this->setState('filter.difficulty', is_string($difficulty) && in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true) ? $difficulty : '');
        $duration = $filter['duration'] ?? '';
        $this->setState('filter.duration', is_string($duration) && in_array($duration, ['under-30', '30-60', '61-120', 'over-120'], true) ? $duration : '');
    }

    protected function getListQuery()
    {
        $app = Factory::getApplication();
        $query = $app->bootComponent('com_jlms')->getCatalogService()->query($app->getIdentity(), $app->getLanguage()->getTag(),
            (int) $this->getState('filter.category_id'), (int) $this->getState('filter.tag_id'));
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
        return $query->order($this->getState('filter.order') === 'title' ? 'c.title ASC, c.id ASC' : 'c.id DESC');
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
}
