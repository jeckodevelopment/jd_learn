<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$unissuedReasonLabels = [
 'version_disabled' => 'COM_JLMS_CERTIFICATE_DIAG_VERSION_DISABLED',
 'preferences_disabled' => 'COM_JLMS_CERTIFICATE_DIAG_PREFERENCES_DISABLED',
 'template_missing' => 'COM_JLMS_CERTIFICATE_DIAG_TEMPLATE_MISSING',
 'eligible_missing' => 'COM_JLMS_CERTIFICATE_DIAG_ELIGIBLE_MISSING',
 'document_missing' => 'COM_JLMS_CERTIFICATE_DIAG_DOCUMENT_MISSING',
 'invalid_configuration' => 'COM_JLMS_CERTIFICATE_DIAG_INVALID_CONFIGURATION',
];
$this->getDocument()->getWebAssetManager()->useScript('table.columns');
$order = $this->escape((string) $this->state->get('list.ordering', 'd.issued_at'));
$direction = $this->escape((string) $this->state->get('list.direction', 'DESC'));
?>
<form action="<?= Route::_('index.php?option=com_jlms&view=certificates') ?>"
    method="post"
    id="adminForm"
    name="adminForm">
 <div id="j-main-container" class="j-main-container">
  <section id="jlms-admin-certificates" aria-label="<?= Text::_('COM_JLMS_CERTIFICATES') ?>">
   <p><?= Text::_('COM_JLMS_CERTIFICATE_LIST_INTRO') ?></p>
   <?= LayoutHelper::render('joomla.searchtools.default', ['view' => $this]) ?>
   <?php if (!$this->items) : ?>
    <p id="jlms-certificates-empty" class="alert alert-info"><?= Text::_('JGLOBAL_NO_MATCHING_RESULTS') ?></p>
   <?php else : ?>
    <table class="table itemList" id="jlms-certificates-list">
     <caption class="visually-hidden"><?= Text::_('COM_JLMS_CERTIFICATES') ?></caption>
     <thead><tr>
      <th scope="col"><?=
           HTMLHelper::_('searchtools.sort', 'COM_JLMS_CERTIFICATE_STUDENT', 'student_name', $direction, $order) ?></th>
      <th scope="col"><?=
           HTMLHelper::_('searchtools.sort', 'COM_JLMS_CERTIFICATE_COURSE', 'course_title', $direction, $order) ?></th>
      <th scope="col"><?=
           HTMLHelper::_('searchtools.sort', 'COM_JLMS_CERTIFICATE_CODE', 'd.serial', $direction, $order) ?></th>
      <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'JSTATUS', 'certificate_state', $direction, $order) ?></th>
      <th scope="col"><?=
           HTMLHelper::_('searchtools.sort', 'COM_JLMS_CERTIFICATE_ISSUED', 'd.issued_at', $direction, $order) ?></th>
      <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'c.id', $direction, $order) ?></th>
     </tr></thead>
     <tbody>
       <?php foreach ($this->items as $i => $item) : ?>
       <tr class="row<?= $i % 2 ?>">
        <th scope="row"><a href="<?= Route::_('index.php?option=com_jlms&view=certificate&id='
            . (int) $item['id']) ?>"><?= $this->escape($item['student_name']) ?></a></th>
        <td><?= $this->escape($item['course_title']) ?></td>
        <td><?= $this->escape($item['serial']) ?></td>
        <td><?= Text::_($item['status'] === 'revoked' ? 'COM_JLMS_CERTIFICATE_STATE_REVOKED' : ($item['status']
            === 'ready' ? 'COM_JLMS_CERTIFICATE_STATE_READY' : 'COM_JLMS_CERTIFICATE_STATE_PENDING')) ?></td>
        <td><?= $this->escape($item['issued_at']) ?></td>
        <td><?= (int) $item['id'] ?></td>
       </tr>
       <?php endforeach; ?>
     </tbody>
    </table>
   <?php endif; ?>
   <?= $this->pagination->getListFooter() ?>
   <?php if ($this->unissuedCompletions !== []) : ?>
    <section id="jlms-unissued-completions" aria-labelledby="jlms-unissued-title" class="mt-4">
     <h3 id="jlms-unissued-title"><?= Text::_('COM_JLMS_CERTIFICATE_UNISSUED_TITLE') ?></h3>
     <p><?= Text::_('COM_JLMS_CERTIFICATE_UNISSUED_HELP') ?></p>
     <div class="table-responsive">
      <table class="table" id="jlms-unissued-list">
       <thead><tr>
        <th scope="col"><?= Text::_('COM_JLMS_CERTIFICATE_STUDENT') ?></th>
        <th scope="col"><?= Text::_('COM_JLMS_CERTIFICATE_COURSE') ?></th>
        <th scope="col"><?= Text::_('COM_JLMS_CERTIFICATE_COMPLETED_AT') ?></th>
        <th scope="col"><?= Text::_('COM_JLMS_CERTIFICATE_UNISSUED_REASON') ?></th>
        <th scope="col"><?= Text::_('COM_JLMS_CERTIFICATE_HISTORICAL_ACTION') ?></th>
       </tr></thead>
       <tbody>
        <?php foreach ($this->unissuedCompletions as $missing) : ?>
         <tr>
          <th scope="row"><?= $this->escape($missing['student_name']) ?></th>
          <td><?= $this->escape($missing['course_title']) ?></td>
          <td><?= $this->escape($missing['completed_at']) ?></td>
          <td><?= Text::_($unissuedReasonLabels[$missing['reason']]
              ?? 'COM_JLMS_CERTIFICATE_DIAG_INVALID_CONFIGURATION') ?></td>
          <td>
            <?php if (in_array($missing['reason'], ['version_disabled', 'eligible_missing'], true)) : ?>
                <?php $recoveryFormId = 'jlms-recover-' . (int) $missing['enrollment_id']; ?>
            <input type="text"
    class="form-control form-control-sm mb-1"
    form="<?= $recoveryFormId ?>"
    name="reason"
    minlength="8"
    maxlength="500" required
                   aria-label="<?= Text::_('COM_JLMS_CERTIFICATE_HISTORICAL_REASON') ?>"
                       placeholder="<?= Text::_('COM_JLMS_CERTIFICATE_HISTORICAL_REASON') ?>">
            <label><input type="checkbox" form="<?= $recoveryFormId ?>" name="confirmation" value="1" required>
                <?= Text::_($missing['reason'] === 'eligible_missing' ? 'COM_JLMS_CERTIFICATE_MISSING_CONFIRM'
                    : 'COM_JLMS_CERTIFICATE_HISTORICAL_CONFIRM') ?></label>
            <button type="submit"
                class="btn btn-sm btn-outline-primary"
                form="<?=
                     $recoveryFormId ?>"><?=
                     Text::_($missing['reason'] === 'eligible_missing' ? 'COM_JLMS_CERTIFICATE_MISSING_ACTION'
                         : 'COM_JLMS_CERTIFICATE_HISTORICAL_ACTION') ?></button>
            <?php endif; ?>
          </td>
         </tr>
        <?php endforeach; ?>
       </tbody>
      </table>
     </div>
    </section>
   <?php endif; ?>
  </section>
 </div>
 <input type="hidden" name="task" value="">
 <input type="hidden" name="boxchecked" value="0">
 <?= HTMLHelper::_('form.token') ?>
</form>

<?php // The native list form above must not contain nested forms. ?>
<?php foreach ($this->unissuedCompletions as $missing) : ?>
    <?php if (in_array($missing['reason'], ['version_disabled', 'eligible_missing'], true)) : ?>
  <form id="jlms-recover-<?= (int) $missing['enrollment_id'] ?>"
      action="<?= Route::_('index.php?option=com_jlms&task=certificate.' . ($missing['reason']
          === 'eligible_missing' ? 'recoverMissing' : 'recoverHistorical')) ?>"
      method="post">
   <input type="hidden" name="enrollment_id" value="<?= (int) $missing['enrollment_id'] ?>">
        <?= HTMLHelper::_('form.token') ?>
  </form>
    <?php endif; ?>
<?php endforeach; ?>
