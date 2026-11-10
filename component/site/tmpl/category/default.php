<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section id="jlms-category" class="jlms-category" data-category-id="<?= (int) $this->category->id ?>">
 <header id="jlms-category-header"><h1 id="jlms-category-title"><?= $this->escape($this->category->title) ?></h1></header>
 <div id="jlms-category-description"><?= nl2br($this->escape(strip_tags((string) $this->category->description))) ?></div>
 <section id="jlms-subcategories"><h2><?= Text::_('COM_JLMS_SUBCATEGORIES') ?></h2>
  <ul id="jlms-subcategories-list"><?php foreach ($this->children as $child) : ?>
   <li id="jlms-subcategory-<?= (int) $child->id ?>" class="jlms-subcategory"><a href="<?= Route::_('index.php?option=com_jlms&view=category&id=' . (int) $child->id) ?>"><?= $this->escape($child->title) ?></a></li>
  <?php endforeach; ?></ul>
  <?php if (!$this->children) : ?><p id="jlms-subcategories-empty"><?= Text::_('COM_JLMS_NO_VISIBLE_CATEGORIES') ?></p><?php endif; ?>
  <nav id="jlms-subcategories-pagination" aria-label="<?= Text::_('COM_JLMS_SUBCATEGORIES') ?>"><?= $this->childrenPagination->getPagesLinks() ?></nav>
 </section>
 <section id="jlms-category-courses"><h2><?= Text::_('COM_JLMS_COURSES') ?></h2><p><?= Text::_('COM_JLMS_CATEGORY_DESCENDANT_COURSES') ?></p>
  <form id="jlms-category-catalog-filters" method="get" action="<?= Route::_('index.php?option=com_jlms&view=category&id=' . (int) $this->category->id) ?>">
   <input type="hidden" name="option" value="com_jlms"><input type="hidden" name="view" value="category"><input type="hidden" name="id" value="<?= (int) $this->category->id ?>">
   <label for="jlms-category-catalog-search"><?= Text::_('COM_JLMS_CATALOG_SEARCH') ?></label>
   <input id="jlms-category-catalog-search" type="search" name="filter[search]" value="<?= $this->escape($this->search) ?>" maxlength="100">
   <label for="jlms-category-catalog-order"><?= Text::_('COM_JLMS_CATALOG_ORDER') ?></label>
   <select id="jlms-category-catalog-order" name="filter[order]"><option value="recent"<?= $this->order === 'recent' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_ORDER_RECENT') ?></option><option value="title"<?= $this->order === 'title' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_ORDER_TITLE') ?></option></select>
   <label for="jlms-category-catalog-tag"><?= Text::_('COM_JLMS_CATALOG_TAG') ?></label>
   <select id="jlms-category-catalog-tag" name="filter[tag_id]"><option value="0"><?= Text::_('COM_JLMS_CATALOG_ALL_TAGS') ?></option><?php foreach ($this->tags as $tag) : ?><option value="<?= (int) $tag->id ?>"<?= $this->tagId === (int) $tag->id ? ' selected' : '' ?>><?= $this->escape($tag->title) ?></option><?php endforeach; ?></select>
<label for="jlms-category-catalog-difficulty"><?= Text::_('COM_JLMS_CATALOG_DIFFICULTY') ?></label>
   <select id="jlms-category-catalog-difficulty" name="filter[difficulty]"><option value=""><?= Text::_('COM_JLMS_CATALOG_ANY_DIFFICULTY') ?></option><option value="beginner"<?= $this->difficulty === 'beginner' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DIFFICULTY_BEGINNER') ?></option><option value="intermediate"<?= $this->difficulty === 'intermediate' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DIFFICULTY_INTERMEDIATE') ?></option><option value="advanced"<?= $this->difficulty === 'advanced' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DIFFICULTY_ADVANCED') ?></option></select>
   <label for="jlms-category-catalog-duration"><?= Text::_('COM_JLMS_CATALOG_DURATION') ?></label>
   <select id="jlms-category-catalog-duration" name="filter[duration]"><option value=""><?= Text::_('COM_JLMS_CATALOG_ANY_DURATION') ?></option><option value="under-30"<?= $this->duration === 'under-30' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DURATION_UNDER_30') ?></option><option value="30-60"<?= $this->duration === '30-60' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DURATION_30_60') ?></option><option value="61-120"<?= $this->duration === '61-120' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DURATION_61_120') ?></option><option value="over-120"<?= $this->duration === 'over-120' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DURATION_OVER_120') ?></option></select>
   <label for="jlms-category-catalog-layout"><?= Text::_('COM_JLMS_CATALOG_LAYOUT') ?></label>
   <select id="jlms-category-catalog-layout" name="filter[layout]"><option value="grid"<?= $this->layout === 'grid' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_LAYOUT_GRID') ?></option><option value="list"<?= $this->layout === 'list' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_LAYOUT_LIST') ?></option></select>
   <label for="jlms-category-catalog-teacher"><?= Text::_('COM_JLMS_CATALOG_TEACHER') ?></label>
   <select id="jlms-category-catalog-teacher" name="filter[teacher_id]"><option value="0"><?= Text::_('COM_JLMS_CATALOG_ANY_TEACHER') ?></option><?php foreach ($this->teachers as $teacher) : ?><option value="<?= (int) $teacher->id ?>"<?= $this->teacherId === (int) $teacher->id ? ' selected' : '' ?>><?= $this->escape($teacher->title) ?></option><?php endforeach; ?></select>
   <label for="jlms-category-catalog-pathway"><?= Text::_('COM_JLMS_CATALOG_PATHWAY') ?></label>
   <select id="jlms-category-catalog-pathway" name="filter[pathway_id]"><option value="0"><?= Text::_('COM_JLMS_CATALOG_ANY_PATHWAY') ?></option><?php foreach ($this->pathways as $pathway) : ?><option value="<?= (int) $pathway->id ?>"<?= $this->pathwayId === (int) $pathway->id ? ' selected' : '' ?>><?= $this->escape($pathway->title) ?></option><?php endforeach; ?></select>
   <button type="submit"><?= Text::_('COM_JLMS_CATALOG_APPLY') ?></button>
  </form>
  <div id="jlms-category-course-grid" class="jlms-course-grid<?= $this->layout === 'list' ? ' jlms-course-grid--list' : '' ?>" data-layout="<?= $this->escape($this->layout) ?>"><?php foreach ($this->items as $item) : ?>
   <article id="jlms-course-card-<?= (int) $item->id ?>" class="jlms-course-card" data-course-id="<?= (int) $item->id ?>">
    <h3 class="jlms-course-card__title"><a href="<?= Route::_('index.php?option=com_jlms&view=course&id=' . (int) $item->id) ?>"><?= $this->escape($item->title) ?></a></h3>
    <p class="jlms-course-card__summary"><?= $this->escape(\Joomla\String\StringHelper::substr((string) $item->summary, 0, 300)) ?></p>
   </article>
  <?php endforeach; ?></div>
  <?php if (!$this->items) : ?><p id="jlms-category-courses-empty"><?= Text::_('COM_JLMS_NO_PUBLISHED_COURSES') ?></p><?php endif; ?>
  <nav id="jlms-category-courses-pagination" aria-label="<?= Text::_('COM_JLMS_PAGINATION') ?>"><?= $this->pagination->getPagesLinks() ?></nav>
 </section>
 <a id="jlms-category-back" href="<?= Route::_('index.php?option=com_jlms&view=categories') ?>"><?= Text::_('COM_JLMS_CATEGORIES') ?></a>
</section>
