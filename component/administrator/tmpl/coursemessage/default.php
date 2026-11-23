<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$preview = $this->item['preview'] ?? null;
$subject = $preview['subject'] ?? '';
$body = $preview['body'] ?? '';
$template = $this->item['template'];
$selected = $preview['enrollment_ids'] ?? array_map(static fn($row)=>(int)$row->enrollment_id, $this->item['recipients']);
// The idempotency key belongs to the saved preview, never the GET request.
$mutation = $preview['mutation_id'] ?? '';
?>
<section id="jlms-course-communication">
 <h1><?= Text::_('COM_JLMS_COMMUNICATION_TITLE') ?>: <?= $this->escape($this->item['course']->title) ?></h1>
 <p class="alert alert-info"><?= Text::_('COM_JLMS_COMMUNICATION_POLICY') ?></p>
 <form class="mb-3" method="post" action="<?= Route::_('index.php?option=com_jlms&task=coursemessage.preview') ?>">
  <input type="hidden" name="jform[course_id]" value="<?= (int)$this->item['course']->id ?>">
  <div class="card mt-3 mb-3"><div class="card-body">
   <h2 class="h5 mb-2"><?= Text::_('COM_JLMS_COMMUNICATION_TEMPLATE_EDIT') ?></h2>
    <p class="text-muted small"><?= Text::_('COM_JLMS_COMMUNICATION_TEMPLATE_LOCAL') ?> <code>com_jlms.course.message</code></p>
    <label class="form-label" for="jlms-template-subject"><?= Text::_('COM_JLMS_COMMUNICATION_TEMPLATE_SUBJECT') ?></label>
    <input id="jlms-template-subject" name="jform[template][subject]" type="text" maxlength="300" required class="form-control mb-3" value="<?= $this->escape($template['subject']) ?>">
    <label class="form-label" for="jlms-template-htmlbody"><?= Text::_('COM_JLMS_COMMUNICATION_TEMPLATE_HTML') ?></label>
    <?php
    $editorName = (string) Factory::getApplication()->getIdentity()->getParam('editor', Factory::getApplication()->get('editor'));
    echo Editor::getInstance($editorName)->display('jform[template][htmlbody]', (string) $template['htmlbody'], '100%', '260', '60', '10', false, 'jlms-template-htmlbody');
    ?>
    <p class="small text-muted mt-2"><?= Text::_('COM_JLMS_COMMUNICATION_TEMPLATE_TAGS') ?> <code><?= $this->escape(implode(', ', \Jlms\Component\Jlms\Administrator\Application\CourseMessageTemplate::tags())) ?></code></p>
   </div>
  </div>
  <fieldset class="mt-3 mb-3"><legend><?= Text::_('COM_JLMS_COMMUNICATION_RECIPIENTS') ?></legend>
  <?php if (!$this->item['recipients']) : ?><p><?= Text::_('COM_JLMS_COMMUNICATION_NO_RECIPIENTS') ?></p><?php endif; ?>
  <?php foreach ($this->item['recipients'] as $person) : $id = (int)$person->enrollment_id; ?>
   <label class="form-check"><input type="checkbox" name="jform[enrollment_ids][]" value="<?= $id ?>" <?= in_array($id, $selected, true) ? 'checked' : '' ?>> <?= $this->escape($person->name) ?> (<?= $this->escape($person->username) ?>) · #<?= $id ?></label>
  <?php endforeach; ?>
  </fieldset>
  <?= HTMLHelper::_('form.token') ?>
  <button class="btn btn-primary" type="submit"><?= Text::_('COM_JLMS_COMMUNICATION_PREVIEW') ?></button>
 </form>
 <?php if ($preview) : ?>
  <section id="jlms-message-preview" class="card mt-4 mb-3"><div class="card-body">
   <h2><?= Text::_('COM_JLMS_COMMUNICATION_PREVIEW') ?></h2>
   <p><strong><?= $this->escape($preview['rendered']['subject']) ?></strong></p>
   <?php if ($preview['rendered']['htmlbody'] !== '') : ?>
    <iframe sandbox="" title="<?= $this->escape(Text::_('COM_JLMS_COMMUNICATION_RENDERED_HTML')) ?>" loading="lazy" class="w-100 border rounded" style="height:260px" srcdoc="<?= $this->escape($preview['rendered']['htmlbody']) ?>"></iframe>
   <?php else : ?>
    <p><?= nl2br($this->escape($preview['rendered']['body'])) ?></p>
   <?php endif; ?>
   <p class="small text-muted"><?= Text::_('COM_JLMS_COMMUNICATION_PREVIEW_SAMPLE') ?></p>
   <p><?= Text::sprintf('COM_JLMS_COMMUNICATION_PREVIEW_COUNT', $preview['count']) ?></p>
   <form class="mb-3" method="post" action="<?= Route::_('index.php?option=com_jlms&task=coursemessage.test') ?>">
    <input type="hidden" name="jform[course_id]" value="<?= (int)$this->item['course']->id ?>"><input type="hidden" name="jform[mutation_id]" value="<?= $this->escape($mutation) ?>"><?= HTMLHelper::_('form.token') ?>
    <?php foreach (['subject','htmlbody'] as $field) : ?><textarea hidden name="jform[template][<?= $field ?>]"><?= $this->escape($preview['template'][$field]) ?></textarea><?php endforeach; ?>
    <button class="btn btn-secondary" type="submit"><?= Text::_('COM_JLMS_COMMUNICATION_SEND_TEST') ?></button>
   </form>
   <form class="mt-3" method="post" action="<?= Route::_('index.php?option=com_jlms&task=coursemessage.queue') ?>">
    <input type="hidden" name="jform[course_id]" value="<?= (int)$this->item['course']->id ?>">
    <?php foreach ($preview['enrollment_ids'] as $id) : ?><input type="hidden" name="jform[enrollment_ids][]" value="<?= (int)$id ?>"><?php endforeach; ?>
    <?php foreach (['subject','htmlbody'] as $field) : ?><textarea hidden name="jform[template][<?= $field ?>]"><?= $this->escape($preview['template'][$field]) ?></textarea><?php endforeach; ?>
    <input type="hidden" name="jform[impact_hash]" value="<?= $this->escape($preview['hash']) ?>"><input type="hidden" name="jform[mutation_id]" value="<?= $this->escape($mutation) ?>">
    <div class="form-check mb-3"><input class="form-check-input" id="jlms-communication-confirm" type="checkbox" name="jform[confirmation]" value="1" required><label class="form-check-label" for="jlms-communication-confirm"><?= Text::_('COM_JLMS_COMMUNICATION_CONFIRM') ?></label></div>
    <?= HTMLHelper::_('form.token') ?>
    <button class="btn btn-primary" type="submit"><?= Text::_('COM_JLMS_COMMUNICATION_QUEUE') ?></button>
   </form>
  </div></section>
 <?php endif; ?>
</section>
