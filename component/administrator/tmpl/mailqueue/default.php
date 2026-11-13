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
$filterQuery = '&filter[state]=' . rawurlencode((string) $filters['state']) . '&filter[template]=' . rawurlencode((string) $filters['template']);
?>
<section id="jlms-mail-queue">
 <h1><?= Text::_('COM_JLMS_MAIL_QUEUE_TITLE') ?></h1>
 <p class="alert alert-info"><?= Text::_('COM_JLMS_MAIL_QUEUE_HELP') ?></p>
 <form method="get" action="<?= Route::_('index.php') ?>" class="row g-2 align-items-end mb-3">
  <input type="hidden" name="option" value="com_jlms">
  <input type="hidden" name="view" value="mailqueue">
  <div class="col-md-3">
   <label class="form-label" for="jlms-mail-queue-state"><?= Text::_('COM_JLMS_MAIL_QUEUE_FILTER_STATE') ?></label>
   <select class="form-select" name="filter[state]" id="jlms-mail-queue-state">
    <option value=""><?= Text::_('COM_JLMS_MAIL_QUEUE_FILTER_ALL') ?></option>
    <?php foreach (['pending','retry','sending','sent','failed','skipped'] as $state) : ?>
     <option value="<?= $state ?>" <?= $filters['state'] === $state ? 'selected' : '' ?>><?= Text::_('COM_JLMS_MAIL_QUEUE_STATE_' . strtoupper($state)) ?></option>
    <?php endforeach; ?>
   </select>
  </div>
  <div class="col-md-5">
   <label class="form-label" for="jlms-mail-queue-template"><?= Text::_('COM_JLMS_MAIL_QUEUE_FILTER_TEMPLATE') ?></label>
   <select class="form-select" name="filter[template]" id="jlms-mail-queue-template">
    <option value=""><?= Text::_('COM_JLMS_MAIL_QUEUE_FILTER_ALL') ?></option>
    <?php foreach ([
        'com_jlms.enrollment.requested','com_jlms.enrollment.confirmed','com_jlms.course.completed',
        'com_jlms.certificate.available','com_jlms.enrollment.suspended','com_jlms.enrollment.reactivated',
        'com_jlms.course.message','com_jlms.course.published','com_jlms.course.consent',
    ] as $template) : ?>
     <option value="<?= $this->escape($template) ?>" <?= $filters['template'] === $template ? 'selected' : '' ?>><?= $this->escape($template) ?></option>
    <?php endforeach; ?>
   </select>
  </div>
  <div class="col-auto"><button class="btn btn-primary" type="submit"><?= Text::_('COM_JLMS_MAIL_QUEUE_FILTER_APPLY') ?></button></div>
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
     <td><?php if ($row->course_id) : ?><?= $this->escape((string) ($row->course_title ?: '#' . $row->course_id)) ?><?php else : ?>—<?php endif; ?></td>
     <td><?= $row->recipient_masked !== '' ? '<code>' . $this->escape($row->recipient_masked) . '</code>' : '—' ?></td>
     <td><?= Text::_('COM_JLMS_MAIL_QUEUE_STATE_' . strtoupper((string) $row->state)) ?>
      <?php if ((int) $row->is_stale_sending === 1) : ?><div class="text-warning"><?= Text::_('COM_JLMS_MAIL_QUEUE_STALE') ?></div><?php endif; ?>
     </td>
     <td><?= (int) $row->attempts ?></td>
     <td><?= $this->escape((string) $row->created_at) ?></td>
     <td>
      <?php if ((int) $row->is_stale_sending === 1) : ?>
       <details>
        <summary><?= Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE') ?></summary>
        <div class="card card-body mt-2">
         <p><?= Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE_WARNING') ?></p>
         <form method="post" action="<?= Route::_('index.php?option=com_jlms&task=mailqueue.reconcile') ?>" class="mb-3">
          <input type="hidden" name="jform[id]" value="<?= (int) $row->id ?>">
          <input type="hidden" name="jform[action]" value="sent">
          <label class="form-label" for="jlms-mail-queue-sent-note-<?= (int) $row->id ?>"><?= Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE_NOTE') ?></label>
          <textarea class="form-control mb-2" id="jlms-mail-queue-sent-note-<?= (int) $row->id ?>" name="jform[note]" minlength="10" maxlength="1000" required></textarea>
          <div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="jlms-mail-queue-sent-confirm-<?= (int) $row->id ?>" name="jform[confirm]" value="1" required><label class="form-check-label" for="jlms-mail-queue-sent-confirm-<?= (int) $row->id ?>"><?= Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE_SENT_CONFIRM') ?></label></div>
          <button class="btn btn-success" type="submit"><?= Text::_('COM_JLMS_MAIL_QUEUE_MARK_SENT') ?></button>
          <?= HTMLHelper::_('form.token') ?>
         </form>
         <?php if ((int) $row->attempts < 5) : ?>
         <form method="post" action="<?= Route::_('index.php?option=com_jlms&task=mailqueue.reconcile') ?>">
          <input type="hidden" name="jform[id]" value="<?= (int) $row->id ?>">
          <input type="hidden" name="jform[action]" value="retry">
          <label class="form-label" for="jlms-mail-queue-retry-note-<?= (int) $row->id ?>"><?= Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE_NOTE') ?></label>
          <textarea class="form-control mb-2" id="jlms-mail-queue-retry-note-<?= (int) $row->id ?>" name="jform[note]" minlength="10" maxlength="1000" required></textarea>
          <div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="jlms-mail-queue-retry-confirm-<?= (int) $row->id ?>" name="jform[confirm]" value="1" required><label class="form-check-label" for="jlms-mail-queue-retry-confirm-<?= (int) $row->id ?>"><?= Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE_RETRY_CONFIRM') ?></label></div>
          <button class="btn btn-warning" type="submit"><?= Text::_('COM_JLMS_MAIL_QUEUE_RETRY') ?></button>
          <?= Joomla\CMS\HTML\HTMLHelper::_('form.token') ?>
         </form>
         <?php endif; ?>
        </div>
       </details>
      <?php elseif ($row->reconciliation_action) : ?>
       <div><?= Text::_('COM_JLMS_MAIL_QUEUE_RECONCILED') ?>: <?= Text::_('COM_JLMS_MAIL_QUEUE_ACTION_' . strtoupper((string) $row->reconciliation_action)) ?></div>
       <small><?= $this->escape((string) $row->reconciliation_at) ?></small>
       <div><?= $this->escape((string) $row->reconciliation_note) ?></div>
      <?php else : ?>—<?php endif; ?>
     </td>
    </tr>
   <?php endforeach; ?>
   </tbody>
  </table>
 </div>
 <?php endif; ?>
 <nav aria-label="<?= $this->escape(Text::_('COM_JLMS_MAIL_QUEUE_PAGINATION')) ?>">
  <ul class="pagination">
   <?php if ($start > 0) : ?><li class="page-item"><a class="page-link" href="<?= Route::_('index.php?option=com_jlms&view=mailqueue&limitstart=' . max(0, $start - $limit) . $filterQuery) ?>"><?= Text::_('JPREV') ?></a></li><?php endif; ?>
   <?php if ($start + $limit < (int) $data['total']) : ?><li class="page-item"><a class="page-link" href="<?= Route::_('index.php?option=com_jlms&view=mailqueue&limitstart=' . ($start + $limit) . $filterQuery) ?>"><?= Text::_('JNEXT') ?></a></li><?php endif; ?>
  </ul>
 </nav>
</section>
