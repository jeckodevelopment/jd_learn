<?php

declare(strict_types=1);


\defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
HTMLHelper::_('behavior.formvalidator');
?>
<form action="<?= Route::_('index.php?option=com_jlms&layout=edit&id=' . (int) $this->item->id) ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <p class="alert alert-info"><?= Text::_('COM_JLMS_FOUNDATION_NOTICE') ?></p>
    <section id="jlms-course-publication-help" class="alert alert-info" aria-labelledby="jlms-course-publication-heading">
        <h2 id="jlms-course-publication-heading" class="h4"><?= Text::_('COM_JLMS_PUBLICATION_HEADING') ?></h2>
        <p><?= Text::_('COM_JLMS_PUBLICATION_EDITOR_HELP') ?></p>
        <?php if ((int) $this->item->id > 0) : ?>
        <a id="jlms-course-programme-link" class="btn btn-secondary" href="<?= Route::link('administrator', 'index.php?option=com_jlms&view=curriculum&id=' . (int) $this->item->id) ?>"><?= Text::_('COM_JLMS_PUBLICATION_OPEN_PROGRAMME') ?></a>
        <?php else : ?><p><?= Text::_('COM_JLMS_PUBLICATION_SAVE_FIRST') ?></p><?php endif; ?>
    </section>
    <?php if ($this->readOnly) : ?><p class="alert alert-warning"><?= Text::_($this->checkedOutByOther ? 'COM_JLMS_COURSE_CHECKED_OUT' : 'COM_JLMS_VERSION_IMMUTABLE') ?></p><?php endif; ?>
    <?= HTMLHelper::_('uitab.startTabSet', 'jlms-course-tabs', ['active' => 'jlms-course-general']) ?>
    <?= HTMLHelper::_('uitab.addTab', 'jlms-course-tabs', 'jlms-course-general', Text::_('COM_JLMS_COURSE_GENERAL')) ?>
    <?= $this->form->renderFieldset('details') ?>
    <?= HTMLHelper::_('uitab.endTab') ?>
    <?= HTMLHelper::_('uitab.addTab', 'jlms-course-tabs', 'jlms-course-presentation', Text::_('COM_JLMS_COURSE_PRESENTATION')) ?>
    <p class="alert alert-info"><?= Text::_('COM_JLMS_COURSE_PROMOTIONAL_NOTICE') ?></p>
    <?= $this->form->renderFieldset('presentation') ?>
    <?= HTMLHelper::_('uitab.endTab') ?>
    <?= HTMLHelper::_('uitab.addTab', 'jlms-course-tabs', 'jlms-course-access-settings', Text::_('COM_JLMS_COURSE_ACCESS_SETTINGS')) ?>
    <?= $this->form->renderFieldset('access') ?>
    <?= HTMLHelper::_('uitab.endTab') ?>
    <?= HTMLHelper::_('uitab.addTab', 'jlms-course-tabs', 'jlms-course-teachers-settings', Text::_('COM_JLMS_TEACHERS')) ?>
    <?php if ((int) $this->item->id > 0) : ?><?= $this->form->renderFieldset('teachers') ?><?php else : ?><p><?= Text::_('COM_JLMS_TEACHER_SAVE_FIRST') ?></p><?php endif; ?>
    <?= HTMLHelper::_('uitab.endTab') ?>
    <?= HTMLHelper::_('uitab.addTab', 'jlms-course-tabs', 'jlms-course-metadata', Text::_('COM_JLMS_COURSE_METADATA')) ?>
    <?= $this->form->renderFieldset('metadata') ?>
    <?= HTMLHelper::_('uitab.endTab') ?>
    <?= HTMLHelper::_('uitab.addTab', 'jlms-course-tabs', 'jlms-course-calendar', Text::_('COM_JLMS_CALENDAR')) ?>
    <p><?= Text::_('COM_JLMS_CALENDAR_POLICY') ?></p><?= $this->form->renderFieldset('calendar') ?>
    <?= HTMLHelper::_('uitab.endTab') ?>
    <?= HTMLHelper::_('uitab.addTab', 'jlms-course-tabs', 'jlms-course-publishing', Text::_('COM_JLMS_PUBLICATION_HEADING')) ?>
    <?= $this->form->renderFieldset('publication') ?>
    <p><?= Text::_('COM_JLMS_PUBLICATION_EDITOR_HELP') ?></p>
    <?= HTMLHelper::_('uitab.endTab') ?>
    <?php if ($this->form->getFieldset('item_associations')) : ?>
    <?= HTMLHelper::_('uitab.addTab', 'jlms-course-tabs', 'jlms-course-translations', Text::_('COM_JLMS_ASSOCIATIONS')) ?>
    <section id="jlms-course-associations"><h2><?= Text::_('COM_JLMS_ASSOCIATIONS') ?></h2><p><?= Text::_('COM_JLMS_ASSOCIATIONS_HELP') ?></p><?= $this->form->renderFieldset('item_associations') ?></section>
    <?= HTMLHelper::_('uitab.endTab') ?>
    <?php endif; ?>
    <?= HTMLHelper::_('uitab.addTab', 'jlms-course-tabs', 'jlms-course-permissions', Text::_('COM_JLMS_EDITOR_PERMISSIONS')) ?>
    <section id="jlms-course-effective-rights"><p><?= Text::_('COM_JLMS_EDITOR_PERMISSIONS_HELP') ?></p><dl>
    <?php foreach ($this->editorRights as $label=>$allowed) : ?><dt><?= Text::_($label) ?></dt><dd><?= Text::_($allowed ? 'JYES' : 'JNO') ?></dd><?php endforeach; ?></dl>
    <?php if ($this->canConfigure) : ?><a id="jlms-course-options-link" href="<?= Route::_('index.php?option=com_config&view=component&component=com_jlms') ?>"><?= Text::_('JOPTIONS') ?></a><?php endif; ?></section>
    <?= HTMLHelper::_('uitab.endTab') ?>
    <?= HTMLHelper::_('uitab.endTabSet') ?>
    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token') ?>
</form>
