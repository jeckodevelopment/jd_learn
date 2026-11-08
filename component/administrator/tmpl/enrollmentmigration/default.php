<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
$i=$this->item; $p=$i['impact']; $target=[];
foreach ($p['target_lessons'] as $lesson) { $target[(int)$lesson['id']]=$lesson; }
?>
<section id="jlms-enrollment-migration">
 <h1><?= Text::_('COM_JLMS_MIGRATION_TITLE') ?></h1>
 <p id="jlms-migration-policy" class="alert alert-info"><?= Text::_('COM_JLMS_MIGRATION_POLICY') ?></p>
 <h2><?= $this->escape($p['learner_name']) ?> · #<?= (int)$p['source_enrollment_id'] ?></h2>
 <p><?= Text::sprintf('COM_JLMS_MIGRATION_VERSIONS',(int)$p['source_version_no'],(int)$p['target_version_no']) ?></p>
 <form id="jlms-migration-map" method="get" action="<?= Route::_('index.php') ?>">
  <input type="hidden" name="option" value="com_jlms"><input type="hidden" name="view" value="enrollmentmigration">
  <input type="hidden" name="id" value="<?= (int)$i['course_id'] ?>"><input type="hidden" name="enrollment_id" value="<?= (int)$p['source_enrollment_id'] ?>">
  <fieldset><legend><?= Text::_('COM_JLMS_MIGRATION_MAP_TITLE') ?></legend>
   <?php if (!$p['completed_source_lessons']) : ?><p><?= Text::_('COM_JLMS_MIGRATION_NO_COMPLETIONS') ?></p><?php endif; ?>
   <?php foreach ($p['completed_source_lessons'] as $source) : $sid=(int)$source['id']; $selected=(int)($p['mapping'][$sid]??0); ?>
    <label for="jlms-migration-map-<?= $sid ?>"><?= $this->escape($source['title']) ?></label>
    <select id="jlms-migration-map-<?= $sid ?>" name="map[<?= $sid ?>]">
     <option value="0"><?= Text::_('COM_JLMS_MIGRATION_SKIP') ?></option>
     <?php foreach ($p['target_lessons'] as $dest) : ?><option value="<?= (int)$dest['id'] ?>"<?= $selected===(int)$dest['id']?' selected':'' ?>><?= $this->escape($dest['title']) ?><?= $dest['required']?' · '.Text::_('COM_JLMS_MIGRATION_REQUIRED'):'' ?></option><?php endforeach; ?>
    </select>
   <?php endforeach; ?>
  </fieldset>
  <button type="submit" class="btn btn-secondary"><?= Text::_('COM_JLMS_MIGRATION_PREVIEW') ?></button>
 </form>
 <section id="jlms-migration-impact"><h2><?= Text::_('COM_JLMS_MIGRATION_IMPACT') ?></h2>
  <p><?= Text::sprintf('COM_JLMS_MIGRATION_TRANSFER_COUNT',count($p['transfers'])) ?></p>
  <?php if ($p['transfers']) : ?><ul><?php foreach ($p['transfers'] as $row) : ?><li><?= $this->escape($row['source_title']) ?> → <?= $this->escape($row['target_title']) ?> (<?= Text::_('COM_JLMS_MIGRATION_COMPLETED_ONLY') ?>)</li><?php endforeach; ?></ul><?php endif; ?>
  <?php if ($p['unmapped']) : ?><p><?= Text::_('COM_JLMS_MIGRATION_UNMAPPED') ?>: <?php foreach ($p['unmapped'] as $index=>$row) : ?><?= $index?', ':'' ?><?= $this->escape($row['title']) ?><?php endforeach; ?></p><?php endif; ?>
  <p><?= Text::_('COM_JLMS_MIGRATION_NOT_TRANSFERRED') ?></p>
  <?php if ($p['blocked']!==null) : ?><p id="jlms-migration-blocked" class="alert alert-warning"><?= Text::_($p['blocked']) ?></p>
  <?php else : ?>
   <form id="jlms-migration-confirm" method="post" action="<?= Route::_('index.php?option=com_jlms') ?>">
    <?= $this->form->renderFieldset('details') ?><input type="hidden" name="task" value="enrollmentmigration.apply">
    <?php foreach ($p['mapping'] as $from=>$to) : ?><input type="hidden" name="jform[mapping][<?= (int)$from ?>]" value="<?= (int)$to ?>"><?php endforeach; ?>
    <?= HTMLHelper::_('form.token') ?><button type="submit" class="btn btn-primary"><?= Text::_('COM_JLMS_MIGRATION_CONFIRM_BUTTON') ?></button>
   </form>
  <?php endif; ?>
 </section>
 <a href="<?= Route::_('index.php?option=com_jlms&view=enrollments&id='.(int)$i['course_id']) ?>"><?= Text::_('COM_JLMS_ENROLLMENTS_TITLE') ?></a>
</section>
