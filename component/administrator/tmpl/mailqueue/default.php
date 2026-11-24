<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$data = $this->data;
$filters = $data['filters'];
$limit = (int) $data['limit'];
$start = (int) $data['start'];
$stateKeys = ['pending' => 'COM_JLMS_MAIL_QUEUE_STATE_PENDING', 'retry'
    => 'COM_JLMS_MAIL_QUEUE_STATE_RETRY', 'sending' => 'COM_JLMS_MAIL_QUEUE_STATE_SENDING', 'sent'
        => 'COM_JLMS_MAIL_QUEUE_STATE_SENT', 'failed' => 'COM_JLMS_MAIL_QUEUE_STATE_FAILED', 'skipped'
            => 'COM_JLMS_MAIL_QUEUE_STATE_SKIPPED'];
$schedulerStateLabels = [
 'missing' => 'COM_JLMS_MAIL_SCHEDULER_STATE_MISSING',
 'disabled' => 'COM_JLMS_MAIL_SCHEDULER_STATE_DISABLED',
 'waiting' => 'COM_JLMS_MAIL_SCHEDULER_STATE_WAITING',
 'failed' => 'COM_JLMS_MAIL_SCHEDULER_STATE_FAILED',
 'operational' => 'COM_JLMS_MAIL_SCHEDULER_STATE_OPERATIONAL',
];
$actionKeys = ['sent' => 'COM_JLMS_MAIL_QUEUE_ACTION_SENT', 'retry' => 'COM_JLMS_MAIL_QUEUE_ACTION_RETRY'];
$filterQuery = '&filter[state]=' . rawurlencode((string) $filters['state']) . '&filter[template]='
    . rawurlencode((string) $filters['template']);
?>
<section id="jlms-mail-queue">
 <p class="alert alert-info"><?= Text::_('COM_JLMS_MAIL_QUEUE_HELP') ?></p>
 <?php if ($this->mailScheduler) : ?>
 <div class="small mb-3" role="status">
  <strong><?= Text::_('COM_JLMS_MAIL_SCHEDULER_STATUS') ?>:</strong>
        <?= $this->escape(Text::_($schedulerStateLabels[$this->mailScheduler['state']]
            ?? 'COM_JLMS_MAIL_SCHEDULER_STATE_MISSING')) ?>
        <?php if ($this->mailScheduler['interval'] !== null) : ?>
   · <?= Text::sprintf('COM_JLMS_MAIL_SCHEDULER_INTERVAL', $this->mailScheduler['interval']) ?>
        <?php endif; ?>
  · <?= Text::_('COM_JLMS_MAIL_SCHEDULER_LAST') ?>:
        <?= $this->escape((string) ($this->mailScheduler['last_execution'] ?: Text::_('JNEVER'))) ?>
        <?php if ($this->mailScheduler['duplicates']) :
            ?> · <?= Text::_('COM_JLMS_MAIL_SCHEDULER_DUPLICATES') ?><?php
        endif; ?>
  <a href="<?= Route::_('index.php?option=com_scheduler&view=tasks') ?>"><?=
       Text::_('COM_JLMS_MAIL_SCHEDULER_CONFIGURE') ?></a>
 </div>
 <?php endif; ?>
 <form method="get" action="<?= Route::_('index.php') ?>" class="row g-2 align-items-end mb-3">
  <input type="hidden" name="option" value="com_jlms">
  <input type="hidden" name="view" value="mailqueue">
  <div class="col-md-3">
   <label class="form-label" for="jlms-mail-queue-state"><?= Text::_('COM_JLMS_MAIL_QUEUE_FILTER_STATE') ?></label>
   <select class="form-select" name="filter[state]" id="jlms-mail-queue-state">
    <option value=""><?= Text::_('COM_JLMS_MAIL_QUEUE_FILTER_ALL') ?></option>
    <?php foreach (['pending','retry','sending','sent','failed','skipped'] as $state) : ?>
     <option value="<?= $state ?>" <?=
          $filters['state'] === $state ? 'selected' : '' ?>><?=
          Text::_($stateKeys[$state]) ?></option>
    <?php endforeach; ?>
   </select>
  </div>
  <div class="col-md-5">
   <label class="form-label"
    for="jlms-mail-queue-template"><?= Text::_('COM_JLMS_MAIL_QUEUE_FILTER_TEMPLATE') ?></label>
   <select class="form-select" name="filter[template]" id="jlms-mail-queue-template">
    <option value=""><?= Text::_('COM_JLMS_MAIL_QUEUE_FILTER_ALL') ?></option>
    <?php foreach (\Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateInstaller::templateIds() as $template) : ?>
     <option value="<?= $this->escape($template) ?>" <?=
          $filters['template'] === $template ? 'selected' : '' ?>><?=
          $this->escape($template) ?></option>
    <?php endforeach; ?>
   </select>
  </div>
  <div class="col-auto"><button class="btn btn-primary"
    type="submit"><?= Text::_('COM_JLMS_MAIL_QUEUE_FILTER_APPLY') ?></button></div>
 </form>

 <p><?= Text::sprintf('COM_JLMS_MAIL_QUEUE_TOTAL', (int) $data['total']) ?></p>
 <?php if (!$data['rows']) : ?>
  <p class="alert alert-secondary"><?= Text::_('COM_JLMS_MAIL_QUEUE_EMPTY') ?></p>
 <?php else : ?>
 <div class="table-responsive">
  <table class="table table-striped" id="jlms-mail-queue-table">
   <thead><tr>
    <th scope="col"><?= Text::_('COM_JLMS_MAIL_QUEUE_ID') ?></th>
    <th scope="col"><?= Text::_('COM_JLMS_MAIL_QUEUE_TEMPLATE') ?></th>
    <th scope="col"><?= Text::_('COM_JLMS_MAIL_QUEUE_COURSE') ?></th>
    <th scope="col"><?= Text::_('COM_JLMS_MAIL_QUEUE_RECIPIENT') ?></th>
    <th scope="col"><?= Text::_('COM_JLMS_MAIL_QUEUE_STATE') ?></th>
    <th scope="col"><?= Text::_('COM_JLMS_MAIL_QUEUE_ATTEMPTS') ?></th>
    <th scope="col"><?= Text::_('COM_JLMS_MAIL_QUEUE_CREATED') ?></th>
    <th scope="col"><?= Text::_('COM_JLMS_MAIL_QUEUE_ACTIONS') ?></th>
   </tr></thead>
   <tbody>
     <?php foreach ($data['rows'] as $row) : ?>
    <tr id="jlms-mail-queue-<?= (int) $row->id ?>">
     <td><?= (int) $row->id ?></td>
     <td><code><?= $this->escape($row->template_id) ?></code></td>
     <td><?php if ($row->course_id) :
            ?><?= $this->escape((string) ($row->course_title ?: '#' . $row->course_id)) ?><?php
         else :
                ?>—<?php
         endif; ?></td>
     <td><?= $row->recipient_masked !== '' ? '<code>' . $this->escape($row->recipient_masked)
         . '</code>' : '—' ?></td>
     <td><?= Text::_($stateKeys[(string) $row->state] ?? 'COM_JLMS_MAIL_QUEUE_STATE_PENDING') ?>
            <?php if ((int) $row->is_stale_sending === 1) :
                ?><div class="text-warning"><?= Text::_('COM_JLMS_MAIL_QUEUE_STALE') ?></div><?php
            endif; ?>
            <?php if ($row->diagnostic_key !== null && in_array((string) $row->state, ['retry', 'failed'], true)) : ?>
       <div class="small text-muted"><?= $this->escape(Text::_($row->diagnostic_key)) ?></div>
                <?php if ($row->diagnostic_missing_template) : ?>
        <div class="small text-warning"><?= Text::_('COM_JLMS_MAIL_QUEUE_TEMPLATE_NOT_FOUND') ?></div>
                <?php elseif ($row->diagnostic_tokens !== []) : ?>
        <div class="small text-warning"><?= Text::_('COM_JLMS_MAIL_QUEUE_UNRESOLVED_TOKENS') ?>: <?= $this
            ->escape(implode(', ', $row->diagnostic_tokens)) ?></div>
                <?php endif; ?>
            <?php endif; ?>
     </td>
     <td><?= (int) $row->attempts ?></td>
     <td><?= $this->escape((string) $row->created_at) ?></td>
     <td>
            <?php if ((int) $row->is_stale_sending === 1) : ?>
       <details>
        <summary><?= Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE') ?></summary>
        <div class="card card-body mt-2">
         <p><?= Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE_WARNING') ?></p>
         <form method="post"
    action="<?= Route::_('index.php?option=com_jlms&task=mailqueue.reconcile') ?>"
    class="mb-3">
          <input type="hidden" name="jform[id]" value="<?= (int) $row->id ?>">
          <input type="hidden" name="jform[action]" value="sent">
          <label class="form-label" for="jlms-mail-queue-sent-note-<?= (int) $row
              ->id ?>"><?= Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE_NOTE') ?></label>
          <textarea class="form-control mb-2" id="jlms-mail-queue-sent-note-<?= (int) $row
              ->id ?>" name="jform[note]" minlength="10" maxlength="1000" required></textarea>
          <div class="form-check mb-2"><input class="form-check-input" type="checkbox"
            id="jlms-mail-queue-sent-confirm-<?=
               (int) $row->id ?>" name="jform[confirm]" value="1" required><label class="form-check-label" for="jlms-mail-queue-sent-confirm-<?=
               (int) $row->id ?>"><?=
               Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE_SENT_CONFIRM') ?></label></div>
          <button class="btn btn-success" type="submit"><?= Text::_('COM_JLMS_MAIL_QUEUE_MARK_SENT') ?></button>
                <?= HTMLHelper::_('form.token') ?>
         </form>
                <?php if ((int) $row->attempts < 5) : ?>
         <form method="post" action="<?= Route::_('index.php?option=com_jlms&task=mailqueue.reconcile') ?>">
          <input type="hidden" name="jform[id]" value="<?= (int) $row->id ?>">
          <input type="hidden" name="jform[action]" value="retry">
          <label class="form-label" for="jlms-mail-queue-retry-note-<?= (int) $row
              ->id ?>"><?= Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE_NOTE') ?></label>
          <textarea class="form-control mb-2" id="jlms-mail-queue-retry-note-<?= (int) $row
              ->id ?>" name="jform[note]" minlength="10" maxlength="1000" required></textarea>
          <div class="form-check mb-2"><input class="form-check-input" type="checkbox"
            id="jlms-mail-queue-retry-confirm-<?=
               (int) $row->id ?>" name="jform[confirm]" value="1" required><label class="form-check-label" for="jlms-mail-queue-retry-confirm-<?=
               (int) $row->id ?>"><?=
               Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE_RETRY_CONFIRM') ?></label></div>
          <button class="btn btn-warning" type="submit"><?= Text::_('COM_JLMS_MAIL_QUEUE_RETRY') ?></button>
                    <?= Joomla\CMS\HTML\HTMLHelper::_('form.token') ?>
         </form>
                <?php endif; ?>
        </div>
       </details>
            <?php elseif (
            (string) $row->state === 'failed' && (int) $row->attempts < 5
            && $row->diagnostic_key === 'COM_JLMS_MAIL_QUEUE_DIAGNOSTIC_TEMPLATE'
            && in_array((string) $row->template_id, ['com_jlms.enrollment.confirmed',
            'com_jlms.course.completed'], true)
) : ?>
       <details>
        <summary><?= Text::_('COM_JLMS_MAIL_QUEUE_RETRY_TEMPLATE_ONLY') ?></summary>
        <div class="card card-body mt-2">
         <p><?= Text::_('COM_JLMS_MAIL_QUEUE_RETRY_TEMPLATE_WARNING') ?></p>
         <form method="post" action="<?= Route::_('index.php?option=com_jlms&task=mailqueue.reconcile') ?>">
          <input type="hidden" name="jform[id]" value="<?= (int) $row->id ?>">
          <input type="hidden" name="jform[action]" value="retry">
          <label class="form-label" for="jlms-mail-queue-template-note-<?= (int) $row
              ->id ?>"><?= Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE_NOTE') ?></label>
          <textarea class="form-control mb-2" id="jlms-mail-queue-template-note-<?= (int) $row
              ->id ?>" name="jform[note]" minlength="10" maxlength="1000" required></textarea>
          <div class="form-check mb-2"><input class="form-check-input" type="checkbox"
            id="jlms-mail-queue-template-confirm-<?=
               (int) $row->id ?>" name="jform[confirm]" value="1" required><label class="form-check-label" for="jlms-mail-queue-template-confirm-<?=
               (int) $row->id ?>"><?=
               Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE_RETRY_CONFIRM') ?></label></div>
          <button class="btn btn-warning" type="submit"><?= Text::_('COM_JLMS_MAIL_QUEUE_RETRY') ?></button>
          <?= HTMLHelper::_('form.token') ?>
         </form>
        </div>
       </details>
            <?php elseif ($row->reconciliation_action) : ?>
       <div><?= Text::_('COM_JLMS_MAIL_QUEUE_RECONCILED') ?>: <?= Text::_($actionKeys[(string) $row
           ->reconciliation_action] ?? 'COM_JLMS_MAIL_QUEUE_ACTION_SENT') ?></div>
       <small><?= $this->escape((string) $row->reconciliation_at) ?></small>
       <div><?= $this->escape((string) $row->reconciliation_note) ?></div>
            <?php else :
                ?>—<?php
            endif; ?>
     </td>
    </tr>
     <?php endforeach; ?>
   </tbody>
  </table>
 </div>
 <?php endif; ?>
 <nav aria-label="<?= $this->escape(Text::_('COM_JLMS_MAIL_QUEUE_PAGINATION')) ?>">
  <ul class="pagination">
   <?php if ($start > 0) :
        ?><li class="page-item"><a class="page-link" href="<?=
             Route::_('index.php?option=com_jlms&view=mailqueue&limitstart=' . max(0, $start - $limit) . $filterQuery) ?>"><?=
             Text::_('JPREV') ?></a></li><?php
   endif; ?>
   <?php if ($start + $limit < (int) $data['total']) :
        ?><li class="page-item"><a class="page-link" href="<?=
             Route::_('index.php?option=com_jlms&view=mailqueue&limitstart=' . ($start + $limit) . $filterQuery) ?>"><?=
             Text::_('JNEXT') ?></a></li><?php
   endif; ?>
  </ul>
 </nav>
 <details id="jlms-consent-audit" class="mt-4">
  <summary class="h5"><?= Text::_('COM_JLMS_PRIVACY_CONSENTS_TITLE') ?></summary>
  <p class="text-muted"><?= Text::_('COM_JLMS_PRIVACY_CONSENTS_HELP') ?></p>
  <form method="get" action="<?= Route::_('index.php') ?>" class="row g-2 mb-3">
   <input type="hidden" name="option" value="com_jlms">
   <input type="hidden" name="view" value="mailqueue">
   <div class="col-auto">
    <label for="jlms-consent-user-id" class="form-label"><?= Text::_('COM_JLMS_PRIVACY_CONSENTS_USER') ?></label>
    <input class="form-control"
    id="jlms-consent-user-id"
    type="number"
    min="0"
    step="1"
    name="consent_user_id"
    value="<?= (int) $this->consentData['user_id'] ?>">
   </div>
   <div class="col-auto align-self-end">
    <button type="submit" class="btn btn-secondary"><?= Text::_('COM_JLMS_MAIL_QUEUE_FILTER_APPLY') ?></button>
   </div>
  </form>
  <p><?= Text::sprintf('COM_JLMS_PRIVACY_CONSENTS_TOTAL', (int) $this->consentData['total']) ?></p>
  <?php if ($this->consentData['rows']) : ?>
   <div class="table-responsive">
    <table class="table table-striped" id="jlms-consent-audit-table">
     <thead><tr>
      <th scope="col"><?= Text::_('COM_JLMS_PRIVACY_CONSENTS_USER') ?></th>
      <th scope="col"><?= Text::_('COM_JLMS_PRIVACY_CONSENTS_STATE') ?></th>
      <th scope="col"><?= Text::_('COM_JLMS_PRIVACY_CONSENTS_ORIGIN') ?></th>
      <th scope="col"><?= Text::_('COM_JLMS_PRIVACY_CONSENTS_VERSION') ?></th>
      <th scope="col"><?= Text::_('COM_JLMS_PRIVACY_CONSENTS_REQUESTED') ?></th>
      <th scope="col"><?= Text::_('COM_JLMS_PRIVACY_CONSENTS_CONFIRMED') ?></th>
      <th scope="col"><?= Text::_('COM_JLMS_PRIVACY_CONSENTS_REVOKED') ?></th>
     </tr></thead>
     <tbody>
        <?php foreach ($this->consentData['rows'] as $consent) : ?>
      <tr>
       <td><?= (int) $consent['user_id'] ?></td>
       <td><?= $this->escape($consent['state']) ?></td>
       <td><?= $this->escape($consent['origin']) ?></td>
       <td><?= $this->escape($consent['policy_version']) ?></td>
       <td><?= $this->escape($consent['requested_at']) ?></td>
       <td><?= $this->escape($consent['confirmed_at'] ?? '—') ?></td>
       <td><?= $this->escape($consent['revoked_at'] ?? '—') ?></td>
      </tr>
        <?php endforeach; ?>
     </tbody>
    </table>
   </div>
  <?php else : ?>
   <p class="alert alert-secondary"><?= Text::_('COM_JLMS_PRIVACY_CONSENTS_EMPTY') ?></p>
  <?php endif; ?>
  <?php
    $consentStart = (int) $this->consentData['start'];
    $consentLimit = (int) $this->consentData['limit'];
    $consentBase = 'index.php?option=com_jlms&view=mailqueue&consent_user_id=' . (int) $this
        ->consentData['user_id'] . '&consentstart=';
    ?>
  <nav aria-label="<?= $this->escape(Text::_('COM_JLMS_PRIVACY_CONSENTS_TITLE')) ?>">
   <ul class="pagination">
    <?php if ($consentStart > 0) :
        ?><li class="page-item"><a class="page-link" href="<?= Route::_($consentBase
            . max(0, $consentStart - $consentLimit)) ?>"><?= Text::_('JPREV') ?></a></li><?php
    endif; ?>
    <?php if ($consentStart + $consentLimit < (int) $this->consentData['total']) :
        ?><li class="page-item"><a class="page-link" href="<?= Route::_($consentBase
            . ($consentStart + $consentLimit)) ?>"><?= Text::_('JNEXT') ?></a></li><?php
    endif; ?>
   </ul>
  </nav>
 </details>
</section>
