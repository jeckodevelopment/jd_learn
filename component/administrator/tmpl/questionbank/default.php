<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('behavior.formvalidator');
?>
<section id="jlms-question-bank">
 <h1><?= Text::_('COM_JLMS_QUESTION_BANK') ?></h1>
 <p class="alert alert-info"><?= Text::_('COM_JLMS_QUIZ_AUTHORING_NOTICE') ?></p>
 <p><a href="<?= Route::_('index.php?option=com_jlms&view=questionbank') ?>"><?= Text::_('COM_JLMS_QUESTION_NEW') ?></a> · <a href="<?= Route::_('index.php?option=com_categories&extension=com_jlms.question') ?>"><?= Text::_('JCATEGORIES') ?></a></p>
 <table id="jlms-question-list" class="table"><thead><tr><th><?= Text::_('COM_JLMS_QUESTION_PROMPT') ?></th><th><?= Text::_('JCATEGORY') ?></th><th><?= Text::_('COM_JLMS_QUESTION_REVISION_ID') ?></th><th><?= Text::_('JSTATUS') ?></th></tr></thead><tbody>
 <?php foreach($this->bank['items']as$row): ?>
  <tr><td><a href="<?= Route::_('index.php?option=com_jlms&view=questionbank&id='.(int)$row->id) ?>"><?= $this->escape(\Joomla\String\StringHelper::substr(strip_tags($row->prompt),0,160)) ?></a></td><td><?= $this->escape($row->category_title) ?></td><td><?= (int)$row->revision_id ?> (v<?= (int)$row->revision ?>)</td><td><?= Text::_($row->state?'JPUBLISHED':'JUNPUBLISHED') ?></td></tr>
 <?php endforeach; ?></tbody></table>
 <?php if($this->bank['next_cursor']): ?><a href="<?= Route::_('index.php?option=com_jlms&view=questionbank&before='.(int)$this->bank['next_cursor']) ?>"><?= Text::_('JNEXT') ?></a><?php endif; ?>
 <form id="jlms-question-form" method="post" class="form-validate" action="<?= Route::_('index.php?option=com_jlms') ?>">
  <?= $this->form->renderFieldset('details') ?><input type="hidden" name="task" value="questionbank.save">
  <?= HTMLHelper::_('form.token') ?><button class="btn btn-primary" type="submit"><?= Text::_('JSAVE') ?></button>
 </form>
 <?php if($this->item['id']): ?>
 <p id="jlms-question-lock-status"><?= Text::_('COM_JLMS_QUESTION_LOCK') ?>: <?= (int)($this->item['checked_out']??0) ?></p>
 <?php foreach(['checkout','checkin']as$task): ?><form method="post" class="d-inline" action="<?= Route::_('index.php?option=com_jlms') ?>"><input type="hidden" name="task" value="questionbank.<?= $task ?>"><input type="hidden" name="jform[id]" value="<?= (int)$this->item['id'] ?>"><?= HTMLHelper::_('form.token') ?><button class="btn btn-secondary" type="submit"><?= Text::_($task==='checkout'?'COM_JLMS_QUESTION_CHECKOUT':'COM_JLMS_QUESTION_CHECKIN') ?></button></form><?php endforeach; ?>
 <?php endif; ?>
</section>
