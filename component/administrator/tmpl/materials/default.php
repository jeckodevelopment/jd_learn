<?php

declare(strict_types=1);

\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$item = $this->item; $course = $item['course']; $version = $item['version']; $editable = $version->status === 'draft';
$link = static fn (string $url): string => Route::link('administrator', $url);
$base = 'index.php?option=com_jlms&view=materials&id=' . (int) $course->id . '&version_id=' . (int) $version->id;
$hidden = static function (array $fields): void {
    foreach ($fields as $name => $value) { echo '<input type="hidden" name="jform[' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ']" value="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '">'; }
};
$scope = ['course_id' => $course->id, 'version_id' => $version->id, 'expected_version_revision' => $version->revision];
?>
<section id="jlms-admin-materials">
 <h1><?= $this->escape($course->title) ?> · <?= Text::_('COM_JLMS_MATERIALS') ?></h1>
 <a href="<?= $link('index.php?option=com_jlms&view=curriculum&id=' . (int) $course->id . '&version_id=' . (int) $version->id) ?>"><?= Text::_('COM_JLMS_CURRICULUM') ?></a>
 <p class="alert alert-info"><?= Text::_('COM_JLMS_MATERIALS_NOTICE') ?> <?= Text::_('COM_JLMS_EFFECTIVE_LIMIT') ?>: <?= number_format($item['limit'] / 1048576, 2) ?> MiB.</p>
 <p><?= Text::_($item['scan_required'] ? 'COM_JLMS_SCAN_REQUIRED' : 'COM_JLMS_SCAN_NOT_CONFIGURED') ?></p>
 <?php if ($editable) : ?>
 <p class="small text-muted"><?= Text::_('COM_JLMS_MATERIAL_SCHEDULE_ZONE') ?>: <?= $this->escape($this->scheduleTimezone) ?></p>
 <form id="jlms-material-upload" enctype="multipart/form-data" method="post" action="<?= $link('index.php?option=com_jlms') ?>">
  <?php if ($this->replacement) : ?><h2><?= Text::_('COM_JLMS_REPLACE_MATERIAL') ?></h2><input type="hidden" name="jform[lesson_id]" value="<?= $this->replacementLesson ?>"><?php endif; ?>
  <?= $this->form->renderFieldset('details') ?><input type="hidden" name="task" value="material.upload">
  <label><?= Text::_('COM_JLMS_CALENDAR_PUBLISH_UP') ?><input type="datetime-local" step="1" name="jform[publish_up]" value="<?= $this->escape($this->dateValues['publish_up']) ?>"></label>
  <label><?= Text::_('COM_JLMS_CALENDAR_PUBLISH_DOWN') ?><input type="datetime-local" step="1" name="jform[publish_down]" value="<?= $this->escape($this->dateValues['publish_down']) ?>"></label>
  <?= HTMLHelper::_('form.token') ?><button class="btn btn-primary" type="submit"><?= Text::_($this->replacement ? 'COM_JLMS_REPLACE_MATERIAL' : 'COM_JLMS_UPLOAD_MATERIAL') ?></button>
  <?php if ($this->replacement) : ?><a href="<?= $link($base) ?>"><?= Text::_('JCANCEL') ?></a><?php endif; ?>
 </form>
 <?php else : ?><p class="alert alert-warning"><?= Text::_('COM_JLMS_VERSION_IMMUTABLE') ?></p><?php endif; ?>
 <div id="jlms-admin-material-list">
 <?php foreach ($item['files']['items'] as $file) : ?>
 <section class="jlms-admin-material" data-attachment-id="<?= (int) $file->id ?>">
  <h2><?= $this->escape($file->title) ?></h2>
  <p><?= $this->escape($file->original_name) ?> · <?= $this->escape($file->mime_type) ?> · <?= (int) $file->byte_size ?> B · <?= $this->escape($file->status) ?> · <?= Text::_((int) $file->preview === 1 ? 'COM_JLMS_MATERIAL_PUBLIC_PREVIEW' : 'COM_JLMS_MATERIAL_PRIVATE') ?> · ID <?= (int) $file->id ?> · <?= $file->lesson_id ? Text::_('COM_JLMS_MODULE_LESSON') . ' ' . (int) $file->lesson_id : Text::_('COM_JLMS_COURSE_MATERIALS') ?></p>
  <?php if ($file->status === 'ready') : ?><a href="<?= $link('index.php?option=com_jlms&task=material.download&format=raw&course_id=' . (int) $course->id . '&version_id=' . (int) $version->id . '&id=' . (int) $file->id) ?>"><?= Text::_('COM_JLMS_DOWNLOAD') ?></a><?php endif; ?>
  <?php if ($editable) : ?>
  <a class="btn btn-secondary" href="<?= $link($base . '&page=' . (int) $item['files']['page'] . '&replace_id=' . (int) $file->id) ?>#jlms-material-upload"><?= Text::_('COM_JLMS_REPLACE_MATERIAL') ?></a>
  <form method="post" class="jlms-material-edit" action="<?= $link('index.php?option=com_jlms') ?>">
   <?php $hidden($scope + ['id' => $file->id]); ?><input type="hidden" name="task" value="material.save">
   <label><?= Text::_('JGLOBAL_TITLE') ?><input type="text" name="jform[title]" value="<?= $this->escape($file->title) ?>" maxlength="255" required></label>
   <label><?= Text::_('JGLOBAL_DESCRIPTION') ?><textarea name="jform[description]" maxlength="4096"><?= $this->escape($file->description ?? '') ?></textarea></label>
   <label><?= Text::_('JSTATUS') ?><select name="jform[state]"><option value="1"<?= (int) $file->state === 1 ? ' selected' : '' ?>><?= Text::_('JPUBLISHED') ?></option><option value="0"<?= (int) $file->state === 0 ? ' selected' : '' ?>><?= Text::_('JUNPUBLISHED') ?></option></select></label>
   <label><?= Text::_('COM_JLMS_MATERIAL_PUBLIC_PREVIEW') ?><select name="jform[preview]"><option value="0"<?= (int) $file->preview === 0 ? ' selected' : '' ?>><?= Text::_('JNO') ?></option><option value="1"<?= (int) $file->preview === 1 ? ' selected' : '' ?>><?= Text::_('JYES') ?></option></select></label>
   <label><?= Text::_('JFIELD_ORDERING_LABEL') ?><input type="number" name="jform[ordering]" value="<?= (int) $file->ordering ?>" min="0" max="99999"></label>
   <label><?= Text::_('COM_JLMS_CALENDAR_PUBLISH_UP') ?><input type="datetime-local" step="1" name="jform[publish_up]" value="<?= $this->escape($file->publish_up_editor) ?>"></label>
   <label><?= Text::_('COM_JLMS_CALENDAR_PUBLISH_DOWN') ?><input type="datetime-local" step="1" name="jform[publish_down]" value="<?= $this->escape($file->publish_down_editor) ?>"></label>
   <?= HTMLHelper::_('form.token') ?><button type="submit" class="btn btn-secondary"><?= Text::_('JSAVE') ?></button>
  </form>
  <form method="post" action="<?= $link('index.php?option=com_jlms') ?>"><?php $hidden($scope + ['id' => $file->id]); ?><input type="hidden" name="task" value="material.delete"><?= HTMLHelper::_('form.token') ?><button type="submit" class="btn btn-outline-danger"><?= Text::_('COM_JLMS_UNLINK_MATERIAL') ?></button></form>
  <?php $reuseTargets = array_values(array_filter($item['lessons'], static fn ($lesson): bool => (int) $lesson->id !== (int) $file->lesson_id)); ?>
  <?php if ($file->lesson_id || $reuseTargets) : ?>
  <form method="post" class="jlms-material-reuse" action="<?= $link('index.php?option=com_jlms') ?>">
   <?php $hidden($scope + ['id' => $file->id]); ?><input type="hidden" name="task" value="material.reuse">
   <label><?= Text::_('COM_JLMS_MATERIAL_REUSE_TARGET') ?><select name="jform[target_lesson_id]" required>
    <?php if ($file->lesson_id) : ?><option value="0"><?= Text::_('COM_JLMS_COURSE_MATERIALS') ?></option><?php endif; ?>
    <?php foreach ($reuseTargets as $lesson) : ?><option value="<?= (int) $lesson->id ?>"><?= $this->escape($lesson->title) ?></option><?php endforeach; ?>
   </select></label>
   <?= HTMLHelper::_('form.token') ?><button type="submit" class="btn btn-secondary"><?= Text::_('COM_JLMS_MATERIAL_REUSE') ?></button>
  </form>
  <?php endif; ?>
  <?php endif; ?>
  <form method="post" action="<?= $link('index.php?option=com_jlms') ?>"><?php $hidden($scope + ['id' => $file->id]); ?><input type="hidden" name="task" value="material.scan"><?= HTMLHelper::_('form.token') ?><button type="submit" class="btn btn-secondary"><?= Text::_('COM_JLMS_RESCAN') ?></button></form>
 </section>
 <?php endforeach; ?></div>
 <nav aria-label="<?= Text::_('COM_JLMS_PAGINATION') ?>">
 <?php if ($item['files']['page'] > 0) : ?><a href="<?= $link($base . '&page=' . ($item['files']['page'] - 1)) ?>"><?= Text::_('JPREV') ?></a><?php endif; ?>
 <?php if (($item['files']['page'] + 1) * 20 < $item['files']['total']) : ?><a href="<?= $link($base . '&page=' . ($item['files']['page'] + 1)) ?>"><?= Text::_('JNEXT') ?></a><?php endif; ?>
 </nav>
</section>
