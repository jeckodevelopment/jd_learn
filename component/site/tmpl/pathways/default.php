<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Jlms\Component\Jlms\Administrator\Application\PathwayArtwork;
?>
<section id="jlms-pathways" class="jlms-catalog jlms-pathways-catalog">
 <header id="jlms-pathways-header"><h1><?= Text::_('COM_JLMS_PATHWAYS') ?></h1></header>
 <div id="jlms-pathway-grid" class="jlms-course-grid">
 <?php foreach ($this->items as $item) :
        $cover = PathwayArtwork::fromParams((string) ($item->params_json ?? '{}'));
        ?>
  <article id="jlms-pathway-<?= (int) $item->id ?>" class="jlms-card jlms-course-card jlms-pathway-card">
        <?php if ($cover['image'] !== '') : ?>
   <div class="jlms-pathway-card__cover">
    <img src="<?= $this->escape(Uri::root() . implode('/', array_map('rawurlencode', explode('/', $cover['image'])))) ?>" alt="<?=
       $this->escape($cover['alt']) ?>" loading="lazy" decoding="async">
   </div>
        <?php endif; ?>
   <div class="jlms-pathway-card__content">
    <p class="jlms-pathway-card__category"><?= $this->escape($item->category_title) ?></p>
    <h2 class="jlms-pathway-card__title"><a href="<?= Route::_('index.php?option=com_jlms&view=pathway&id='
        . (int) $item->id) ?>"><?= $this->escape($item->title) ?></a></h2>
        <?php if (trim(strip_tags((string) $item->description)) !== '') : ?>
     <p class="jlms-pathway-card__summary"><?=
        $this->escape(\Joomla\String\StringHelper::substr(trim(strip_tags((string) $item->description)), 0, 300)) ?></p>
        <?php endif; ?>
   </div>
  </article>
 <?php endforeach; ?>
 <?php if (!$this->items) :
        ?><p id="jlms-pathways-empty"><?= Text::_('COM_JLMS_PATHWAYS_EMPTY') ?></p><?php
 endif; ?>
 </div>
 <nav id="jlms-pathways-pagination" aria-label="<?= Text::_('COM_JLMS_PAGINATION') ?>"><?= $this->pagination
     ->getPagesLinks() ?></nav>
</section>
