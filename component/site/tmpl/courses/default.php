<?php

declare(strict_types=1);

\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section id="jlms-courses" class="jlms-catalog">
 <header id="jlms-catalog-header"><h1><?= Text::_('COM_JLMS_COURSES') ?></h1><a id="jlms-catalog-categories" href="<?= Route::_('index.php?option=com_jlms&view=categories') ?>"><?= Text::_('COM_JLMS_CATEGORIES') ?></a> <a id="jlms-catalog-pathways" href="<?= Route::_('index.php?option=com_jlms&view=pathways') ?>"><?= Text::_('COM_JLMS_PATHWAYS') ?></a></header>
 <form id="jlms-catalog-filters" method="get" action="<?= Route::_('index.php?option=com_jlms&view=courses' . ($this->categoryId ? '&catid=' . $this->categoryId : '')) ?>">
  <input type="hidden" name="option" value="com_jlms"><input type="hidden" name="view" value="courses">
  <?php if ($this->categoryId) : ?><input type="hidden" name="catid" value="<?= $this->categoryId ?>"><?php endif; ?>
  <label for="jlms-catalog-search"><?= Text::_('COM_JLMS_CATALOG_SEARCH') ?></label>
  <input id="jlms-catalog-search" type="search" name="filter[search]" value="<?= $this->escape($this->search) ?>" maxlength="100">
  <label for="jlms-catalog-order"><?= Text::_('COM_JLMS_CATALOG_ORDER') ?></label>
  <select id="jlms-catalog-order" name="filter[order]"><option value="recent"<?= $this->order === 'recent' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_ORDER_RECENT') ?></option><option value="title"<?= $this->order === 'title' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_ORDER_TITLE') ?></option></select>
  <label for="jlms-catalog-tag"><?= Text::_('COM_JLMS_CATALOG_TAG') ?></label>
  <select id="jlms-catalog-tag" name="filter[tag_id]"><option value="0"><?= Text::_('COM_JLMS_CATALOG_ALL_TAGS') ?></option><?php foreach ($this->tags as $tag) : ?><option value="<?= (int) $tag->id ?>"<?= $this->tagId === (int) $tag->id ? ' selected' : '' ?>><?= $this->escape($tag->title) ?></option><?php endforeach; ?></select>
<label for="jlms-catalog-difficulty"><?= Text::_('COM_JLMS_CATALOG_DIFFICULTY') ?></label>
  <select id="jlms-catalog-difficulty" name="filter[difficulty]"><option value=""><?= Text::_('COM_JLMS_CATALOG_ANY_DIFFICULTY') ?></option><option value="beginner"<?= $this->difficulty === 'beginner' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DIFFICULTY_BEGINNER') ?></option><option value="intermediate"<?= $this->difficulty === 'intermediate' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DIFFICULTY_INTERMEDIATE') ?></option><option value="advanced"<?= $this->difficulty === 'advanced' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DIFFICULTY_ADVANCED') ?></option></select>
  <label for="jlms-catalog-duration"><?= Text::_('COM_JLMS_CATALOG_DURATION') ?></label>
  <select id="jlms-catalog-duration" name="filter[duration]"><option value=""><?= Text::_('COM_JLMS_CATALOG_ANY_DURATION') ?></option><option value="under-30"<?= $this->duration === 'under-30' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DURATION_UNDER_30') ?></option><option value="30-60"<?= $this->duration === '30-60' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DURATION_30_60') ?></option><option value="61-120"<?= $this->duration === '61-120' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DURATION_61_120') ?></option><option value="over-120"<?= $this->duration === 'over-120' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DURATION_OVER_120') ?></option></select>
  <label for="jlms-catalog-layout"><?= Text::_('COM_JLMS_CATALOG_LAYOUT') ?></label>
  <select id="jlms-catalog-layout" name="filter[layout]"><option value="grid"<?= $this->layout === 'grid' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_LAYOUT_GRID') ?></option><option value="list"<?= $this->layout === 'list' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_LAYOUT_LIST') ?></option></select>
  <button type="submit"><?= Text::_('COM_JLMS_CATALOG_APPLY') ?></button>
 </form>
 <?php if ($this->categoryId) : ?><p id="jlms-category-filter"><?= Text::_('COM_JLMS_CATEGORY_FILTER_ACTIVE') ?> <a href="<?= Route::_('index.php?option=com_jlms&view=courses') ?>"><?= Text::_('COM_JLMS_ALL_COURSES') ?></a></p><?php endif; ?>
 <div id="jlms-course-grid" class="jlms-course-grid<?= $this->layout === 'list' ? ' jlms-course-grid--list' : '' ?>" data-layout="<?= $this->escape($this->layout) ?>">
 <?php foreach ($this->items as $item) : ?>
  <article id="jlms-course-card-<?= (int) $item->id ?>" class="jlms-course-card" data-course-id="<?= (int) $item->id ?>">
   <h2 class="jlms-course-card__title"><a href="<?= Route::_('index.php?option=com_jlms&view=course&id=' . (int) $item->id) ?>"><?= $this->escape($item->title) ?></a></h2>
   <a class="jlms-course-card__category" href="<?= Route::_('index.php?option=com_jlms&view=category&id=' . (int) $item->catid) ?>"><?= $this->escape($item->category_title) ?></a>
   <p class="jlms-course-card__summary"><?= $this->escape(\Joomla\String\StringHelper::substr((string) $item->summary, 0, 300)) ?></p>
  </article>
 <?php endforeach; ?>
 <?php if (!$this->items) : ?><p id="jlms-catalog-empty"><?= Text::_('COM_JLMS_NO_PUBLISHED_COURSES') ?></p><?php endif; ?>
 </div>
 <nav id="jlms-catalog-pagination" aria-label="<?= Text::_('COM_JLMS_PAGINATION') ?>"><?= $this->pagination->getPagesLinks() ?></nav>
</section>
