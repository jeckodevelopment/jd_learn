<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
?>
<section id="jlms-dashboard" class="jlms-dashboard">
 <header id="jlms-dashboard-header"><h1><?= Text::_('COM_JLMS_MY_COURSES') ?></h1><p><?= Text::_('COM_JLMS_DASHBOARD_INTRO') ?></p></header>
 <a id="jlms-dashboard-my-courses-link" href="<?= Route::_('index.php?option=com_jlms&view=mycourses') ?>"><?= Text::_('COM_JLMS_MY_COURSES') ?></a>
 <form id="jlms-dashboard-filters" method="get" action="<?= Route::_('index.php?option=com_jlms&view=dashboard') ?>">
  <?= $this->progressForm->renderField('progress') ?>
  <input type="hidden" name="option" value="com_jlms"><input type="hidden" name="view" value="dashboard">
  <button type="submit" class="btn btn-secondary"><?= Text::_('COM_JLMS_DASHBOARD_FILTER') ?></button>
 </form>
 <?php require __DIR__ . '/_courses.php'; ?>
 <?php if ($this->certificates) : ?>
 <section id="jlms-my-certificates" class="jlms-dashboard-group"><h2><?= Text::_('COM_JLMS_MY_CERTIFICATES') ?></h2>
  <?php $certificates = $this->certificates; require __DIR__ . '/_certificates.php'; ?>
 </section><?php endif; ?>
 <a id="jlms-dashboard-my-certificates-link" href="<?= Route::_('index.php?option=com_jlms&view=mycertificates') ?>"><?= Text::_('COM_JLMS_MY_CERTIFICATES') ?></a>
 <a id="jlms-dashboard-preferences-link" href="<?= Route::_('index.php?option=com_jlms&view=preferences') ?>"><?= Text::_('COM_JLMS_PREFERENCES') ?></a>
 <?php require __DIR__ . '/_preferences.php'; ?>
 <nav id="jlms-dashboard-pagination" aria-label="<?= Text::_('COM_JLMS_PAGINATION') ?>"><?= $this->pagination->getPagesLinks() ?></nav>
 <a id="jlms-dashboard-catalog" href="<?= Route::_('index.php?option=com_jlms&view=courses') ?>"><?= Text::_('COM_JLMS_ALL_COURSES') ?></a>
</section>
