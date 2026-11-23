<?php
declare(strict_types=1);
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section id="jlms-my-certificates-view" class="jlms-dashboard jlms-my-certificates-view">
 <?php $currentView = 'mycertificates';
    require __DIR__ . '/../dashboard/_personal_navigation.php'; ?>
 <header><h1><?= Text::_('COM_JLMS_MY_CERTIFICATES') ?></h1></header>
 <?php if ($this->items) : ?>
        <?php $certificates = $this->items;
        require __DIR__ . '/../dashboard/_certificates.php'; ?>
 <?php else : ?>
  <p><?= Text::_('COM_JLMS_MY_CERTIFICATES_EMPTY') ?></p>
 <?php endif; ?>
 <a href="<?= Route::_('index.php?option=com_jlms&view=mycourses') ?>"><?= Text::_('COM_JLMS_MY_COURSES') ?></a>
</section>
