<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
?>
<section id="jlms-teachers" class="jlms-catalog jlms-teachers-catalog">
 <header id="jlms-teachers-header"><h1><?= Text::_('COM_JLMS_TEACHERS') ?></h1></header>
 <div id="jlms-teacher-grid" class="jlms-course-grid">
 <?php foreach ($this->items as $teacher) :
        $params = json_decode((string) ($teacher->params_json ?? '{}'), true);
        $params = is_array($params) ? $params : [];
        $image = (string) ($teacher->image ?? '');
     // Only local media paths can be emitted by public templates.
        $validImage = $image !== '' && !str_starts_with($image, '/') && !str_contains($image, '..')
         && !preg_match('/[\\\\:\\x00-\\x1f?#]/', $image);
        $summary = trim((string) ($teacher->bio ?? ''));
        ?>
  <article class="jlms-card jlms-course-card jlms-teacher-card">
        <?php if ($validImage) : ?>
   <div class="jlms-teacher-card__cover"><img src="<?= $this->escape(Uri::root()
       . implode('/', array_map('rawurlencode', explode('/', $image)))) ?>"
           alt="<?= !empty($params['image_decorative']) ? '' : $this->escape((string) ($params['image_alt'] ?? '')) ?>"
           loading="lazy"
           decoding="async"></div>
        <?php endif; ?>
   <div class="jlms-teacher-card__content">
    <h2 class="jlms-teacher-card__title"><a href="<?= Route::_('index.php?option=com_jlms&view=teacher&id='
        . (int) $teacher->id) ?>"><?= $this->escape($teacher->title) ?></a></h2>
        <?php if ($summary !== '') :
            ?><p class="jlms-teacher-card__summary"><?= $this->escape(\Joomla\String\StringHelper::substr(strip_tags($summary), 0, 300)) ?></p><?php
        endif; ?>
   </div>
  </article>
 <?php endforeach; ?>
 <?php if (!$this->items) :
        ?><p id="jlms-teachers-empty"><?= Text::_('COM_JLMS_TEACHERS_EMPTY') ?></p><?php
 endif; ?>
 </div>
 <nav id="jlms-teachers-pagination" aria-label="<?= Text::_('COM_JLMS_PAGINATION') ?>"><?= $this->pagination
     ->getPagesLinks() ?></nav>
</section>
