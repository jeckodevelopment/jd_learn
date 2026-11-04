<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
HTMLHelper::_('behavior.formvalidator');
?>
<form action="<?= Route::_('index.php?option=com_jlms&layout=edit&id=' . (int) $this->item->id) ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
 <section id="jlms-teacher-editor"><p><?= Text::_('COM_JLMS_TEACHER_NOTICE') ?></p><?= $this->form->renderFieldset('details') ?></section>
 <?php foreach ($this->form->getFieldsets('com_fields') as $fieldset) : ?>
  <fieldset><legend><?= $this->escape(Text::_($fieldset->label)) ?></legend><?= $this->form->renderFieldset($fieldset->name) ?></fieldset>
 <?php endforeach; ?>
 <input type="hidden" name="task" value=""><?= HTMLHelper::_('form.token') ?>
</form>
