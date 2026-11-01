<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
$statusLabel = fn ($status): string => match ($status) {
 'active' => Text::_('COM_JLMS_ROSTER_ACTIVE'), 'suspended' => Text::_('COM_JLMS_ROSTER_SUSPENDED'),
 'completed' => Text::_('COM_JLMS_ROSTER_LEGACY_COMPLETED'), default => $this->escape($status),
};
HTMLHelper::_('behavior.formvalidator'); $i = $this->item; $p = $i['impact'];
?>
<section id="jlms-enrollment-state">
 <h1><?= Text::_('COM_JLMS_ENROLLMENT_STATE_TITLE') ?></h1>
 <h2><?= $this->escape($i['course']->title) ?> · <?= $this->escape($i['learner_name']) ?> · #<?= (int) $i['enrollment_id'] ?></h2>
 <p id="jlms-enrollment-state-policy" class="alert alert-info"><?= Text::_('COM_JLMS_ENROLLMENT_STATE_POLICY') ?></p>
 <section id="jlms-enrollment-state-impact"><h3><?= Text::_('COM_JLMS_ENROLLMENT_STATE_PREVIEW') ?></h3>
  <p><?= Text::_('COM_JLMS_ROSTER_STATUS') ?>: <?= $statusLabel($p['previous_status']) ?> → <?= $statusLabel($p['target_status']) ?></p>
  <p><?= Text::_('COM_JLMS_ENROLLMENT_STATE_OPEN_QUIZZES') ?>: <?= (int) $p['open_attempts'] ?></p>
  <p><?= Text::_('COM_JLMS_ENROLLMENT_STATE_RETAINED') ?>: <?= (int) $p['completed_total'] ?>/<?= (int) $p['required_total'] ?> · <?= $p['completed_at'] === null ? Text::_('COM_JLMS_ENROLLMENT_STATE_NO_COMPLETION') : $this->escape($p['completed_at']).' UTC' ?></p>
  <?php if ($p['blocked'] !== null) : ?><p id="jlms-enrollment-state-blocked" class="alert alert-warning"><?= Text::_($p['blocked']) ?></p>
  <?php else : ?><form id="jlms-enrollment-state-form" method="post" class="form-validate" action="<?= Route::_('index.php?option=com_jlms') ?>">
   <?= $this->form->renderFieldset('details') ?><input type="hidden" name="task" value="enrollmentstate.apply"><?= HTMLHelper::_('form.token') ?>
   <button type="submit" class="btn btn-primary"><?= Text::_($p['target_status'] === 'active' ? 'COM_JLMS_ENROLLMENT_STATE_REACTIVATE' : 'COM_JLMS_ENROLLMENT_STATE_SUSPEND') ?></button>
  </form><?php endif; ?>
 </section>
 <div class="table-responsive"><table id="jlms-enrollment-state-history" class="table"><caption><?= Text::_('COM_JLMS_ENROLLMENT_STATE_HISTORY') ?></caption>
  <thead><tr><th scope="col">#</th><th scope="col"><?= Text::_('COM_JLMS_ROSTER_STATUS') ?></th><th scope="col"><?= Text::_('COM_JLMS_ENROLLMENT_STATE_REASON_LABEL') ?></th><th scope="col"><?= Text::_('JAUTHOR') ?></th><th scope="col"><?= Text::_('JDATE') ?> UTC</th></tr></thead>
  <tbody><?php foreach ($i['history'] as $h) : ?><tr><td><?= (int) $h->revision_no ?></td><td><?= $statusLabel($h->previous_status) ?> → <?= $statusLabel($h->target_status) ?></td><td><?= $this->escape($h->reason) ?></td><td><?= (int) $h->actor_user_id ?></td><td><?= $this->escape($h->created_at) ?></td></tr><?php endforeach; ?></tbody>
 </table></div>
 <a id="jlms-enrollment-state-roster" href="<?= Route::_('index.php?option=com_jlms&view=enrollments&id='.(int) $i['course']->id) ?>"><?= Text::_('COM_JLMS_ENROLLMENTS_TITLE') ?></a>
</section>
