<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
HTMLHelper::_('behavior.formvalidator');
?>
<div id="j-main-container" class="j-main-container">
 <p class="alert alert-info"><?= Text::_('COM_JLMS_QUIZ_AUTHORING_NOTICE') ?></p>
 <div id="jlms-question-form"><form id="adminForm" name="adminForm" method="post" action="<?= Route::_('index.php?option=com_jlms') ?>" class="form-validate">
  <?= $this->form->renderFieldset('details') ?>
  <input type="hidden" name="task" value="questionbank.save">
  <?= HTMLHelper::_('form.token') ?>
 </form></div>
 <?php if ((int)$this->item['id']>0) : ?>
  <p id="jlms-question-lock-status" class="small text-body-secondary"><?= Text::_('COM_JLMS_QUESTION_LOCK') ?>: <?= (int)($this->item['checked_out']??0) ?></p>
  <?php foreach (['checkout','checkin'] as $task) : ?><form method="post" class="d-inline" action="<?= Route::_('index.php?option=com_jlms') ?>"><input type="hidden" name="task" value="questionbank.<?= $task ?>"><input type="hidden" name="jform[id]" value="<?= (int)$this->item['id'] ?>"><?= HTMLHelper::_('form.token') ?><button class="btn btn-secondary" type="submit"><?= Text::_($task==='checkout'?'COM_JLMS_QUESTION_CHECKOUT':'COM_JLMS_QUESTION_CHECKIN') ?></button></form><?php endforeach; ?>
 <?php endif; ?>
</div>
