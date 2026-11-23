<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Category;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Jlms\Component\Jlms\Site\Helper\CategoryNavigationHelper;
use Jlms\Component\Jlms\Site\Helper\CategoryOptions;
use Jlms\Component\Jlms\Site\Helper\CatalogOptions;
use Jlms\Component\Jlms\Site\Helper\CatalogPagination;
use Jlms\Component\Jlms\Site\Helper\PublicMetadataHelper;

final class HtmlView extends BaseHtmlView
{
    public object $category;
    public array $items = [];
    public array $children = [];
    public bool $showSubcategories = true;
    public bool $showCourses = true;
    public bool $showFilters = true;
    public bool $showOrder = true;
    public $pagination;
    public $childrenPagination;
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

    public function display($tpl = null)
    {
        $this->category = $this->getModel()->getCategory();
        $options = CategoryOptions::sections((int) $this->category->id);
        $this->showSubcategories = $options['subcategories'];
        $this->showCourses = $options['courses'];
        $this->showFilters = $this->showCourses && CatalogOptions::enabled('catalog_show_filters');
        $this->showOrder = $this->showFilters && CatalogOptions::enabled('catalog_show_order');
        $this->items = $this->showCourses ? $this->get('Items') : [];
        $this->pagination = $this->showCourses ? $this->get('Pagination') : null;
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
        if ($this->pagination !== null) {
            CatalogPagination::apply($this->pagination, $paginationFilters, $this->showFilters);
        }
        $this->teachers = $this->showFilters ? $this->getModel()->getTeachers() : [];
        $this->pathways = $this->showFilters ? $this->getModel()->getPathways() : [];
        if ($this->get('Errors')) {
            throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 500);
        }
        $app = Factory::getApplication();
        if ($this->showSubcategories) {
            $model = $app->bootComponent('com_jlms')->getMVCFactory()->createModel('Categories', 'Site', ['ignore_request' => true]);
            $model->setState('filter.parent_id', (int) $this->category->id);
            $model->setState('list.limit', 20);
            $model->setState('list.start', max(0, $app->getInput()->getInt('subcategories_limitstart', 0)));
            $model->setState('pagination.prefix', 'subcategories_');
            $this->children = $model->getItems();
            $this->childrenPagination = $model->getPagination();
        // Independent subcategory pages must retain the active course filters.
            CatalogPagination::apply($this->childrenPagination, $paginationFilters, $this->showFilters);
        }
        CategoryNavigationHelper::addPath((int) $this->category->id);
        try {
            $guestCategory = $app->bootComponent('com_jlms')->getCatalogService()->category(
                Factory::getUser(0), $app->getLanguage()->getTag(), (int) $this->category->id
            );
        } catch (\RuntimeException $error) {
            $guestCategory = null;
        }
        if ($guestCategory) {
            PublicMetadataHelper::apply(
                $this->getDocument(),
                (string) $this->category->title,
                (string) ($this->category->description ?? ''),
                'index.php?option=com_jlms&view=category&id=' . (int) $this->category->id
            );
        } else {
            $this->getDocument()->setTitle((string) $this->category->title);
            $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
            $app->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
        }
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
