<?php

declare(strict_types=1);

\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$course = $this->item['course'];
?>
<article id="jlms-course" class="jlms-course" data-course-id="<?= (int) $course->id ?>">
 <header id="jlms-course-header"><h1 id="jlms-course-title"><?= $this->escape($course->title) ?></h1>
  <a id="jlms-course-category" href="<?= Route::_('index.php?option=com_jlms&view=courses&catid=' . (int) $course->catid) ?>"><?= $this->escape($course->category_title) ?></a>
 </header>
 <section id="jlms-course-summary"><h2><?= Text::_('COM_JLMS_COURSE_PRESENTATION') ?></h2><p><?= nl2br($this->escape((string) $course->summary)) ?></p></section>
 <aside id="jlms-course-access" class="jlms-course-access"><p><?= Text::_($this->hasContentAccess ? 'COM_JLMS_CONTENT_ACCESS_ALLOWED' : 'COM_JLMS_CONTENT_ACCESS_REQUIRED') ?></p><p><?= Text::_('COM_JLMS_CLASSROOM_PENDING') ?></p></aside>
 <section id="jlms-course-outline"><h2><?= Text::_('COM_JLMS_CURRICULUM') ?></h2>
 <?php foreach ($this->item['modules'] as $module) : ?>
  <section id="jlms-module-<?= (int) $module->id ?>" class="jlms-module"><h3 class="jlms-module__title"><?= $this->escape($module->title) ?></h3>
   <ol class="jlms-module__lessons"><?php foreach ($this->item['lessons'][(int) $module->id] ?? [] as $lesson) : ?>
    <li id="jlms-lesson-<?= (int) $lesson->id ?>" class="jlms-lesson"><span class="jlms-lesson__title"><?= $this->escape($lesson->title) ?></span> <span class="jlms-lesson__requirement"><?= Text::_($lesson->required ? 'COM_JLMS_REQUIRED' : 'COM_JLMS_OPTIONAL') ?></span></li>
   <?php endforeach; ?></ol>
  </section>
 <?php endforeach; ?></section>
 <a id="jlms-course-back" href="<?= Route::_('index.php?option=com_jlms&view=courses') ?>"><?= Text::_('COM_JLMS_ALL_COURSES') ?></a>
</article>
