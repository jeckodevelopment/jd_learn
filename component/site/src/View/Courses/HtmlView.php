<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Site\View\Courses;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Language\Text;
use Jlms\Component\Jlms\Site\Helper\CatalogOptions;
use Jlms\Component\Jlms\Site\Helper\CatalogPagination;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public bool $loadFailed = false;
    public array $cardTeachers = [];
    public array $cardEnrollmentStates = [];
    public array $cardPendingApprovals = [];
    public $pagination;
    public int $categoryId = 0;
    public string $search = '';
    public string $order = 'recent';
    public int $tagId = 0;
    public array $tags = [];
public string $difficulty = '';
    public string $duration = '';
    public string $available = '';
    public string $layout = 'grid';
    public int $teacherId = 0;
    public int $pathwayId = 0;
    public array $teachers = [];
    public array $pathways = [];
    public bool $showFilters = true;
    public bool $showOrder = true;
    public bool $showCategory = true;
    public int $categoryChoice = 0;
    public array $categories = [];
    public bool $showCover = true;
    public bool $showSummary = true;

    public function display($tpl = null)
    {
        $this->showFilters = CatalogOptions::enabled('catalog_show_filters');
        $this->showOrder = $this->showFilters && CatalogOptions::enabled('catalog_show_order');
        $this->showCategory = $this->showFilters && CatalogOptions::enabled('catalog_show_category');
        $this->showCover = CatalogOptions::enabled('catalog_show_cover');
        $this->showSummary = CatalogOptions::enabled('catalog_show_summary');
        try {
        $this->items = $this->get('Items');
        $this->cardTeachers = $this->getModel()->getCardTeachers($this->items);
        $this->cardEnrollmentStates = $this->getModel()->getCardEnrollmentStates($this->items);
        $this->cardPendingApprovals = $this->getModel()->getCardPendingApprovals($this->items, $this->cardEnrollmentStates);
        $this->pagination = $this->get('Pagination');
        $this->categoryId = (int) $this->getModel()->getState('filter.category_id');
        $this->categoryChoice = $this->getModel()->getCategoryChoice();
        $this->categories = $this->showCategory ? $this->getModel()->getCategories() : [];
        $this->search = $this->getModel()->getSearch();
        $this->order = $this->getModel()->getOrder();
        $this->tagId = $this->getModel()->getTagId();
        $this->tags = $this->showFilters ? $this->getModel()->getTags() : [];
$this->difficulty = $this->getModel()->getDifficulty();
        $this->duration = $this->getModel()->getDuration();
        $this->available = $this->getModel()->getAvailable();
        $this->layout = $this->getModel()->getLayout();
        $this->teacherId = $this->getModel()->getTeacherId();
        $this->pathwayId = $this->getModel()->getPathwayId();
        // Register public, server-normalized filters explicitly: Joomla's
        // pagination links otherwise discard request query parameters.
        $paginationFilters = [
            'search' => $this->search,
            'order' => $this->showOrder ? $this->order : '',
            'tag_id' => $this->tagId,
            'difficulty' => $this->difficulty,
            'duration' => $this->duration,
            'available' => $this->available,
            'layout' => $this->layout,
            'teacher_id' => $this->teacherId,
            'pathway_id' => $this->pathwayId,
        ];
        $paginationFilters['category_id'] = $this->showCategory ? $this->categoryChoice : 0;
        CatalogPagination::apply($this->pagination, $paginationFilters, $this->showFilters, $this->categoryId);
        $this->teachers = $this->showFilters ? $this->getModel()->getTeachers() : [];
        $this->pathways = $this->showFilters ? $this->getModel()->getPathways() : [];
        if ($this->get('Errors')) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 500);
        }
        } catch (\Throwable $error) {
            // Access denials and unknown routes must never become a public result.
            if (in_array((int) $error->getCode(), [401, 403, 404], true)) {
throw $error;
            }
            // Fail closed: discard all partially loaded owner and course data.
            $this->loadFailed = true;
            $this->items = [];
            $this->cardTeachers = [];
            $this->cardEnrollmentStates = [];
            $this->cardPendingApprovals = [];
            $this->pagination = null;
        }
        $this->getDocument()->setTitle(Text::_('COM_JLMS_COURSES'));
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
