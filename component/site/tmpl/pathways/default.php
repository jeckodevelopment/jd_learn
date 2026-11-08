<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section id="jlms-pathways" class="jlms-catalog">
 <header id="jlms-pathways-header"><h1><?= Text::_('COM_JLMS_PATHWAYS') ?></h1></header>
 <div id="jlms-pathway-grid" class="jlms-course-grid">
 <?php foreach ($this->items as $item) : ?>
  <article id="jlms-pathway-<?= (int) $item->id ?>" class="jlms-course-card">
   <h2><a href="<?= Route::_('index.php?option=com_jlms&view=pathway&id=' . (int) $item->id) ?>"><?= $this->escape($item->title) ?></a></h2>
   <p class="jlms-course-card__category"><?= $this->escape($item->category_title) ?></p>
   <div class="content"><?= $item->description ?></div>
  </article>
 <?php endforeach; ?>
 <?php if (!$this->items) : ?><p id="jlms-pathways-empty"><?= Text::_('COM_JLMS_PATHWAYS_EMPTY') ?></p><?php endif; ?>
 </div>
 <nav id="jlms-pathways-pagination" aria-label="<?= Text::_('COM_JLMS_PAGINATION') ?>"><?= $this->pagination->getPagesLinks() ?></nav>
</section>
