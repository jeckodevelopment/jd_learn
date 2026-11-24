<?php

/** Shared FE-05 learner course cards; $this holds the active Joomla HtmlView. */

\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
 <div id="jlms-my-courses">
 <?php foreach (['not_started', 'in_progress', 'completed'] as $state) : ?>
        <?php $cards = array_filter($this->items, static fn ($item): bool => $item['learning_state'] === $state);
        if (!$cards) {
            continue;
        } ?>
  <section id="jlms-dashboard-<?= str_replace('_', '-', $state) ?>"
    class="jlms-dashboard-group"><h2><?= Text::_(implode('_', ['COM', 'JLMS', 'DASHBOARD', strtoupper($state)])) ?></h2>
   <div class="jlms-my-course-grid"><?php foreach ($cards as $item) : ?>
    <article id="jlms-my-course-<?= (int) $item['id'] ?>"
    class="jlms-card jlms-my-course"
    data-enrollment-id="<?= (int) $item['id'] ?>">
     <h3 class="jlms-my-course__title"><?=
        $this->escape($item['title'] !== '' ? $item['title'] : Text::_('COM_JLMS_DASHBOARD_HISTORICAL_COURSE')) ?></h3>
     <p class="jlms-my-course__access"><?=
        Text::_(implode('_', ['COM', 'JLMS', 'DASHBOARD', 'ACCESS', strtoupper($item['access'])])) ?></p>
        <?php if ($item['percent'] !== null) : ?>
      <label for="jlms-my-course-progress-<?=
         (int) $item['id'] ?>"><?=
         Text::_('COM_JLMS_LEARNING_PROGRESS') ?>: <?=
         (int) $item['percent'] ?>%</label>
      <progress id="jlms-my-course-progress-<?= (int) $item['id'] ?>"
    class="jlms-my-course__progress"
    max="100"
    value="<?= (int) $item['percent'] ?>"><?= (int) $item['percent'] ?>%</progress>
        <?php else :
            ?><p><?= Text::_(!empty($item['progress_applicable']) ? 'COM_JLMS_DASHBOARD_PROGRESS_UNKNOWN' : 'COM_JLMS_DASHBOARD_PROGRESS_NOT_APPLICABLE') ?></p><?php
        endif; ?>
        <?php if ($item['access'] === 'available') :
            ?><a id="jlms-my-course-resume-<?=
               (int) $item['id'] ?>" class="jlms-my-course__action" href="<?=
               Route::_('index.php?option=com_jlms&task=enrollment.resume&id=' . (int) $item['id']) ?>"><?=
               Text::_($item['completed'] ? 'COM_JLMS_DASHBOARD_REVIEW' : 'COM_JLMS_DASHBOARD_RESUME') ?></a><?php
        endif; ?>
    </article>
                                    <?php endforeach; ?></div>
  </section>
 <?php endforeach; ?>
 <?php if (!$this->items) :
        ?><p id="jlms-dashboard-empty"><?= Text::_('COM_JLMS_DASHBOARD_EMPTY') ?> <a id="jlms-dashboard-empty-catalog"
    href="<?= Route::_('index.php?option=com_jlms&view=courses') ?>"><?= Text::_('COM_JLMS_ALL_COURSES') ?></a></p><?php
 endif; ?>
 </div>
