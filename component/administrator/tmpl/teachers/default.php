<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
?>
<form action="<?= Route::_('index.php?option=com_jlms&view=teachers') ?>" method="post" id="adminForm" name="adminForm">
 <section id="jlms-admin-teachers"><h1><?= Text::_('COM_JLMS_TEACHERS') ?></h1>
 <table class="table"><caption class="visually-hidden"><?= Text::_('COM_JLMS_TEACHERS') ?></caption><thead><tr><th scope="col"><?= Text::_('JGLOBAL_TITLE') ?></th><th scope="col"><?= Text::_('JSTATUS') ?></th><th scope="col"><?= Text::_('COM_JLMS_REVISION') ?></th></tr></thead>
 <tbody><?php foreach ($this->items as $item) : ?><tr><td><a href="<?= Route::_('index.php?option=com_jlms&task=teacher.edit&id=' . (int) $item->id) ?>"><?= $this->escape($item->title) ?></a></td><td><?= Text::_((int) $item->state === 1 ? 'JPUBLISHED' : 'JUNPUBLISHED') ?></td><td><?= (int) $item->revision ?></td></tr><?php endforeach; ?></tbody></table>
 <?= $this->pagination->getListFooter() ?></section><input type="hidden" name="task" value=""><?= HTMLHelper::_('form.token') ?>
</form>
