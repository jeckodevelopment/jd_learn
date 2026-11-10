<?php

declare(strict_types=1);


\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Registry\Registry;
HTMLHelper::_('behavior.formvalidator');
$course = $this->outline['course'];
$version = $this->outline['version'];
$base = 'index.php?option=com_jlms&view=curriculum&id=' . (int) $course->id;
$link = static fn (string $url): string => Route::link('administrator', $url);
// Legacy installed workflows use action keys absent from Joomla's global language file.
// Preserve administrator-defined titles and translate only our two original default titles.
$transitionLabel = static fn (object $transition): string => Text::_(match ($transition->title) {
    'JPUBLISH' => 'COM_JLMS_PUBLISH_ACTION',
    'JUNPUBLISH' => 'COM_JLMS_UNPUBLISH_ACTION',
    default => $transition->title,
});
$transitionAction = static fn (object $transition): string => (string) (new Registry($transition->options))->get('jlms_action', '');
$transitionDisplay = static function (object $transition) use ($transitionLabel, $transitionAction): string {
    return Text::_(match ($transitionAction($transition)) {
        'review' => 'COM_JLMS_SUBMIT_REVIEW',
        'return' => 'COM_JLMS_RETURN_TO_DRAFT',
        'publish' => 'COM_JLMS_APPROVE_REVIEW',
        'archive' => 'COM_JLMS_ARCHIVE_COURSE',
        'restore' => 'COM_JLMS_RESTORE_COURSE',
        default => $transition->title === 'JPUBLISH' ? 'COM_JLMS_PUBLISH_ACTION'
            : ($transition->title === 'JUNPUBLISH' ? 'COM_JLMS_UNPUBLISH_ACTION' : $transition->title),
    });
};
$scope = $version ? ['course_id' => $course->id, 'version_id' => $version->id, 'expected_version_revision' => $version->revision] : [];
$hidden = static function (array $fields): void {
    foreach ($fields as $name => $value) {
        echo '<input type="hidden" name="jform[' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ']" value="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '">';
    }
};
$view = $base . ($version ? '&version_id=' . (int) $version->id : '');
$actionUrl = static fn (string $action, int $id): string => $link($view . '&item_action=' . $action . '&item_id=' . $id) . '#jlms-item-action';
$lessonUrl = static fn (object $lesson): string => $lesson->kind === 'quiz'
    ? $link('index.php?option=com_jlms&view=quiz&id=' . (int) $course->id . '&version_id=' . (int) $version->id . '&lesson_id=' . (int) $lesson->id)
    : $link($view . '&edit_lesson=' . (int) $lesson->id) . '#jlms-lesson-form';
$actions = function (string $kind, object $item, int $position, int $total) use ($actionUrl): void {
    ?><details class="jlms-item-menu"><summary><?= Text::_('COM_JLMS_PROGRAMME_ACTIONS') ?><span class="visually-hidden">: <?= $this->escape($item->title) ?></span></summary><ul>
    <li><a href="<?= $actionUrl('copy_' . $kind, (int) $item->id) ?>"><?= Text::_($kind === 'module' ? 'COM_JLMS_DUPLICATE_MODULE' : 'COM_JLMS_DUPLICATE_LESSON') ?></a></li>
    <?php if ($kind === 'lesson') : ?><li><a href="<?= $actionUrl('move_lesson', (int) $item->id) ?>"><?= Text::_('COM_JLMS_PROGRAMME_MOVE_LESSON') ?></a></li><?php endif; ?>
    <?php foreach (['up','down'] as $direction) : if (($direction === 'up' && !$position) || ($direction === 'down' && $position === $total - 1)) { continue; } ?>
    <li><a data-order-kind="<?= $kind ?>" data-order-id="<?= (int) $item->id ?>" data-direction="<?= $direction ?>" href="<?= $this->escape(str_replace('#jlms-item-action', '&direction=' . $direction . '#jlms-item-action', html_entity_decode($actionUrl('order_' . $kind, (int) $item->id), ENT_QUOTES, 'UTF-8'))) ?>"><?= Text::_($direction === 'up' ? 'COM_JLMS_MOVE_UP' : 'COM_JLMS_MOVE_DOWN') ?></a></li>
    <?php endforeach; ?>
    <?php if ($this->canDelete && ($kind === 'module' || $item->kind !== 'quiz')) : ?><li><a href="<?= $actionUrl('delete_' . $kind, (int) $item->id) ?>"><?= Text::_('JACTION_DELETE') ?></a></li><?php endif; ?>
    </ul></details><?php
};
?>
<section id="jlms-admin-curriculum" data-editor-mode="<?= $this->escape($this->editorMode) ?>" data-revision="<?= $version ? (int) $version->revision : 0 ?>">
 <header class="jlms-programme-header">
  <a href="<?= $link('index.php?option=com_jlms&task=course.edit&id=' . (int) $course->id) ?>"><?= Text::_('COM_JLMS_PROGRAMME_BACK_COURSE') ?></a>
  <h1><?= $this->escape($course->title) ?></h1>
  <p><?= Text::_('COM_JLMS_PROGRAMME_INTRO') ?></p>
 </header>
 <?php if (!$version) : ?>
 <p id="jlms-publication-notice" class="alert alert-info"><?= Text::_($this->publicationNotice) ?></p>
 <form method="post" id="jlms-version-form" action="<?= $link('index.php?option=com_jlms') ?>">
  <?php $hidden(['course_id'=>$course->id,'revision'=>$course->revision]); ?><input type="hidden" name="task" value="curriculum.createVersion">
  <?= HTMLHelper::_('form.token') ?><button class="btn btn-primary" type="submit"><?= Text::_('COM_JLMS_CREATE_DRAFT_VERSION') ?></button>
 </form>
 <?php else : ?>
 <div class="jlms-programme-layout"><div class="jlms-programme-main">
 <?php if ($this->editable && $course->current_version_id !== null) : ?><p class="alert alert-info"><?= Text::_('COM_JLMS_WORKING_REVISION_NOTICE') ?></p><?php endif; ?>
 <?php if ($this->inReview) : ?><p class="alert alert-info" id="jlms-version-in-review"><?= Text::_('COM_JLMS_VERSION_IN_REVIEW') ?></p><?php endif; ?>
 <?php if (!$this->editable) : ?><p class="alert alert-info"><?= Text::_('COM_JLMS_VERSION_IMMUTABLE') ?></p><?php endif; ?>
 <p id="jlms-order-status" role="status" aria-live="polite" data-reload="<?= Text::_('COM_JLMS_PROGRAMME_RELOAD') ?>" data-saving="<?= Text::_('COM_JLMS_PROGRAMME_SAVING') ?>" data-error="<?= Text::_('COM_JLMS_PROGRAMME_ORDER_FAILED') ?>"></p>
 <?php if ($this->editable && $this->outline['modules']) : ?><p class="small text-muted"><?= Text::_('COM_JLMS_PROGRAMME_ORDER_HINT') ?></p><?php endif; ?>
 <div id="jlms-admin-course-outline" data-sort-kind="module" data-module-id="0">
 <?php foreach ($this->outline['modules'] as $modulePosition => $module) : $lessons = $this->lessonsByModule[(int) $module->id] ?? []; ?>
 <section id="jlms-module-<?= (int) $module->id ?>" class="jlms-admin-module" data-item-id="<?= (int) $module->id ?>" data-module-id="<?= (int) $module->id ?>">
  <details<?= (!$modulePosition || $this->selectedModule === (int) $module->id || ($this->editorMode === 'module' && (int) $this->moduleForm->getValue('id') === (int) $module->id)) ? ' open' : '' ?>>
   <summary><span class="jlms-module-title"><?= $this->escape($module->title) ?></span><span class="badge bg-secondary"><?= count($lessons) ?> <?= Text::_('COM_JLMS_PROGRAMME_LESSONS') ?></span></summary>
   <div class="jlms-module-content">
    <?php if ($this->editable) : ?><div class="jlms-programme-buttons">
     <button class="btn btn-sm btn-outline-secondary jlms-drag-handle" type="button" draggable="true" data-drag-handle="module" aria-label="<?= $this->escape(Text::sprintf('COM_JLMS_PROGRAMME_DRAG', $module->title)) ?>" title="<?= Text::_('COM_JLMS_PROGRAMME_ORDER_HINT') ?>"><span aria-hidden="true">↕</span></button>
     <a class="btn btn-sm btn-secondary" href="<?= $link($view . '&edit_module=' . (int) $module->id) ?>#jlms-module-form"><?= Text::_('COM_JLMS_PROGRAMME_EDIT_MODULE') ?></a>
     <?php $actions('module', $module, $modulePosition, count($this->outline['modules'])); ?>
    </div><?php endif; ?>
    <?php if (!$lessons) : ?><p class="text-muted"><?= Text::_('COM_JLMS_PROGRAMME_EMPTY_MODULE') ?></p><?php endif; ?>
    <ol class="jlms-programme-lessons" data-sort-kind="lesson" data-module-id="<?= (int) $module->id ?>">
    <?php foreach ($lessons as $position => $lesson) : ?>
     <li id="jlms-lesson-<?= (int) $lesson->id ?>" data-item-id="<?= (int) $lesson->id ?>">
      <?php if ($this->editable) : ?><button class="btn btn-sm btn-outline-secondary jlms-drag-handle" type="button" draggable="true" data-drag-handle="lesson" aria-label="<?= $this->escape(Text::sprintf('COM_JLMS_PROGRAMME_DRAG', $lesson->title)) ?>" title="<?= Text::_('COM_JLMS_PROGRAMME_ORDER_HINT') ?>"><span aria-hidden="true">↕</span></button><?php endif; ?>
      <div class="jlms-lesson-label">
       <?php if (($this->editable && ($lesson->kind !== 'quiz' || $this->canQuiz)) || (!$this->editable && $this->canMaterials && $lesson->provider_key === 'html5')) : ?><a class="jlms-lesson-title" href="<?= $lessonUrl($lesson) ?>"><?= $this->escape($lesson->title) ?></a><?php else : ?><strong><?= $this->escape($lesson->title) ?></strong><?php endif; ?>
       <div class="small text-muted"><?= Text::_(match ($lesson->kind) { 'text'=>'COM_JLMS_LESSON_TEXT','document'=>'COM_JLMS_LESSON_DOCUMENT','audio'=>'COM_JLMS_LESSON_AUDIO','video'=>'COM_JLMS_LESSON_VIDEO',default=>'COM_JLMS_LESSON_QUIZ' }) ?> · <?= Text::_($lesson->required ? 'COM_JLMS_REQUIRED' : 'COM_JLMS_OPTIONAL') ?></div>
      </div>
      <?php if ($this->editable) : $actions('lesson', $lesson, $position, count($lessons)); endif; ?>
     </li>
    <?php endforeach; ?></ol>
    <?php if ($this->editable) : ?><div class="jlms-programme-buttons jlms-add-lesson">
     <a class="btn btn-sm btn-outline-primary" href="<?= $link($view . '&new_lesson=1&module_id=' . (int) $module->id) ?>#jlms-lesson-form"><?= Text::_('COM_JLMS_PROGRAMME_ADD_LESSON') ?></a>
     <a class="btn btn-sm btn-outline-secondary" href="<?= $link($view . '&new_lesson=1&lesson_kind=video&module_id=' . (int) $module->id) ?>#jlms-lesson-form"><?= Text::_('COM_JLMS_LESSON_VIDEO') ?></a>
     <a class="btn btn-sm btn-outline-secondary" href="<?= $link($view . '&new_lesson=1&lesson_kind=document&module_id=' . (int) $module->id) ?>#jlms-lesson-form"><?= Text::_('COM_JLMS_LESSON_DOCUMENT') ?></a>
     <a class="btn btn-sm btn-outline-secondary" href="<?= $link($view . '&new_lesson=1&lesson_kind=audio&module_id=' . (int) $module->id) ?>#jlms-lesson-form"><?= Text::_('COM_JLMS_LESSON_AUDIO') ?></a>
     <?php if ($this->canQuiz) : ?><a class="btn btn-sm btn-outline-secondary" href="<?= $link('index.php?option=com_jlms&view=quiz&id=' . (int) $course->id . '&version_id=' . (int) $version->id . '&module_id=' . (int) $module->id) ?>"><?= Text::_('COM_JLMS_QUIZ_NEW') ?></a><?php endif; ?>
    </div><?php endif; ?>
   </div>
  </details>
 </section>
 <?php endforeach; ?></div>
 <?php if (!$this->outline['modules']) : ?><p class="alert alert-info"><?= Text::_('COM_JLMS_CREATE_MODULE_FIRST') ?></p><?php endif; ?>
<?php if ($version && ($this->editable ? $this->editorMode === 'rules' : \Joomla\CMS\Factory::getApplication()->getInput()->getBool('edit_rules'))) : ?>
 <section id="jlms-teaching-rules" class="<?= $this->editable && $this->rulesForm ? 'jlms-focused-editor' : '' ?>" aria-labelledby="jlms-teaching-rules-heading">
  <h2 id="jlms-teaching-rules-heading" class="h3"><?= Text::_('COM_JLMS_TEACHING_RULES') ?></h2>
  <?php if ($this->teachingRules === null) : ?><p class="alert alert-warning"><?= Text::_('COM_JLMS_PUBLICATION_UNSUPPORTED') ?></p>
  <?php else : ?>
   <p><?= Text::_('COM_JLMS_TEACHING_RULES_SUPPORTED') ?></p>
   <p><?= Text::_('COM_JLMS_TEACHING_RULES_SCOPE') ?></p>
   <?php if ($this->editable && $this->rulesForm) : ?>
   <form id="jlms-teaching-rules-form" class="form-validate" method="post" action="<?= $link('index.php?option=com_jlms') ?>">
    <?= $this->rulesForm->renderFieldset('details') ?><input type="hidden" name="task" value="curriculum.saveTeachingRules">
    <?= HTMLHelper::_('form.token') ?><button class="btn btn-primary" type="submit"><?= Text::_('JSAVE') ?></button>
    <a class="btn btn-secondary" href="<?= $link($view) ?>"><?= Text::_('JCANCEL') ?></a>
   </form>
   <?php else : ?><p><?= Text::_('COM_JLMS_SEQUENCE_MODE') ?>: <?= Text::_($this->teachingRules['sequence_mode'] === 'sequential' ? 'COM_JLMS_SEQUENCE_SEQUENTIAL' : 'COM_JLMS_SEQUENCE_FREE') ?></p><p><?= Text::_('COM_JLMS_TEACHING_THRESHOLD') ?>: <?= $this->escape(\Jlms\Component\Jlms\Administrator\Domain\TeachingRules::percent($this->teachingRules['video_threshold_basis_points'])) ?>%</p><p><?= Text::_('COM_JLMS_TEACHING_RULES_READ_ONLY') ?></p><?php endif; ?>
  <?php endif; ?>
 </section>
<?php endif; ?>
<?php if ($this->editable && in_array($this->editorMode, ['module','lesson'], true)) : $isModule = $this->editorMode === 'module'; $form = $isModule ? $this->moduleForm : $this->lessonForm; ?>
 <section class="jlms-focused-editor" aria-labelledby="jlms-editor-heading">
  <h2 id="jlms-editor-heading" class="h3"><?= Text::_($isModule ? 'COM_JLMS_MODULE_EDITOR' : 'COM_JLMS_LESSON_EDITOR') ?></h2>
  <?php if (!$isModule) : ?><p><?= Text::_('COM_JLMS_MODULE') ?>: <strong><?php foreach ($this->outline['modules'] as $m) { if ((int) $m->id === $this->selectedModule) { echo $this->escape($m->title); } } ?></strong></p><?php endif; ?>
  <form id="jlms-<?= $isModule ? 'module' : 'lesson' ?>-form" class="form-validate" method="post" action="<?= $link('index.php?option=com_jlms') ?>">
   <?= $form->renderFieldset('details') ?>
   <?php if (!$isModule && $form->getFieldset('com_fields')) : ?><fieldset id="jlms-lesson-fields"><legend><?= Text::_('JFIELD_CUSTOM_FIELDS') ?></legend><?= $form->renderFieldset('com_fields') ?></fieldset><?php endif; ?>
   <input type="hidden" name="task" value="<?= $isModule ? 'module' : 'lesson' ?>.save">
   <?php if (!$isModule) : ?><section id="jlms-video-metadata" hidden aria-label="<?= Text::_('COM_JLMS_VIDEO_DURATION_AUTO') ?>">
    <p role="status" aria-live="polite"></p>
    <div id="jlms-video-metadata-preview" data-preview data-title="<?= Text::_('COM_JLMS_VIDEO_DURATION_PREVIEW') ?>" class="ratio ratio-16x9" hidden></div>
    <button class="btn btn-secondary mb-2" type="button"><?= Text::_('COM_JLMS_VIDEO_DURATION_RETRY') ?></button>
   </section><?php endif; ?>
   <?= HTMLHelper::_('form.token') ?><button class="btn btn-primary" type="submit"><?= Text::_('JSAVE') ?></button>
   <a class="btn btn-secondary" href="<?= $link($view) ?>"><?= Text::_('JCANCEL') ?></a>
  </form>
  <?php if (!$isModule && $this->canMaterials && $this->selectedItem) : ?>
   <p><a class="btn btn-secondary" href="<?= $link('index.php?option=com_jlms&view=materials&id=' . (int) $course->id . '&version_id=' . (int) $version->id . '&lesson_id=' . (int) $this->selectedItem->id) ?>"><?= Text::_('COM_JLMS_MATERIALS') ?></a></p>
  <?php endif; ?>
 </section>
 <?php endif; ?>
<?php $lesson = $this->selectedItem && isset($this->selectedItem->kind) ? $this->selectedItem : null; ?>
   <?php if ($lesson && $lesson->provider_key === 'html5' && \Joomla\CMS\Factory::getApplication()->getIdentity()->authorise('jlms.materials.manage', 'com_jlms.course.' . (int) $course->id)) : ?>
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
   <?php endif; ?>
<?php if ($this->editable && $this->editorMode === 'action') :
 $item = $this->selectedItem; $action = $this->itemAction; $kind = str_ends_with($action, '_module') ? 'module' : 'lesson';
 $operation = str_starts_with($action, 'copy_') ? 'duplicate' : (str_starts_with($action, 'delete_') ? 'delete' : (str_starts_with($action, 'order_') ? 'reorder' : 'move'));
 $heading = match ($operation) { 'duplicate' => $kind === 'module' ? 'COM_JLMS_DUPLICATE_MODULE' : 'COM_JLMS_DUPLICATE_LESSON', 'delete'=>'JACTION_DELETE', 'reorder'=>'COM_JLMS_PROGRAMME_REORDER', default=>'COM_JLMS_PROGRAMME_MOVE_LESSON' };
 ?>
 <section class="jlms-focused-editor" id="jlms-item-action"><h2 class="h3"><?= Text::_($heading) ?>: <?= $this->escape($item->title) ?></h2>
 <form method="post" action="<?= $link('index.php?option=com_jlms') ?>">
  <?php $hidden($scope + ['id'=>$item->id,'revision'=>$item->revision]); ?><input type="hidden" name="task" value="<?= $kind . '.' . $operation ?>">
  <?php if ($operation === 'duplicate') : ?><label for="jlms-copy-title"><?= Text::_('COM_JLMS_COPY_TITLE') ?></label><input id="jlms-copy-title" class="form-control" name="jform[title]" maxlength="255" required value="<?= $this->escape(\Joomla\String\StringHelper::substr(Text::sprintf('COM_JLMS_COPY_OF', $item->title), 0, 255)) ?>"><?php endif; ?>
  <?php if ($kind === 'lesson' && in_array($operation, ['duplicate','move'], true)) : ?>
   <label for="jlms-target-module"><?= Text::_('COM_JLMS_COPY_DESTINATION_MODULE') ?></label><select id="jlms-target-module" name="jform[module_id]" class="form-select" required>
   <?php foreach ($this->outline['modules'] as $destination) : ?><option value="<?= (int) $destination->id ?>"<?= (int) $destination->id === (int) $item->module_id ? ' selected' : '' ?>><?= $this->escape($destination->title) ?></option><?php endforeach; ?>
   </select>
  <?php endif; ?>
  <?php if ($operation === 'reorder') : $direction = \Joomla\CMS\Factory::getApplication()->getInput()->getCmd('direction'); $direction = $direction === 'down' ? 'down' : 'up'; ?>
   <?php $hidden(['direction'=>$direction]); ?><p><?= Text::_($direction === 'up' ? 'COM_JLMS_MOVE_UP' : 'COM_JLMS_MOVE_DOWN') ?></p>
  <?php endif; ?>
  <?php if ($operation === 'delete') : ?><p><?= Text::_($kind === 'module' ? 'COM_JLMS_DELETE_EMPTY_MODULE' : 'COM_JLMS_CONFIRM_DELETE_LESSON') ?></p><label><input type="checkbox" name="jform[confirmation]" value="1" required> <?= Text::_('COM_JLMS_PROGRAMME_CONFIRM_DELETE') ?></label><?php endif; ?>
  <div class="jlms-programme-buttons"><?= HTMLHelper::_('form.token') ?><button class="btn <?= $operation === 'delete' ? 'btn-danger' : 'btn-primary' ?>" type="submit"><?= Text::_($heading) ?></button><a class="btn btn-secondary" href="<?= $link($view) ?>"><?= Text::_('JCANCEL') ?></a></div>
 </form></section>
 <?php endif; ?>
 </div><aside class="jlms-programme-sidebar" aria-label="<?= Text::_('COM_JLMS_PUBLICATION_HEADING') ?>">
 <section id="jlms-publication-status"><h2 class="h4"><?= Text::_('COM_JLMS_PUBLICATION_HEADING') ?></h2>
 <p><?= Text::_('JSTATUS') ?>: <strong><?= Text::_((int) $course->state === 1 ? 'JPUBLISHED' : 'JUNPUBLISHED') ?></strong></p>
 <p><?= Text::_('COM_JLMS_VERSION') ?> <?= (int) $version->version_no ?> · <?= Text::_($this->inReview && $version->status === 'draft' ? 'COM_JLMS_IN_REVIEW' : ($version->status === 'draft' ? 'COM_JLMS_DRAFT' : ($version->status === 'published' ? 'JPUBLISHED' : 'JARCHIVED'))) ?></p>
 <p><?= count($this->outline['modules']) ?> <?= Text::_('COM_JLMS_PROGRAMME_MODULES') ?> · <?= count($this->outline['lessons']) ?> <?= Text::_('COM_JLMS_PROGRAMME_LESSONS') ?></p>
 <?php if ($version->status === 'draft') : ?>
 <section id="jlms-publication-readiness" aria-labelledby="jlms-publication-readiness-heading">
  <h3 id="jlms-publication-readiness-heading" class="h5"><?= Text::_('COM_JLMS_READINESS_HEADING') ?></h3>
  <?php if ($this->publicationReadiness['blockers']) : ?>
   <div class="alert alert-danger" id="jlms-publication-blockers"><h4 class="h6"><?= Text::_('COM_JLMS_READINESS_BLOCKERS') ?></h4><ul>
   <?php foreach ($this->publicationReadiness['blockers'] as $message) : ?><li data-readiness-code="<?= $this->escape($message) ?>"><?= Text::_($message) ?></li><?php endforeach; ?>
   </ul></div>
  <?php else : ?><p class="alert alert-success" id="jlms-publication-blockers"><?= Text::_('COM_JLMS_READINESS_STRUCTURE_OK') ?></p><?php endif; ?>
  <?php if ($this->publicationReadiness['warnings']) : ?>
   <div class="alert alert-warning" id="jlms-publication-warnings"><h4 class="h6"><?= Text::_('COM_JLMS_READINESS_WARNINGS') ?></h4><ul>
   <?php foreach ($this->publicationReadiness['warnings'] as $message) : ?><li data-readiness-code="<?= $this->escape($message) ?>"><?= Text::_($message) ?></li><?php endforeach; ?>
   </ul></div>
  <?php endif; ?>
  <p class="small text-muted"><?= Text::_('COM_JLMS_READINESS_FINAL_CHECK') ?></p>
 </section>
 <?php endif; ?>
 <?php if ($this->transitions) : ?>
 <form method="post" id="jlms-publication-form" action="<?= $link('index.php?option=com_jlms') ?>">
  <?php $hidden($scope + ['revision' => $course->revision]); ?>
  <input type="hidden" name="task" value="curriculum.transition">
  <label for="jlms-transition"><?= Text::_('COM_JLMS_WORKFLOW_TRANSITION') ?></label>
  <select id="jlms-transition" name="jform[transition_id]" class="form-select">
  <?php foreach ($this->transitions as $transition) : ?><option value="<?= (int) $transition->id ?>"><?= $this->escape($transitionDisplay($transition)) ?></option><?php endforeach; ?>
  </select>
  <?= HTMLHelper::_('form.token') ?><button id="jlms-publication-submit" type="submit" class="btn btn-primary"><?php if (count($this->transitions) !== 1) : ?><?= Text::_('COM_JLMS_EXECUTE_TRANSITION') ?><?php elseif ($transitionAction($this->transitions[0]) === 'review') : ?><?= Text::_('COM_JLMS_SUBMIT_REVIEW') ?><?php elseif ($transitionAction($this->transitions[0]) === 'return') : ?><?= Text::_('COM_JLMS_RETURN_TO_DRAFT') ?><?php elseif ($version->status === 'draft' && $course->current_version_id !== null && $transitionAction($this->transitions[0]) === 'publish') : ?><?= Text::_('COM_JLMS_PUBLICATION_REVIEW_ACTION') ?><?php else : ?><?= $this->escape($transitionDisplay($this->transitions[0])) ?><?php endif; ?></button>
 </form><?php endif; ?>
 <?php if ($version->status === 'published' && (int)$course->current_version_id === (int)$version->id) : ?>
 <form method="post" id="jlms-edit-programme-form" action="<?= $link('index.php?option=com_jlms') ?>">
  <?php $hidden(['course_id' => $course->id, 'revision' => $course->revision]); ?>
  <input type="hidden" name="task" value="curriculum.createVersion">
  <?= HTMLHelper::_('form.token') ?><button class="btn btn-primary" type="submit"><?= Text::_('COM_JLMS_EDIT_PROGRAMME') ?></button>
 </form>
 <?php endif; ?>
 <?php if ($this->publicationNotice !== '') : ?><p id="jlms-publication-notice" class="small text-muted"><?= Text::_($this->publicationNotice) ?></p><?php endif; ?>
 </section>
 <?php if ($this->canMaterials) : ?><p><a id="jlms-manage-materials" class="btn btn-secondary" href="<?= $link('index.php?option=com_jlms&view=materials&id=' . (int) $course->id . '&version_id=' . (int) $version->id) ?>"><?= Text::_('COM_JLMS_MATERIALS') ?></a></p><?php endif; ?>
 <details class="jlms-programme-versions"><summary><?= Text::_('COM_JLMS_VERSIONS') ?></summary><nav aria-label="<?= Text::_('COM_JLMS_VERSIONS') ?>"><ul>
 <?php foreach ($this->outline['versions'] as $entry) : ?><li><a href="<?= $link($base . '&version_id=' . (int) $entry->id) ?>"<?= (int) $entry->id === (int) $version->id ? ' aria-current="page"' : '' ?>>v<?= (int) $entry->version_no ?> · <?= Text::_($this->inReview && (int) $entry->id === (int) $version->id ? 'COM_JLMS_IN_REVIEW' : ($entry->status === 'draft' ? 'COM_JLMS_DRAFT' : ($entry->status === 'published' ? 'JPUBLISHED' : 'JARCHIVED'))) ?></a></li><?php endforeach; ?>
 </ul></nav></details>
 <?php if ($this->canRestoreProgramme) : ?>
 <details id="jlms-restore-programme"><summary><?= Text::_('COM_JLMS_PROGRAMME_RESTORE_ACTION') ?></summary>
  <p><?= Text::sprintf('COM_JLMS_PROGRAMME_RESTORE_NOTICE', (int)$version->version_no) ?></p>
  <p><?= Text::_('COM_JLMS_PROGRAMME_RESTORE_KEEP_DRAFTS') ?></p>
  <form method="post" id="jlms-restore-programme-form" action="<?= $link('index.php?option=com_jlms') ?>">
   <?php $hidden($scope + ['revision'=>$course->revision]); ?><input type="hidden" name="task" value="curriculum.restoreVersion">
   <label for="jlms-restore-confirm"><input type="checkbox" id="jlms-restore-confirm" name="jform[confirmation]" value="1" required> <?= Text::_('COM_JLMS_PROGRAMME_RESTORE_CONFIRM') ?></label>
   <?= HTMLHelper::_('form.token') ?><button class="btn btn-secondary mt-2" type="submit"><?= Text::_('COM_JLMS_PROGRAMME_RESTORE_ACTION') ?></button>
  </form>
 </details>
 <?php endif; ?>
 <?php if ($this->canDuplicateCourse) : ?>
 <details id="jlms-duplicate-course"><summary><?= Text::_('COM_JLMS_DUPLICATE_COURSE') ?></summary>
 <p><?= Text::_('COM_JLMS_DUPLICATE_COURSE_NOTICE') ?></p>
 <form method="post" action="<?= $link('index.php?option=com_jlms') ?>">
  <?php $hidden($scope + ['revision' => $course->revision]); ?><input type="hidden" name="task" value="curriculum.duplicateCourse">
  <label for="jlms-copy-course-title"><?= Text::_('COM_JLMS_COPY_TITLE') ?></label>
  <input id="jlms-copy-course-title" class="form-control" name="jform[title]" maxlength="255" required value="<?= $this->escape(\Joomla\String\StringHelper::substr(Text::sprintf('COM_JLMS_COPY_OF', $course->title), 0, 255)) ?>">
  <?= HTMLHelper::_('form.token') ?><button class="btn btn-secondary" type="submit"><?= Text::_('COM_JLMS_DUPLICATE_COURSE') ?></button>
 </form></details>
 <?php endif; ?>
<?php if ($this->editable) : ?>
  <details id="jlms-video-setup">
   <summary><?= Text::_('COM_JLMS_VIDEO_EDITOR_SETUP') ?></summary>
   <ul>
   <?php foreach ($this->videoProviders as $label => $status) : ?>
    <li><strong><?= $this->escape($label) ?></strong> — <?= Text::_($status) ?></li>
   <?php endforeach; ?>
   </ul>
   <p><?= Text::_('COM_JLMS_VIDEO_EDITOR_INSTRUCTIONS') ?></p>
   <?php if ($this->canManagePlugins) : ?>
    <p><a href="<?= $link('index.php?option=com_plugins&filter[folder]=jlmsvideo') ?>"><?= Text::_('COM_JLMS_VIDEO_EDITOR_PLUGINS') ?></a></p>
   <?php endif; ?>
   <?php if ($this->canConfigureVideo) : ?>
    <p><a href="<?= $link('index.php?option=com_config&view=component&component=com_jlms') ?>"><?= Text::_('COM_JLMS_VIDEO_EDITOR_OPTIONS') ?></a></p>
   <?php endif; ?>
  </details>
<?php endif; ?>
</aside></div>
<?php if ($this->editable) : ?>
 <form id="jlms-reorder-form" method="post" action="<?= $link('index.php?option=com_jlms') ?>" hidden>
  <?php $hidden($scope + ['id'=>0,'direction'=>'up','kind'=>'module','module_id'=>0]); ?><input type="hidden" name="task" value="module.reorder"><?= HTMLHelper::_('form.token') ?>
 </form>
 <?php endif; ?>
 <?php endif; ?>
</section>
