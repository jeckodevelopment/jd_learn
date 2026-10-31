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
  <div id="jlms-category-course-grid" class="jlms-course-grid"><?php foreach ($this->items as $item) : ?>
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
