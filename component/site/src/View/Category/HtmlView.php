<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Category;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Jlms\Component\Jlms\Site\Helper\CategoryNavigationHelper;
use Jlms\Component\Jlms\Site\Helper\PublicMetadataHelper;

final class HtmlView extends BaseHtmlView
{
    public object $category;
    public array $items = [];
    public array $children = [];
    public $pagination;
    public $childrenPagination;
    public string $search = '';
    public string $order = 'recent';
    public int $tagId = 0;
    public array $tags = [];
public string $difficulty = '';
    public string $duration = '';
    public string $layout = 'grid';

    public function display($tpl = null)
    {
        $this->category = $this->getModel()->getCategory();
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->search = $this->getModel()->getSearch();
        $this->order = $this->getModel()->getOrder();
        $this->tagId = $this->getModel()->getTagId();
        $this->tags = $this->getModel()->getTags();
$this->difficulty = $this->getModel()->getDifficulty();
        $this->duration = $this->getModel()->getDuration();
        $this->layout = $this->getModel()->getLayout();
        if ($this->get('Errors')) { throw new \RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 500); }
        $app = Factory::getApplication();
        $model = $app->bootComponent('com_jlms')->getMVCFactory()->createModel('Categories', 'Site', ['ignore_request' => true]);
        $model->setState('filter.parent_id', (int) $this->category->id);
        $model->setState('list.limit', 20);
        $model->setState('list.start', max(0, $app->getInput()->getInt('subcategories_limitstart', 0)));
        $model->setState('pagination.prefix', 'subcategories_');
        $this->children = $model->getItems();
        $this->childrenPagination = $model->getPagination();
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
        }
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
