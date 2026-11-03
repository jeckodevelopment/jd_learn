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
            <?php if ($lesson->kind !== 'text') : ?>
                <p><?= Text::_('COM_JLMS_COURSE_PREVIEW_ACTIVITY') ?></p>
            <?php endif; ?>
        </article>
    <?php else : ?>
        <p><?= Text::_($this->preview['lessons'] ? 'COM_JLMS_COURSE_PREVIEW_SELECT' : 'COM_JLMS_COURSE_PREVIEW_EMPTY') ?></p>
    <?php endif; ?>
</section>
