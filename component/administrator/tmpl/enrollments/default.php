<?php

declare(strict_types=1);

\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section id="jlms-admin-enrollments">
 <h1><?= Text::_('COM_JLMS_ENROLLMENTS_TITLE') ?>: <?= $this->escape($this->course->title) ?></h1>
 <p id="jlms-roster-policy" class="alert alert-info"><?= Text::_('COM_JLMS_ROSTER_POLICY') ?></p>
 <?php if ($this->pendingRequests) : ?>
 <section id="jlms-pending-requests" aria-labelledby="jlms-pending-title">
  <h2 id="jlms-pending-title"><?= Text::_('COM_JLMS_ENROLLMENT_APPROVAL_PENDING') ?> (<?= count($this->pendingRequests) ?>)</h2>
  <p><?= Text::_('COM_JLMS_ENROLLMENT_APPROVAL_POLICY') ?></p>
  <div class="table-responsive"><table class="table">
   <thead><tr><th scope="col"><?= Text::_('JGLOBAL_USERNAME') ?></th><th scope="col"><?= Text::_('JDATE') ?></th><th scope="col"><?= Text::_('COM_JLMS_ENROLLMENT_APPROVAL_ACTIONS') ?></th></tr></thead>
   <tbody><?php foreach ($this->pendingRequests as $request) : ?>
   <tr id="jlms-pending-request-<?= (int) $request->id ?>">
    <th scope="row"><?= $this->escape($request->name ?? '') ?> (<?= $this->escape($request->username ?? '') ?>) · #<?= (int) $request->id ?></th>
    <td><?= $this->escape($request->created_at) ?> UTC</td>
    <td>
        <?php foreach (['approved', 'rejected'] as $decision) : ?>
     <form class="jlms-approval-decision" method="post" action="<?= Route::_('index.php?option=com_jlms') ?>">
      <input type="hidden" name="task" value="enrollmentapproval.decide">
      <input type="hidden" name="jform[course_id]" value="<?= (int) $this->course->id ?>">
      <input type="hidden" name="jform[request_id]" value="<?= (int) $request->id ?>">
      <input type="hidden" name="jform[revision]" value="<?= (int) $request->revision ?>">
      <input type="hidden" name="jform[decision]" value="<?= $decision ?>">
      <input type="hidden" name="jform[confirmation]" value="1">
      <label><?= Text::_('COM_JLMS_ENROLLMENT_APPROVAL_REASON') ?>
       <input class="form-control" type="text" name="jform[reason]" maxlength="500" autocomplete="off">
      </label>
            <?= \Joomla\CMS\HTML\HTMLHelper::_('form.token') ?>
      <button class="btn <?= $decision === 'approved' ? 'btn-success' : 'btn-outline-danger' ?>" type="submit"><?= Text::_($decision === 'approved' ? 'COM_JLMS_ENROLLMENT_APPROVAL_ACCEPT' : 'COM_JLMS_ENROLLMENT_APPROVAL_REJECT') ?></button>
     </form>
        <?php endforeach; ?>
    </td>
   </tr>
          <?php endforeach; ?></tbody>
  </table></div>
 </section>
 <?php endif; ?>
 <form id="jlms-roster-filters"
    method="get"
    action="<?= Route::_('index.php?option=com_jlms&view=enrollments&id=' . (int) $this->course->id) ?>">
  <div class="row">
   <div class="col-md-6">
    <label for="jlms-roster-version-search"><?= Text::_('COM_JLMS_ROSTER_VERSION_OPTIONS_SEARCH') ?></label>
    <input id="jlms-roster-version-search"
    type="search"
    class="form-control"
    data-roster-option-search="filter_version"
    autocomplete="off">
    <?= $this->rosterForm->renderField('version') ?>
   </div>
   <div class="col-md-6">
    <label for="jlms-roster-user-search"><?= Text::_('COM_JLMS_ROSTER_USER_OPTIONS_SEARCH') ?></label>
    <input id="jlms-roster-user-search"
    type="search"
    class="form-control"
    data-roster-option-search="filter_user"
    autocomplete="off">
    <?= $this->rosterForm->renderField('user') ?>
   </div>
  </div>
  <?= $this->rosterForm->renderField('search') ?>
  <?= $this->rosterForm->renderField('status') ?>
  <?= $this->rosterForm->renderField('progress') ?>
  <input type="hidden"
    name="option"
    value="com_jlms"><input type="hidden"
    name="view"
    value="enrollments"><input type="hidden"
    name="id"
    value="<?= (int) $this->course->id ?>"><input type="hidden"
    name="limitstart"
    value="0">
  <button type="submit" class="btn btn-primary"><?= Text::_('COM_JLMS_ROSTER_FILTER') ?></button>
 </form>
 <p id="jlms-roster-total"><?= Text::_('COM_JLMS_ROSTER_TOTAL') ?>: <?= (int) $this->pagination->total ?></p>
 <div class="table-responsive"><table id="jlms-roster-table" class="table">
  <caption><?= Text::_('COM_JLMS_ROSTER_CAPTION') ?></caption>
  <thead><tr><?php foreach (['JGLOBAL_USERNAME', 'COM_JLMS_ROSTER_VERSION', 'COM_JLMS_ROSTER_STATUS', 'COM_JLMS_ROSTER_PROGRESS', 'COM_JLMS_ROSTER_DATES'] as $key) :
        ?><th scope="col"><?= Text::_($key) ?></th><?php
             endforeach; ?></tr></thead>
  <tbody><?php foreach ($this->items as $item) :
        $state = $item->completed_at !== null ? 'completed' : (($item->started_at !== null || $item
            ->last_activity_at !== null || (int) $item->completed_total > 0) ? 'in_progress' : 'not_started');
        $total = (int) $item->required_total;
        $done = (int) $item->completed_total;
        $percent = $total > 0 && $done >= 0 && $done <= $total ? min($item
            ->completed_at !== null ? 100 : 99, (int) floor(100 * $done / $total)) : null;
        ?><tr id="jlms-roster-enrollment-<?= (int) $item->id ?>" data-enrollment-id="<?= (int) $item->id ?>">
    <th scope="row"><?= $this->escape($item->learner_name ?? Text::_('COM_JLMS_ROSTER_ANONYMIZED')) ?><?php if ($item->username !== null) :
        ?> (<?= $this->escape($item->username) ?>)<?php
                    endif; ?><br>#<?= (int) $item->id ?> · <a href="<?= Route::_('index.php?option=com_jlms&view=enrollmentstate&id=' . (int) $this->course->id . '&enrollment_id=' . (int) $item->id) ?>"><?= Text::_('COM_JLMS_ENROLLMENT_STATE_TITLE') ?></a><?php if (in_array($item->status, ['active', 'suspended'], true) && $item->completed_at === null) :
    ?> · <a href="<?= Route::_('index.php?option=com_jlms&view=enrollmentstate&id=' . (int)$this->course->id . '&enrollment_id=' . (int)$item->id . '&target_status=cancelled') ?>"><?= Text::_('COM_JLMS_ENROLLMENT_STATE_CANCEL') ?></a><?php
                    endif; ?><?php if (in_array($item->status, ['active','completed','suspended'], true) && (int)$item->course_version_id !== (int)$item->current_version_id) :
    ?> · <a href="<?= Route::_('index.php?option=com_jlms&view=enrollmentmigration&id=' . (int)$this->course->id . '&enrollment_id=' . (int)$item->id) ?>"><?= Text::_('COM_JLMS_MIGRATION_TITLE') ?></a><?php
                    endif; ?><?php if ($item->account_blocked) :
    ?> · <?= Text::_('COM_JLMS_ROSTER_ACCOUNT_BLOCKED') ?><?php
                    endif; ?></th>
    <td><?= $this->escape($item->title_snapshot ?? '') ?><br><?= Text::_('COM_JLMS_ROSTER_VERSION') ?> #<?= (int) $item->course_version_id ?> · v<?= (int) $item->version_no ?> · <?= Text::_('COM_JLMS_ROSTER_EDITION') ?> #<?= (int) $item->edition_id ?></td>
    <td><?= isset(['requested' => 1,'active' => 1,'completed' => 1,'suspended' => 1,'migrated' => 1,'expired' => 1,'cancelled' => 1,'rejected' => 1][$item->status]) ? Text::_(implode('_', ['COM', 'JLMS', 'ROSTER', $item->status === 'completed' ? 'LEGACY_COMPLETED' : strtoupper($item->status)])) : $this->escape($item->status) ?></td>
    <td><?= Text::_(implode('_', ['COM', 'JLMS', 'ROSTER', strtoupper($state)])) ?><?php if ($percent !== null) :
        ?> · <?= $percent ?>%<?php
        endif; ?></td>
    <td><?php foreach (['starts_at' => 'COM_JLMS_ROSTER_START', 'ends_at' => 'COM_JLMS_ROSTER_END', 'started_at' => 'COM_JLMS_ROSTER_FIRST', 'last_activity_at' => 'COM_JLMS_ROSTER_LAST', 'completed_at' => 'COM_JLMS_ROSTER_FINISHED'] as $field => $label) : ?>
     <div><?= Text::_($label) ?>: <?= $item->$field === null ? '—' : $this->escape($item->$field) . ' UTC' ?></div>
        <?php endforeach; ?></td>
   </tr>
         <?php endforeach; ?></tbody>
 </table></div>
 <?php if (!$this->items) :
        ?><p id="jlms-roster-empty"><?= Text::_('COM_JLMS_ROSTER_EMPTY') ?></p><?php
 endif; ?>
 <nav id="jlms-roster-pagination"
    aria-label="<?= Text::_('COM_JLMS_ROSTER_PAGINATION') ?>"><?= $this->pagination->getPagesLinks() ?></nav>
</section>
