<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section id="jlms-dashboard" class="jlms-dashboard jlms-my-courses-view">
 <?php $currentView = 'mycourses';
    require __DIR__ . '/../dashboard/_personal_navigation.php'; ?>
 <header id="jlms-dashboard-header">
  <h1><?= Text::_('COM_JLMS_MY_COURSES') ?></h1>
  <p><?= Text::_('COM_JLMS_DASHBOARD_INTRO') ?></p>
 </header>
 <form id="jlms-dashboard-filters" method="get" action="<?= Route::_('index.php?option=com_jlms&view=mycourses') ?>">
  <?= $this->progressForm->renderField('progress') ?>
  <input type="hidden" name="option" value="com_jlms"><input type="hidden" name="view" value="mycourses">
  <button type="submit" class="btn btn-secondary"><?= Text::_('COM_JLMS_DASHBOARD_FILTER') ?></button>
 </form>
 <?php require __DIR__ . '/../dashboard/_courses.php'; ?>
 <nav id="jlms-dashboard-pagination" aria-label="<?= Text::_('COM_JLMS_PAGINATION') ?>"><?= $this->pagination->getPagesLinks() ?></nav>
 <?php if ($this->items) :
        ?><a id="jlms-dashboard-catalog" href="<?= Route::_('index.php?option=com_jlms&view=courses') ?>"><?= Text::_('COM_JLMS_ALL_COURSES') ?></a><?php
 endif; ?>
</section>
