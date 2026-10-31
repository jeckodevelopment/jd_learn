<?php

declare(strict_types=1);


\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('behavior.formvalidator');
$course = $this->outline['course'];
$version = $this->outline['version'];
$base = 'index.php?option=com_jlms&view=curriculum&id=' . (int) $course->id;
$link = static fn (string $url): string => Route::link('administrator', $url);
$scope = $version ? ['course_id' => $course->id, 'version_id' => $version->id, 'expected_version_revision' => $version->revision] : [];
$hidden = static function (array $fields): void {
    foreach ($fields as $name => $value) {
        echo '<input type="hidden" name="jform[' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ']" value="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '">';
    }
};
?>
<section id="jlms-admin-curriculum">
 <h1><?= $this->escape($course->title) ?></h1>
 <p><a href="<?= $link('index.php?option=com_jlms&view=courses') ?>"><?= Text::_('COM_JLMS_BACK_TO_COURSES') ?></a></p>
 <p class="alert alert-info"><?= Text::_('COM_JLMS_AUTHORING_NOTICE') ?></p>
 <?php if (!$version) : ?>
  <form method="post" id="jlms-version-form" action="<?= $link('index.php?option=com_jlms') ?>">
   <?php $hidden(['course_id' => $course->id, 'revision' => $course->revision]); ?>
   <input type="hidden" name="task" value="curriculum.createVersion">
   <?= HTMLHelper::_('form.token') ?><button class="btn btn-primary" type="submit"><?= Text::_('COM_JLMS_CREATE_DRAFT_VERSION') ?></button>
  </form>
 <?php else : ?>
 <p><?= Text::_('COM_JLMS_VERSION') ?> <?= (int) $version->version_no ?> · <?= Text::_($version->status === 'draft' ? 'COM_JLMS_DRAFT' : ($version->status === 'published' ? 'JPUBLISHED' : 'JARCHIVED')) ?> · <?= Text::_('COM_JLMS_REVISION') ?> <?= (int) $version->revision ?></p>
 <nav aria-label="<?= Text::_('COM_JLMS_VERSIONS') ?>">
 <?php foreach ($this->outline['versions'] as $entry) : ?>
  <a href="<?= $link($base . '&version_id=' . (int) $entry->id) ?>">v<?= (int) $entry->version_no ?></a>
 <?php endforeach; ?></nav>
 <?php if ($this->transitions) : ?>
 <form method="post" id="jlms-publication-form" action="<?= $link('index.php?option=com_jlms') ?>">
  <?php $hidden($scope + ['revision' => $course->revision]); ?>
  <input type="hidden" name="task" value="curriculum.transition">
  <label for="jlms-transition"><?= Text::_('COM_JLMS_WORKFLOW_TRANSITION') ?></label>
  <select id="jlms-transition" name="jform[transition_id]" class="form-select">
  <?php foreach ($this->transitions as $transition) : ?><option value="<?= (int) $transition->id ?>"><?= $this->escape(Text::_($transition->title)) ?></option><?php endforeach; ?>
  </select>
  <?= HTMLHelper::_('form.token') ?><button type="submit" class="btn btn-primary"><?= Text::_('COM_JLMS_EXECUTE_TRANSITION') ?></button>
 </form><?php endif; ?>
 <?php if (\Joomla\CMS\Factory::getApplication()->getIdentity()->authorise('jlms.materials.manage', 'com_jlms.course.' . (int) $course->id)) : ?>
 <p><a id="jlms-manage-materials" class="btn btn-secondary" href="<?= $link('index.php?option=com_jlms&view=materials&id=' . (int) $course->id . '&version_id=' . (int) $version->id) ?>"><?= Text::_('COM_JLMS_MATERIALS') ?></a></p>
 <?php endif; ?>
 <div id="jlms-admin-course-outline">
 <?php foreach ($this->outline['modules'] as $module) : ?>
  <section class="jlms-admin-module" data-module-id="<?= (int) $module->id ?>"><h2><?= $this->escape($module->title) ?></h2>
  <?php if ($this->editable) : ?>
   <a href="<?= $link($base . '&version_id=' . (int) $version->id . '&edit_module=' . (int) $module->id) ?>#jlms-module-form"><?= Text::_('JGLOBAL_EDIT') ?></a>
   <?php foreach (['up', 'down'] as $direction) : ?>
   <form method="post" class="d-inline" action="<?= $link('index.php?option=com_jlms') ?>">
    <?php $hidden($scope + ['id' => $module->id, 'direction' => $direction]); ?><input type="hidden" name="task" value="module.reorder">
    <?= HTMLHelper::_('form.token') ?><button type="submit" class="btn btn-sm btn-secondary"><?= Text::_($direction === 'up' ? 'COM_JLMS_MOVE_UP' : 'COM_JLMS_MOVE_DOWN') ?></button>
   </form><?php endforeach; ?>
   <?php if ($this->canDelete) : ?>
   <form method="post" class="d-inline" action="<?= $link('index.php?option=com_jlms') ?>">
    <?php $hidden($scope + ['id' => $module->id, 'revision' => $module->revision, 'confirmation' => '1']); ?><input type="hidden" name="task" value="module.delete">
    <?= HTMLHelper::_('form.token') ?><button type="submit" class="btn btn-sm btn-outline-danger"><?= Text::_('COM_JLMS_DELETE_EMPTY_MODULE') ?></button>
   </form><?php endif; ?>
  <?php endif; ?>
  <ol><?php foreach ($this->lessonsByModule[(int) $module->id] ?? [] as $lesson) : ?>
   <li><strong><?= $this->escape($lesson->title) ?></strong> · <?= Text::_($lesson->kind === 'text' ? 'COM_JLMS_LESSON_TEXT' : ($lesson->kind === 'video' ? 'COM_JLMS_LESSON_VIDEO' : $lesson->kind)) ?> · <?= Text::_($lesson->required ? 'COM_JLMS_REQUIRED' : 'COM_JLMS_OPTIONAL') ?>
   <?php if ($this->editable) : ?>
    <a href="<?= $link($base . '&version_id=' . (int) $version->id . '&edit_lesson=' . (int) $lesson->id) ?>#jlms-lesson-form"><?= Text::_('JGLOBAL_EDIT') ?></a>
    <?php foreach (['up','down'] as $direction) : ?>
    <form method="post" class="d-inline" action="<?= $link('index.php?option=com_jlms') ?>">
     <?php $hidden($scope + ['id' => $lesson->id, 'direction' => $direction]); ?><input type="hidden" name="task" value="lesson.reorder">
     <?= HTMLHelper::_('form.token') ?><button type="submit" class="btn btn-sm btn-secondary"><?= Text::_($direction === 'up' ? 'COM_JLMS_MOVE_UP' : 'COM_JLMS_MOVE_DOWN') ?></button>
    </form><?php endforeach; ?>
    <?php if ($this->canDelete) : ?>
    <form method="post" class="d-inline" action="<?= $link('index.php?option=com_jlms') ?>">
     <?php $hidden($scope + ['id' => $lesson->id, 'revision' => $lesson->revision]); ?><input type="hidden" name="task" value="lesson.delete">
     <label><input type="checkbox" name="jform[confirmation]" value="1" required> <?= Text::_('COM_JLMS_CONFIRM_DELETE_LESSON') ?></label>
     <?= HTMLHelper::_('form.token') ?><button type="submit" class="btn btn-sm btn-outline-danger"><?= Text::_('JACTION_DELETE') ?></button>
    </form><?php endif; ?>
   <?php endif; ?>
   <?php if ($lesson->provider_key === 'html5' && \Joomla\CMS\Factory::getApplication()->getIdentity()->authorise('jlms.materials.manage', 'com_jlms.course.' . (int) $course->id)) : ?>
    <section id="jlms-video-file-<?= (int) $lesson->id ?>" class="jlms-video-file">
     <p><?= Text::_('COM_JLMS_VIDEO_FILE_NOTICE') ?> <?= $lesson->media_asset_id ? Text::_('COM_JLMS_VIDEO_FILE_ATTACHED') : Text::_('COM_JLMS_VIDEO_FILE_MISSING') ?></p>
     <?php if ($this->editable) : ?>
     <form id="jlms-video-upload-<?= (int) $lesson->id ?>" method="post" enctype="multipart/form-data" action="<?= $link('index.php?option=com_jlms') ?>">
      <?php $hidden($scope + ['lesson_id' => $lesson->id]); ?><input type="hidden" name="task" value="videofile.upload">
      <label for="jlms-video-input-<?= (int) $lesson->id ?>"><?= Text::_('COM_JLMS_VIDEO_FILE_UPLOAD') ?></label>
      <input id="jlms-video-input-<?= (int) $lesson->id ?>" name="jform[upload]" type="file" accept="video/mp4,.mp4" required>
      <?= HTMLHelper::_('form.token') ?><button class="btn btn-secondary" type="submit"><?= Text::_('JUPLOAD') ?></button>
     </form><?php endif; ?>
     <?php if ($lesson->media_asset_id) : ?>
     <form method="post" action="<?= $link('index.php?option=com_jlms') ?>">
      <?php $hidden($scope + ['lesson_id' => $lesson->id]); ?><input type="hidden" name="task" value="videofile.scan">
      <?= HTMLHelper::_('form.token') ?><button class="btn btn-secondary" type="submit"><?= Text::_('COM_JLMS_VIDEO_FILE_SCAN') ?></button>
     </form><?php endif; ?>
    </section>
   <?php endif; ?></li>
  <?php endforeach; ?></ol></section>
 <?php endforeach; ?></div>
 <?php if ($this->editable) : ?>
 <div class="row mt-4"><section class="col-lg-4"><h2><?= Text::_('COM_JLMS_MODULE_EDITOR') ?></h2>
  <form id="jlms-module-form" class="form-validate" method="post" action="<?= $link('index.php?option=com_jlms') ?>">
   <?= $this->moduleForm->renderFieldset('details') ?><input type="hidden" name="task" value="module.save">
   <?= HTMLHelper::_('form.token') ?><button class="btn btn-primary" type="submit"><?= Text::_('JSAVE') ?></button>
  </form></section>
 <section class="col-lg-8"><h2><?= Text::_('COM_JLMS_LESSON_EDITOR') ?></h2>
 <?php if ($this->outline['modules']) : ?>
  <form id="jlms-lesson-form" class="form-validate" method="post" action="<?= $link('index.php?option=com_jlms') ?>">
   <?= $this->lessonForm->renderFieldset('details') ?><input type="hidden" name="task" value="lesson.save">
   <?= HTMLHelper::_('form.token') ?><button class="btn btn-primary" type="submit"><?= Text::_('JSAVE') ?></button>
  </form>
 <?php else : ?><p><?= Text::_('COM_JLMS_CREATE_MODULE_FIRST') ?></p><?php endif; ?>
 </section></div>
 <?php else : ?><p class="alert alert-warning"><?= Text::_('COM_JLMS_VERSION_IMMUTABLE') ?></p><?php endif; ?>
 <?php endif; ?>
</section>
