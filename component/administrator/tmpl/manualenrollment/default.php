<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('behavior.formvalidator'); $i = $this->item;
?>
<section id="jlms-manual-enrollment">
 <h1><?= Text::_('COM_JLMS_MANUAL_TITLE') ?>: <?= $this->escape($i['course']->title) ?></h1>
 <p id="jlms-manual-policy" class="alert alert-info"><?= Text::_('COM_JLMS_MANUAL_POLICY') ?></p>
 <form id="jlms-manual-selection" method="get" action="<?= Route::_('index.php') ?>">
  <?= $this->selectionForm->renderFieldset('selection') ?><input type="hidden" name="option" value="com_jlms"><input type="hidden" name="view" value="manualenrollment"><input type="hidden" name="id" value="<?= (int) $i['course']->id ?>">
  <button type="submit" class="btn btn-secondary"><?= Text::_('COM_JLMS_MANUAL_PREVIEW') ?></button>
 </form>
 <?php if ($i['preview']) : $v = $i['preview']; $p = $v['impact']; ?>
 <section id="jlms-manual-impact"><h2><?= Text::_('COM_JLMS_MANUAL_PREVIEW') ?></h2>
  <p><?= $this->escape($v['learner_name']) ?> (<?= $this->escape($v['username']) ?>)</p>
  <p><?= Text::_('COM_JLMS_ROSTER_VERSION') ?> #<?= (int) $p['version_id'] ?> · <?= Text::_('COM_JLMS_ROSTER_EDITION') ?> #<?= (int) $p['edition_id'] ?> · <?= $this->escape($p['language']) ?></p>
  <?php if (!$p['has_current_access']) : ?><p id="jlms-manual-access-warning" class="alert alert-warning" role="status"><?= Text::_('COM_JLMS_PREASSIGN_WARNING') ?></p><?php endif; ?>
  <p><?= Text::_('COM_JLMS_MANUAL_CAPACITY') ?>: <?= (int) $p['used'] ?> / <?= $p['capacity'] === null ? Text::_('COM_JLMS_MANUAL_UNLIMITED') : (int) $p['capacity'] ?></p>
  <?php if ($p['blocked'] !== null) : ?><p id="jlms-manual-blocked" class="alert alert-warning"><?= Text::_($p['blocked']) ?></p>
   <?php if ($p['existing_id']) : ?><p id="jlms-manual-existing">#<?= (int) $p['existing_id'] ?> · <?= $this->escape($p['existing_status']) ?></p><?php endif; ?>
  <?php else : ?><form id="jlms-manual-form" method="post" class="form-validate" action="<?= Route::_('index.php?option=com_jlms') ?>">
   <?= $this->form->renderFieldset('details') ?><input type="hidden" name="task" value="manualenrollment.apply"><?= HTMLHelper::_('form.token') ?><button type="submit" class="btn btn-primary"><?= Text::_('COM_JLMS_MANUAL_APPLY') ?></button>
  </form><?php endif; ?>
  <?php if ($v['receipt']) : $h = $v['receipt']; ?><section id="jlms-manual-receipt"><h3><?= Text::_('COM_JLMS_MANUAL_RECEIPT') ?> #<?= (int) $h->id ?></h3><p><?= $this->escape($h->reason) ?> · <?= (int) $h->actor_user_id ?> · <?= $this->escape($h->created_at) ?> UTC</p></section><?php endif; ?>
 </section><?php endif; ?>
 <a id="jlms-manual-roster" href="<?= Route::_('index.php?option=com_jlms&view=enrollments&id='.(int) $i['course']->id) ?>"><?= Text::_('COM_JLMS_ENROLLMENTS_TITLE') ?></a>
</section>
