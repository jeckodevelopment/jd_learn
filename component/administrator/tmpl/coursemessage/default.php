<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$preview=$this->item['preview']??null;
$subject=$preview['subject']??'';
$body=$preview['body']??'';
$selected=$preview['enrollment_ids']??array_map(static fn($row)=>(int)$row->enrollment_id,$this->item['recipients']);
$mutation=bin2hex(random_bytes(16));
$mutation=substr($mutation,0,8).'-'.substr($mutation,8,4).'-4'.substr($mutation,13,3).'-a'.substr($mutation,17,3).'-'.substr($mutation,20);
?>
<section id="jlms-course-communication">
 <h1><?= Text::_('COM_JLMS_COMMUNICATION_TITLE') ?>: <?= $this->escape($this->item['course']->title) ?></h1>
 <p class="alert alert-info"><?= Text::_('COM_JLMS_COMMUNICATION_POLICY') ?></p>
 <form class="mb-3" method="post" action="<?= Route::_('index.php?option=com_jlms&task=coursemessage.preview') ?>">
  <input type="hidden" name="jform[course_id]" value="<?= (int)$this->item['course']->id ?>">
  <label class="form-label" for="jlms-message-subject"><?= Text::_('COM_JLMS_COMMUNICATION_SUBJECT') ?></label>
  <input id="jlms-message-subject" name="jform[subject]" type="text" maxlength="200" required class="form-control" value="<?= $this->escape($subject) ?>">
  <label class="form-label mt-3" for="jlms-message-body"><?= Text::_('COM_JLMS_COMMUNICATION_BODY') ?></label>
  <textarea id="jlms-message-body" name="jform[body]" maxlength="12000" required rows="8" class="form-control"><?= $this->escape($body) ?></textarea>
  <fieldset class="mt-3 mb-3"><legend><?= Text::_('COM_JLMS_COMMUNICATION_RECIPIENTS') ?></legend>
  <?php if (!$this->item['recipients']) : ?><p><?= Text::_('COM_JLMS_COMMUNICATION_NO_RECIPIENTS') ?></p><?php endif; ?>
  <?php foreach ($this->item['recipients'] as $person) : $id=(int)$person->enrollment_id; ?>
   <label class="form-check"><input type="checkbox" name="jform[enrollment_ids][]" value="<?= $id ?>" <?= in_array($id,$selected,true)?'checked':'' ?>> <?= $this->escape($person->name) ?> (<?= $this->escape($person->username) ?>) · #<?= $id ?></label>
  <?php endforeach; ?>
  </fieldset>
  <?= HTMLHelper::_('form.token') ?>
  <button class="btn btn-primary" type="submit"><?= Text::_('COM_JLMS_COMMUNICATION_PREVIEW') ?></button>
 </form>
 <?php if ($preview) : ?>
  <section id="jlms-message-preview" class="card mt-4 mb-3"><div class="card-body">
   <h2><?= Text::_('COM_JLMS_COMMUNICATION_PREVIEW') ?></h2>
   <p><strong><?= $this->escape($preview['subject']) ?></strong></p><p><?= nl2br($this->escape($preview['body'])) ?></p>
   <p><?= Text::sprintf('COM_JLMS_COMMUNICATION_PREVIEW_COUNT',$preview['count']) ?></p>
   <form class="mb-3" method="post" action="<?= Route::_('index.php?option=com_jlms&task=coursemessage.test') ?>">
    <input type="hidden" name="jform[course_id]" value="<?= (int)$this->item['course']->id ?>"><input type="hidden" name="jform[subject]" value="<?= $this->escape($preview['subject']) ?>"><textarea hidden name="jform[body]"><?= $this->escape($preview['body']) ?></textarea><?= HTMLHelper::_('form.token') ?>
    <button class="btn btn-secondary" type="submit"><?= Text::_('COM_JLMS_COMMUNICATION_SEND_TEST') ?></button>
   </form>
   <form class="mt-3" method="post" action="<?= Route::_('index.php?option=com_jlms&task=coursemessage.queue') ?>">
    <input type="hidden" name="jform[course_id]" value="<?= (int)$this->item['course']->id ?>"><input type="hidden" name="jform[subject]" value="<?= $this->escape($preview['subject']) ?>"><textarea hidden name="jform[body]"><?= $this->escape($preview['body']) ?></textarea>
    <?php foreach ($preview['enrollment_ids'] as $id) : ?><input type="hidden" name="jform[enrollment_ids][]" value="<?= (int)$id ?>"><?php endforeach; ?>
    <input type="hidden" name="jform[impact_hash]" value="<?= $this->escape($preview['hash']) ?>"><input type="hidden" name="jform[mutation_id]" value="<?= $this->escape($mutation) ?>">
    <div class="form-check mb-3"><input class="form-check-input" id="jlms-communication-confirm" type="checkbox" name="jform[confirmation]" value="1" required><label class="form-check-label" for="jlms-communication-confirm"><?= Text::_('COM_JLMS_COMMUNICATION_CONFIRM') ?></label></div>
    <?= HTMLHelper::_('form.token') ?>
    <button class="btn btn-primary" type="submit"><?= Text::_('COM_JLMS_COMMUNICATION_QUEUE') ?></button>
   </form>
  </div></section>
 <?php endif; ?>
 <a href="<?= Route::_('index.php?option=com_jlms&view=enrollments&id='.(int)$this->item['course']->id) ?>"><?= Text::_('COM_JLMS_ENROLLMENTS_TITLE') ?></a>
</section>
