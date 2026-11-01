<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section id="jlms-categories" class="jlms-categories">
 <header id="jlms-categories-header"><h1><?= Text::_('COM_JLMS_CATEGORIES') ?><?php if ($this->parent) : ?>: <?= $this->escape($this->parent->title) ?><?php endif; ?></h1></header>
 <div id="jlms-categories-description"><p><?= Text::_('COM_JLMS_CATEGORIES_INTRO') ?></p></div>
 <div id="jlms-categories-list" class="jlms-category-grid">
 <?php foreach ($this->items as $item) : ?>
  <article id="jlms-category-card-<?= (int) $item->id ?>" class="jlms-category-card" data-category-id="<?= (int) $item->id ?>">
   <h2 class="jlms-category-card__title"><a href="<?= Route::_('index.php?option=com_jlms&view=category&id=' . (int) $item->id) ?>"><?= $this->escape($item->title) ?></a></h2>
   <p class="jlms-category-card__description"><?= $this->escape(\Joomla\String\StringHelper::substr(strip_tags((string) $item->description), 0, 300)) ?></p>
  </article>
 <?php endforeach; ?>
 <?php if (!$this->items) : ?><p id="jlms-categories-empty"><?= Text::_('COM_JLMS_NO_VISIBLE_CATEGORIES') ?></p><?php endif; ?>
 </div>
 <nav id="jlms-categories-pagination" aria-label="<?= Text::_('COM_JLMS_PAGINATION') ?>"><?= $this->pagination->getPagesLinks() ?></nav>
 <?php if ($this->parent) : ?><a id="jlms-categories-parent" href="<?= Route::_('index.php?option=com_jlms&view=category&id=' . (int) $this->parent->id) ?>"><?= $this->escape($this->parent->title) ?></a><?php endif; ?>
 <a id="jlms-categories-catalog" href="<?= Route::_('index.php?option=com_jlms&view=courses') ?>"><?= Text::_('COM_JLMS_ALL_COURSES') ?></a>
</section>
