<?php

declare(strict_types=1);

\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section id="jlms-courses" class="jlms-catalog">
 <header id="jlms-catalog-header"><h1><?= Text::_('COM_JLMS_COURSES') ?></h1><a id="jlms-catalog-categories" href="<?= Route::_('index.php?option=com_jlms&view=categories') ?>"><?= Text::_('COM_JLMS_CATEGORIES') ?></a></header>
 <?php if ($this->categoryId) : ?><p id="jlms-category-filter"><?= Text::_('COM_JLMS_CATEGORY_FILTER_ACTIVE') ?> <a href="<?= Route::_('index.php?option=com_jlms&view=courses') ?>"><?= Text::_('COM_JLMS_ALL_COURSES') ?></a></p><?php endif; ?>
 <div id="jlms-course-grid" class="jlms-course-grid">
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
