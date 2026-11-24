<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$i = $this->item;
?>
<section id="jlms-admin-certificate">
 <dl class="row"><dt class="col-sm-3"><?=
      Text::_('COM_JLMS_CERTIFICATE_STUDENT') ?></dt><dd class="col-sm-9"><?=
      $this->escape($i['student_name']) ?></dd>
 <dt class="col-sm-3"><?= Text::_('COM_JLMS_CERTIFICATE_COURSE') ?></dt><dd class="col-sm-9"><?= $this
     ->escape($i['course_title']) ?></dd>
 <dt class="col-sm-3"><?= Text::_('COM_JLMS_CERTIFICATE_CODE') ?></dt><dd class="col-sm-9"><?= $this
     ->escape($i['serial']) ?></dd>
 <dt class="col-sm-3"><?=
      Text::_('JSTATUS') ?></dt><dd class="col-sm-9"><?=
      Text::_($i['status'] === 'revoked' ? 'COM_JLMS_CERTIFICATE_STATE_REVOKED' : ($i['status'] === 'ready'
          ? 'COM_JLMS_CERTIFICATE_STATE_READY' : 'COM_JLMS_CERTIFICATE_STATE_PENDING')) ?></dd>
 <dt class="col-sm-3"><?= Text::_('COM_JLMS_CERTIFICATE_ISSUED') ?></dt><dd class="col-sm-9"><?= $this
     ->escape($i['issued_at']) ?></dd>
 <dt class="col-sm-3"><?= Text::_('COM_JLMS_CERTIFICATE_COMPLETED') ?></dt><dd class="col-sm-9"><?= $this
     ->escape($i['completed_at']) ?></dd>
 <dt class="col-sm-3"><?=
      Text::_('COM_JLMS_CERTIFICATE_COURSE_VERSION') ?></dt><dd class="col-sm-9"><?=
      (int) $i['course_version_id'] ?></dd>
 <?php if ($i['revoked_at'] !== '') :
        ?><dt class="col-sm-3"><?=
             Text::_('COM_JLMS_CERTIFICATE_REVOKED_AT') ?></dt><dd class="col-sm-9"><?=
             $this->escape($i['revoked_at']) ?> — <?=
             $this->escape($i['revocation_reason']) ?></dd><?php
 endif; ?></dl>
 <?php if ($i['status'] === 'ready') :
        ?><a class="btn btn-primary" href="<?=
             Route::_('index.php?option=com_jlms&task=certificate.download&id=' . (int) $i['id']) ?>"><?=
             Text::_('COM_JLMS_CERTIFICATE_DOWNLOAD_ADMIN') ?></a><?php
 endif; ?>
 <?php if ($i['status'] === 'ready') : ?>
 <section id="jlms-certificate-regeneration" class="mt-4" aria-labelledby="jlms-cert-regenerate-heading">
  <h2 id="jlms-cert-regenerate-heading" class="h4"><?= Text::_('COM_JLMS_CERTIFICATE_REGENERATE') ?></h2>
  <p><?= Text::_('COM_JLMS_CERTIFICATE_REGENERATE_HELP') ?></p>
  <form method="post" action="<?= Route::_('index.php?option=com_jlms') ?>">
   <input type="hidden" name="task" value="certificate.regenerate">
   <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
   <input type="hidden" name="revision" value="<?= (int) $i['revision'] ?>">
   <label for="jlms-regenerate-reason"><?= Text::_('COM_JLMS_CERTIFICATE_REGENERATE_REASON') ?></label>
   <textarea class="form-control"
    id="jlms-regenerate-reason"
    name="reason"
    minlength="8"
    maxlength="500"
    rows="2" required></textarea>
   <label class="d-block mt-2"><input type="checkbox" name="confirmation" value="1" required>
        <?= Text::_('COM_JLMS_CERTIFICATE_REGENERATE_CONFIRM') ?></label>
        <?= \Joomla\CMS\HTML\HTMLHelper::_('form.token') ?>
   <button type="submit" class="btn btn-warning mt-2"><?= Text::_('COM_JLMS_CERTIFICATE_REGENERATE') ?></button>
  </form>
 </section>
 <?php endif; ?>
 <h2><?= Text::_('COM_JLMS_CERTIFICATE_AUDIT') ?></h2>
 <?php if (!$i['audit']) :
        ?><p><?= Text::_('JGLOBAL_NO_MATCHING_RESULTS') ?></p><?php
 else :
        ?><table class="table">
          <thead>
          <tr>
          <th><?= Text::_('COM_JLMS_CERTIFICATE_AUDIT_ACTION') ?></th>
          <th><?= Text::_('COM_JLMS_CERTIFICATE_AUDIT_ACTOR') ?></th>
          <th><?= Text::_('COM_JLMS_CERTIFICATE_AUDIT_DATE') ?></th>
          </tr>
          </thead><tbody>
     <?php foreach ($i['audit'] as $event) :
            ?><tr><td><?= $this->escape($event->action) ?></td><td><?= (int) $event
                ->actor_user_id ?></td><td><?= $this->escape($event->created_at) ?></td></tr><?php
     endforeach; ?>
 </tbody></table>
 <?php endif; ?>
 <a class="btn btn-secondary"
    href="<?= Route::_('index.php?option=com_jlms&view=certificates') ?>"><?= Text::_('JTOOLBAR_BACK') ?></a>
</section>
