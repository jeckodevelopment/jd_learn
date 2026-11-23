<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section id="jlms-preferences-view" class="jlms-dashboard jlms-preferences-view">
 <?php $currentView = 'preferences';
    require __DIR__ . '/../dashboard/_personal_navigation.php'; ?>
 <header><h1><?= Text::_('COM_JLMS_PREFERENCES') ?></h1></header>
 <?php require __DIR__ . '/../dashboard/_preferences.php'; ?>
 <a href="<?= Route::_('index.php?option=com_jlms&view=mycourses') ?>"><?= Text::_('COM_JLMS_MY_COURSES') ?></a>
</section>
