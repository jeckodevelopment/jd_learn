<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
HTMLHelper::_('behavior.formvalidator');
?>
<form id="jlms-teacher-profile-form" class="form-validate" action="<?= Route::_('index.php?option=com_jlms&task=teacher.save&id=' . (int) $this->item->id) ?>" method="post">
 <h1><?= Text::_('COM_JLMS_TEACHER_EDIT_PROFILE') ?></h1>
 <?= $this->form->renderFieldset('details') ?>
 <?php foreach ($this->form->getFieldsets('com_fields') as $fieldset) : ?>
  <fieldset><legend><?= $this->escape(Text::_($fieldset->label)) ?></legend><?= $this->form->renderFieldset($fieldset->name) ?></fieldset>
 <?php endforeach; ?>
 <input type="hidden" name="task" value="teacher.save">
 <?= HTMLHelper::_('form.token') ?>
 <button class="btn btn-primary" type="submit"><?= Text::_('JSAVE') ?></button>
</form>
