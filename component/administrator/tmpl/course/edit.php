<?php

declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
HTMLHelper::_('behavior.formvalidator');
$id = (int) $this->item->id;
?>
<form action="<?= Route::_('index.php?option=com_jlms&layout=edit&id=' . $id) ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
 <section id="jlms-course-publication-help" class="mb-3" aria-labelledby="jlms-course-publication-heading">
  <h2 id="jlms-course-publication-heading" class="visually-hidden"><?= Text::_('COM_JLMS_CURRICULUM') ?></h2>
  <?php if ($id) : ?>
   <a id="jlms-course-programme-link" class="btn btn-secondary" href="<?= Route::link('administrator', 'index.php?option=com_jlms&view=curriculum&id=' . $id) ?>"><?= Text::_('COM_JLMS_CURRICULUM') ?></a>
  <?php else : ?><p class="alert alert-info"><?= Text::_('COM_JLMS_PUBLICATION_SAVE_FIRST') ?></p><?php endif; ?>
 </section>
 <?php if ($this->readOnly) : ?><p class="alert alert-warning"><?= Text::_('COM_JLMS_COURSE_CHECKED_OUT') ?></p><?php endif; ?>
 <?= HTMLHelper::_('uitab.startTabSet', 'jlms-course-tabs', ['active'=>'jlms-course-general']) ?>
 <?= HTMLHelper::_('uitab.addTab', 'jlms-course-tabs', 'jlms-course-general', Text::_('COM_JLMS_COURSE')) ?>
 <div id="jlms-course-editor-layout" class="row">
  <div class="col-lg-9">
   <?= $this->form->renderField('id') ?><?= $this->form->renderField('revision') ?>
   <?= $this->form->renderField('title') ?><?= $this->form->renderField('alias') ?>
   <section id="jlms-course-presentation">
    <?= $this->form->renderField('summary') ?><?= $this->form->renderField('presentation_description') ?>
    <details id="jlms-course-learning-description"><summary><?= Text::_('COM_JLMS_COURSE_OBJECTIVES') ?>, <?= Text::_('COM_JLMS_COURSE_AUDIENCE') ?>, <?= Text::_('COM_JLMS_COURSE_PREREQUISITE_TEXT') ?></summary>
     <?= $this->form->renderField('presentation_objectives') ?><?= $this->form->renderField('presentation_audience') ?><?= $this->form->renderField('presentation_prerequisite_text') ?>
    </details>
   </section>
  </div>
  <aside id="jlms-course-editor-sidebar" class="col-lg-3">
   <?= $this->form->renderField('state') ?><?= $this->form->renderField('catid') ?><?= $this->form->renderField('language') ?>
   <?= $this->form->renderField('content_access') ?>
   <?= $this->form->renderField('presentation_difficulty') ?><?= $this->form->renderField('presentation_estimated_minutes') ?>
   <?= $this->form->renderField('version_note') ?>
  </aside>
 </div>
 <?= HTMLHelper::_('uitab.endTab') ?>
 <?= HTMLHelper::_('uitab.addTab', 'jlms-course-tabs', 'jlms-course-programme', Text::_('COM_JLMS_CURRICULUM')) ?>
 <?php if (!$id) : ?><p><?= Text::_('COM_JLMS_PUBLICATION_SAVE_FIRST') ?></p>
 <?php else : ?>
  <p><?= Text::_('COM_JLMS_COURSE_PROGRAMME_HELP') ?></p>
  <?php if ($this->programme['modules']) : ?>
   <ul id="jlms-course-programme-summary">
   <?php foreach ($this->programme['modules'] as $module) : ?><li><?= $this->escape($module->title) ?></li><?php endforeach; ?>
   </ul>
  <?php else : ?><p><?= Text::_('COM_JLMS_COURSE_PROGRAMME_EMPTY') ?></p><?php endif; ?>
  <?php if (!$this->readOnly) : ?>
   <joomla-toolbar-button task="course.programme" form-validation>
    <button type="button" class="btn btn-primary"><?= Text::_('COM_JLMS_COURSE_SAVE_PROGRAMME') ?></button>
   </joomla-toolbar-button>
  <?php endif; ?>
 <?php endif; ?>
 <?= HTMLHelper::_('uitab.endTab') ?>
 <?= HTMLHelper::_('uitab.addTab', 'jlms-course-tabs', 'jlms-course-teachers-settings', Text::_('COM_JLMS_TEACHERS')) ?>
 <?php if ($id) : ?><?= $this->form->renderFieldset('teachers') ?><?php else : ?><p><?= Text::_('COM_JLMS_TEACHER_SAVE_FIRST') ?></p><?php endif; ?>
 <?= HTMLHelper::_('uitab.endTab') ?>
 <?= HTMLHelper::_('uitab.addTab', 'jlms-course-tabs', 'jlms-course-images', Text::_('COM_JLMS_COURSE_IMAGE')) ?>
 <?= $this->form->renderField('presentation_image') ?><?= $this->form->renderField('presentation_image_alt') ?><?= $this->form->renderField('presentation_image_decorative') ?>
 <?= HTMLHelper::_('uitab.endTab') ?>
 <?= HTMLHelper::_('uitab.addTab', 'jlms-course-tabs', 'jlms-course-publishing', Text::_('COM_JLMS_PUBLICATION_HEADING')) ?>
 <p><?= Text::_('COM_JLMS_COURSE_WORKFLOW_HELP') ?></p>
 <section id="jlms-course-calendar"><?= $this->form->renderFieldset('calendar') ?></section>
 <?= HTMLHelper::_('uitab.endTab') ?>
 <?= HTMLHelper::_('uitab.addTab', 'jlms-course-tabs', 'jlms-course-options', Text::_('JOPTIONS')) ?>
 <section id="jlms-course-access-settings"><h2 class="h4"><?= Text::_('COM_JLMS_COURSE_ACCESS_SETTINGS') ?></h2>
  <?= $this->form->renderField('catalog_access') ?><?= $this->form->renderField('enrollment_mode') ?>
 </section>
 <section id="jlms-course-metadata"><h2 class="h4"><?= Text::_('COM_JLMS_COURSE_METADATA') ?></h2><?= $this->form->renderFieldset('metadata') ?></section>
 <?php if ($this->form->getFieldset('item_associations')) : ?>
  <section id="jlms-course-translations"><h2 class="h4"><?= Text::_('COM_JLMS_ASSOCIATIONS') ?></h2><div id="jlms-course-associations"><?= $this->form->renderFieldset('item_associations') ?></div></section>
 <?php endif; ?>
 <section id="jlms-course-permissions"><h2 class="h4"><?= Text::_('COM_JLMS_EDITOR_PERMISSIONS') ?></h2>
  <details id="jlms-course-effective-rights"><summary><?= Text::_('COM_JLMS_EDITOR_PERMISSIONS_HELP') ?></summary><dl>
  <?php foreach ($this->editorRights as $label=>$allowed) : ?><dt><?= Text::_($label) ?></dt><dd><?= Text::_($allowed ? 'JYES' : 'JNO') ?></dd><?php endforeach; ?></dl>
  <?php if ($this->canConfigure) : ?><a id="jlms-course-options-link" href="<?= Route::_('index.php?option=com_config&view=component&component=com_jlms') ?>"><?= Text::_('JOPTIONS') ?></a><?php endif; ?>
  </details>
  <?php if ($this->canManageCourseRules) : ?>
   <section id="jlms-course-rules"><p><?= Text::_('COM_JLMS_COURSE_RULES_IMMEDIATE') ?></p><?= $this->form->renderField('asset_id') ?><?= $this->form->renderField('rules') ?></section>
  <?php elseif (!$id) : ?><p><?= Text::_('COM_JLMS_COURSE_RULES_SAVE_FIRST') ?></p><?php endif; ?>
 </section>
 <?= HTMLHelper::_('uitab.endTab') ?>
 <?= HTMLHelper::_('uitab.endTabSet') ?>
 <input type="hidden" name="task" value=""><?= HTMLHelper::_('form.token') ?>
</form>
