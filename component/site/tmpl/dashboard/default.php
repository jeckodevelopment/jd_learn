<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section id="jlms-dashboard" class="jlms-dashboard jlms-learner-overview">
 <?php $currentView = 'dashboard';
    require __DIR__ . '/../dashboard/_personal_navigation.php'; ?>
 <header id="jlms-dashboard-header">
  <h1><?= Text::_('COM_JLMS_MY_TRAINING') ?></h1>
  <p><?= Text::_('COM_JLMS_MY_TRAINING_INTRO') ?></p>
 </header>
 <div id="jlms-dashboard-summary" class="jlms-my-course-grid">
  <section class="jlms-card jlms-my-course" aria-labelledby="jlms-summary-courses-title">
   <h2 id="jlms-summary-courses-title"><?= Text::_('COM_JLMS_MY_COURSES') ?></h2>
   <dl class="jlms-summary-stats">
    <div><dt><?= Text::_('COM_JLMS_DASHBOARD_NOT_STARTED') ?></dt><dd><?=
         (int) $this->summary['not_started'] ?></dd></div>
    <div><dt><?= Text::_('COM_JLMS_DASHBOARD_IN_PROGRESS') ?></dt><dd><?=
         (int) $this->summary['in_progress'] ?></dd></div>
    <div><dt><?= Text::_('COM_JLMS_DASHBOARD_COMPLETED') ?></dt><dd><?= (int) $this->summary['completed'] ?></dd></div>
   </dl>
   <a id="jlms-dashboard-my-courses-link"
    href="<?= Route::_('index.php?option=com_jlms&view=mycourses') ?>"><?= Text::_('COM_JLMS_MY_COURSES') ?></a>
  </section>
  <section class="jlms-card jlms-my-course" aria-labelledby="jlms-summary-certificates-title">
   <h2 id="jlms-summary-certificates-title"><?= Text::_('COM_JLMS_MY_CERTIFICATES') ?></h2>
   <p><?= Text::sprintf('COM_JLMS_MY_CERTIFICATES_COUNT', (int) $this->certificateCount) ?></p>
   <a id="jlms-dashboard-my-certificates-link" href="<?=
        Route::_('index.php?option=com_jlms&view=mycertificates') ?>"><?=
        Text::_('COM_JLMS_MY_CERTIFICATES') ?></a>
  </section>
  <section class="jlms-card jlms-my-course" aria-labelledby="jlms-summary-preferences-title">
   <h2 id="jlms-summary-preferences-title"><?= Text::_('COM_JLMS_PREFERENCES') ?></h2>
   <a id="jlms-dashboard-preferences-link"
    href="<?= Route::_('index.php?option=com_jlms&view=preferences') ?>"><?= Text::_('COM_JLMS_PREFERENCES') ?></a>
  </section>
 </div>
 <?php if ($this->summary['latest'] !== null) : ?>
        <?php $recent = $this->summary['latest']; ?>
  <section id="jlms-dashboard-latest" class="jlms-dashboard-group" aria-labelledby="jlms-dashboard-latest-title">
   <h2 id="jlms-dashboard-latest-title"><?= Text::_('COM_JLMS_MY_TRAINING_CONTINUE') ?></h2>
   <p><?= $this->escape($recent['title'] !== '' ? $recent['title'] : Text::_('COM_JLMS_DASHBOARD_HISTORICAL_COURSE')) ?></p>
        <?php if ($recent['percent'] !== null) : ?>
    <p><?= Text::_('COM_JLMS_LEARNING_PROGRESS') ?>: <?= (int) $recent['percent'] ?>%</p>
        <?php endif; ?>
   <a id="jlms-dashboard-resume" href="<?=
        Route::_('index.php?option=com_jlms&task=enrollment.resume&id=' . (int) $recent['id']) ?>"><?=
        Text::_('COM_JLMS_DASHBOARD_RESUME') ?></a>
  </section>
 <?php endif; ?>
 <?php if ((int) $this->summary['not_started'] + (int) $this->summary['in_progress'] + (int) $this->summary['completed'] === 0) : ?>
  <p id="jlms-dashboard-empty"><?= Text::_('COM_JLMS_MY_TRAINING_EMPTY') ?></p>
 <?php endif; ?>
 <a id="jlms-dashboard-catalog"
    href="<?= Route::_('index.php?option=com_jlms&view=courses') ?>"><?= Text::_('COM_JLMS_ALL_COURSES') ?></a>
</section>
