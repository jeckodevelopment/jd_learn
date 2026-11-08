<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<article id="jlms-pathway" class="jlms-course-page">
 <header id="jlms-pathway-header">
  <h1><?= $this->escape($this->item['pathway']->title) ?></h1>
  <div class="content"><?= $this->item['pathway']->description ?></div>
  <?php if ($this->item['progress'] !== null) : ?>
   <p id="jlms-pathway-progress-label"><?= Text::_('COM_JLMS_PATHWAY_PROGRESS') ?>: <?= (int) $this->item['progress'] ?>%</p>
   <progress id="jlms-pathway-progress" max="100" value="<?= (int) $this->item['progress'] ?>"><?= (int) $this->item['progress'] ?>%</progress>
   <?php if ($this->item['has_unavailable_courses']) : ?><p id="jlms-pathway-unavailable"><?= Text::_('COM_JLMS_PATHWAY_HAS_UNAVAILABLE') ?></p><?php endif; ?>
  <?php endif; ?>
 </header>
 <ol id="jlms-pathway-courses" class="jlms-course-list">
 <?php foreach ($this->item['courses'] as $index => $course) : ?>
  <li id="jlms-pathway-course-<?= (int) $index ?>" class="jlms-course-list__item">
   <?php if ($course['id'] !== null) : ?>
    <h2><a href="<?= Route::_('index.php?option=com_jlms&view=course&id=' . (int) $course['id']) ?>"><?= $this->escape($course['title']) ?></a></h2>
    <p><?= $this->escape(\Joomla\String\StringHelper::substr((string) ($course['summary'] ?? ''), 0, 300)) ?></p>
    <?php $stateKey = match ($course['learning_state']) {
        'completed' => 'COM_JLMS_PATHWAY_COURSE_STATE_COMPLETED',
        'in_progress' => 'COM_JLMS_PATHWAY_COURSE_STATE_IN_PROGRESS',
        'not_started' => 'COM_JLMS_PATHWAY_COURSE_STATE_NOT_STARTED',
        default => 'COM_JLMS_PATHWAY_COURSE_STATE_NOT_ENROLLED',
    }; ?>
    <p><?= Text::_($stateKey) ?></p>
    <?php if ($course['access'] === 'available') : ?>
     <label for="jlms-pathway-course-progress-<?= (int) $index ?>"><?= Text::_('COM_JLMS_LEARNING_PROGRESS') ?>: <?= (int) $course['percent'] ?>%</label>
     <progress id="jlms-pathway-course-progress-<?= (int) $index ?>" max="100" value="<?= (int) $course['percent'] ?>"><?= (int) $course['percent'] ?>%</progress>
    <?php else : ?><p><?= Text::_('COM_JLMS_PATHWAY_COURSE_RESTRICTED') ?></p><?php endif; ?>
   <?php else : ?><p><?= Text::_('COM_JLMS_PATHWAY_COURSE_UNAVAILABLE') ?></p><?php endif; ?>
  </li>
 <?php endforeach; ?>
 </ol>
 <?php if ($this->item['required_courses'] === 0) : ?><p id="jlms-pathway-empty"><?= Text::_('COM_JLMS_PATHWAY_NO_COURSES') ?></p><?php endif; ?>
 <a id="jlms-pathway-catalog" href="<?= Route::_('index.php?option=com_jlms&view=courses') ?>"><?= Text::_('COM_JLMS_COURSES') ?></a>
</article>
