<?php

declare(strict_types=1);

\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
$item = $this->item;
$selected = $item['lesson'];
$currentModule = 0;
?>
<section id="jlms-classroom" class="jlms-classroom" data-enrollment-id="<?= (int) $item['enrollment_id'] ?>">
 <header id="jlms-classroom-header"><h1><?= $this->escape($item['title']) ?></h1></header>
 <section id="jlms-learning-progress" aria-label="<?= Text::_('COM_JLMS_LEARNING_PROGRESS') ?>">
  <label for="jlms-progress-meter"><?= Text::_('COM_JLMS_LEARNING_PROGRESS') ?>: <?= (int) $item['percent'] ?>%</label>
  <progress id="jlms-progress-meter" max="100" value="<?= (int) $item['percent'] ?>"><?= (int) $item['percent'] ?>%</progress>
  <?php if ($item['completed_at']) : ?><p id="jlms-course-completed" role="status"><?= Text::_('COM_JLMS_LEARNING_COMPLETED') ?></p><?php endif; ?>
 </section>
 <div id="jlms-classroom-layout" class="jlms-classroom-layout">
 <nav id="jlms-classroom-outline" aria-label="<?= Text::_('COM_JLMS_CURRICULUM') ?>">
 <?php foreach ($item['outline'] as $entry) : ?>
  <?php if ((int) $entry->module_id !== $currentModule) : ?>
   <?php if ($currentModule) : ?></ol></section><?php endif; $currentModule = (int) $entry->module_id; ?>
   <section id="jlms-classroom-module-<?= $currentModule ?>" class="jlms-classroom-module"><h2><?= $this->escape($entry->module_title) ?></h2><ol>
  <?php endif; ?>
  <li id="jlms-classroom-lesson-<?= (int) $entry->id ?>" class="jlms-classroom-lesson">
   <a href="<?= Route::_('index.php?option=com_jlms&view=classroom&id=' . (int) $item['enrollment_id'] . '&lesson_id=' . (int) $entry->id) ?>"<?= (int) $selected->id === (int) $entry->id ? ' aria-current="page"' : '' ?>><?= $this->escape($entry->title) ?></a>
   <span><?= Text::_($entry->state === 'completed' ? 'COM_JLMS_LESSON_COMPLETED' : ($entry->required ? 'COM_JLMS_REQUIRED' : 'COM_JLMS_OPTIONAL')) ?></span>
  </li>
 <?php endforeach; ?>
 <?php if ($currentModule) : ?></ol></section><?php endif; ?>
 </nav>
 <article id="jlms-lesson-content" class="jlms-lesson-content"><h2 id="jlms-lesson-title"><?= $this->escape($selected->title) ?></h2>
  <div id="jlms-lesson-body"><?= $selected->body /* HTML cleaned by the server-side core InputFilter. */ ?></div>
  <form id="jlms-complete-form" method="post" action="<?= Route::_('index.php?option=com_jlms') ?>">
   <input type="hidden" name="task" value="lesson.complete">
   <input type="hidden" name="jform[enrollment_id]" value="<?= (int) $item['enrollment_id'] ?>">
   <input type="hidden" name="jform[lesson_id]" value="<?= (int) $selected->id ?>">
   <input type="hidden" name="jform[idempotency_key]" value="<?= $this->escape($this->mutationKey) ?>">
   <?= HTMLHelper::_('form.token') ?><button class="btn btn-primary" type="submit"><?= Text::_('COM_JLMS_MARK_COMPLETE') ?></button>
  </form>
 </article></div>
 <a id="jlms-classroom-back" href="<?= Route::_('index.php?option=com_jlms&view=course&id=' . (int) $item['course_id']) ?>"><?= Text::_('COM_JLMS_COURSE_PRESENTATION') ?></a>
</section>
