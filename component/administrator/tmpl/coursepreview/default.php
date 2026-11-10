<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$course = $this->preview['course'];
$version = $this->preview['version'];
$lesson = $this->preview['selected'];
$scope = '&id=' . (int) $course->id . '&version_id=' . (int) $version->id;
$base = 'index.php?option=com_jlms&view=coursepreview' . $scope;
$lessonsByModule = [];
foreach ($this->preview['lessons'] as $entry) {
    $lessonsByModule[(int) $entry->module_id][] = $entry;
}
?>
<section id="jlms-course-preview">
    <h1><?= $this->escape($course->title) ?></h1>
    <p><?= Text::_('COM_JLMS_VERSION') ?> <?= (int) $version->version_no ?> · <?= Text::_('COM_JLMS_REVISION') ?> <?= (int) $version->revision ?></p>
    <p class="alert alert-info"><?= Text::_('COM_JLMS_COURSE_PREVIEW_NOTICE') ?></p>
    <?php $access = $this->preview['access_preview']; ?>
    <section id="jlms-course-access-preview" aria-labelledby="jlms-course-access-preview-title">
        <h2 id="jlms-course-access-preview-title"><?= Text::_('COM_JLMS_ACCESS_PREVIEW_TITLE') ?></h2>
        <p><?= Text::_('COM_JLMS_ACCESS_PREVIEW_NOTICE') ?></p>
        <form method="get" action="<?= Route::_('index.php') ?>">
            <input type="hidden" name="option" value="com_jlms"><input type="hidden" name="view" value="coursepreview">
            <input type="hidden" name="id" value="<?= (int) $course->id ?>"><input type="hidden" name="version_id" value="<?= (int) $version->id ?>">
            <?php if ($lesson) : ?><input type="hidden" name="lesson_id" value="<?= (int) $lesson->id ?>"><?php endif; ?>
            <fieldset><legend><?= Text::_('COM_JLMS_ACCESS_PREVIEW_LEVELS') ?></legend>
            <?php foreach ($access['levels'] as $level) : $levelId = (int) $level->id; ?>
                <label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="access_levels[]" value="<?= $levelId ?>"<?= in_array($levelId, $access['selected'], true) ? ' checked' : '' ?>> <span class="form-check-label"><?= $this->escape($level->title) ?></span></label>
            <?php endforeach; ?>
            </fieldset>
            <button class="btn btn-secondary mt-2" type="submit"><?= Text::_('COM_JLMS_ACCESS_PREVIEW_CHECK') ?></button>
        </form>
        <ul class="mt-3">
            <li><?= Text::_('COM_JLMS_ACCESS_PREVIEW_CATALOG') ?>: <strong><?= Text::_($access['catalog_visible'] ? 'JYES' : 'JNO') ?></strong></li>
            <li><?= Text::_('COM_JLMS_ACCESS_PREVIEW_CONTENT') ?>: <strong><?= Text::_($access['content_allowed'] ? 'JYES' : 'JNO') ?></strong></li>
            <li><?= Text::_('COM_JLMS_ACCESS_PREVIEW_REQUIREMENTS') ?>: <strong><?= Text::_($access['access_requirements_met'] ? 'JYES' : 'JNO') ?></strong></li>
        </ul>
    </section>
    <p><a href="<?= Route::_('index.php?option=com_jlms&view=curriculum' . $scope) ?>"><?= Text::_('COM_JLMS_CURRICULUM') ?></a></p>
    <nav aria-label="<?= Text::_('COM_JLMS_CURRICULUM') ?>">
    <?php foreach ($this->preview['modules'] as $module) : ?>
        <h2><?= $this->escape($module->title) ?></h2>
        <ol>
        <?php foreach ($lessonsByModule[(int) $module->id] ?? [] as $entry) : ?>
            <li><a href="<?= Route::_($base . '&lesson_id=' . (int) $entry->id) ?>"<?= $lesson && $lesson->id === (int) $entry->id ? ' aria-current="page"' : '' ?>><?= $this->escape($entry->title) ?></a>
                · <?= Text::_($entry->required ? 'COM_JLMS_REQUIRED' : 'COM_JLMS_OPTIONAL') ?></li>
        <?php endforeach; ?>
        </ol>
    <?php endforeach; ?>
    </nav>
    <?php if ($lesson) : ?>
        <article id="jlms-course-preview-lesson">
            <h2><?= $this->escape($lesson->title) ?></h2>
            <?= $lesson->body /* Filtered with a strict tag allowlist and no attributes. */ ?>
            <?php if ($lesson->kind === 'video') : ?>
                <section id="jlms-video-region" aria-labelledby="jlms-preview-lesson-title">
                    <h3 id="jlms-preview-lesson-title"><?= $this->escape($lesson->title) ?></h3>
                    <p id="jlms-player-status" role="status" aria-live="polite"></p>
                    <div id="jlms-player" class="jlms-player" data-title="<?= $this->escape($lesson->title) ?>"></div>
                </section>
            <?php elseif (in_array($lesson->kind, ['document', 'audio'], true)) : ?>
                <?php $materials = $lesson->preview_materials ?? ['course' => ['items' => []], 'lesson' => ['items' => []]]; ?>
                <p class="alert alert-info" role="status"><?= Text::_('COM_JLMS_COURSE_PREVIEW_MEDIA_READY') ?></p>
                <?php if ($lesson->kind === 'audio') : ?>
                    <?php $audioFiles = array_values(array_filter($materials['lesson']['items'] ?? [], static fn ($file): bool => in_array($file->mime_type, ['audio/mpeg', 'audio/ogg'], true))); ?>
                    <section id="jlms-preview-audio-region" aria-label="<?= $this->escape($lesson->title) ?>">
                        <?php if ($audioFiles) : ?><audio id="jlms-preview-audio" controls preload="metadata" aria-label="<?= $this->escape($lesson->title) ?>">
                            <?php foreach ($audioFiles as $file) : ?>
                                <?php $src = 'index.php?option=com_jlms&task=attachment.preview&format=raw&course_id=' . (int) $course->id . '&version_id=' . (int) $version->id . '&lesson_id=' . (int) $lesson->id . '&id=' . (int) $file->id; ?>
                                <source src="<?= $this->escape(\Joomla\CMS\Router\Route::link('site', $src, false)) ?>" type="<?= $this->escape($file->mime_type) ?>">
                            <?php endforeach; ?>
                        </audio><?php else : ?><p><?= Text::_('COM_JLMS_NO_MATERIALS') ?></p><?php endif; ?>
                    </section>
                <?php endif; ?>
                <div id="jlms-preview-materials">
                    <?php foreach (['course' => 'COM_JLMS_COURSE_MATERIALS', 'lesson' => 'COM_JLMS_LESSON_MATERIALS'] as $scope => $label) : ?>
                        <?php $files = $materials[$scope]['items'] ?? []; if (!$files) { continue; } ?>
                        <section class="jlms-material-list"><h3><?= Text::_($label) ?></h3>
                            <?php foreach ($files as $file) : if ($lesson->kind === 'audio' && $scope === 'lesson' && in_array($file->mime_type, ['audio/mpeg', 'audio/ogg'], true)) { continue; } ?>
                                <?php $url = 'index.php?option=com_jlms&task=attachment.preview&format=raw&course_id=' . (int) $course->id . '&version_id=' . (int) $version->id . '&lesson_id=' . (int) $lesson->id . '&id=' . (int) $file->id; ?>
                                <article class="jlms-material"><h4><?= $this->escape($file->title) ?></h4>
                                    <?php if ($file->description !== '') : ?><p><?= $this->escape($file->description) ?></p><?php endif; ?>
                                    <p><?= $this->escape($file->mime_type) ?> · <?= (int) $file->byte_size ?> B</p>
                                    <a href="<?= $this->escape(\Joomla\CMS\Router\Route::link('site', $url, false)) ?>"<?= in_array($file->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'text/plain', 'text/csv'], true) ? ' target="_blank" rel="noopener"' : '' ?>><?= Text::_('COM_JLMS_DOWNLOAD') ?></a>
                                </article>
                            <?php endforeach; ?>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($lesson->kind !== 'text') : ?>
                <p><?= Text::_('COM_JLMS_COURSE_PREVIEW_ACTIVITY') ?></p>
            <?php endif; ?>
        </article>
    <?php else : ?>
        <p><?= Text::_($this->preview['lessons'] ? 'COM_JLMS_COURSE_PREVIEW_SELECT' : 'COM_JLMS_COURSE_PREVIEW_EMPTY') ?></p>
    <?php endif; ?>
</section>
