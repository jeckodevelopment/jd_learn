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
 <header id="jlms-classroom-header"><h1><?= $this->escape($item['title']) ?></h1><a id="jlms-classroom-dashboard" href="<?= Route::_('index.php?option=com_jlms&view=dashboard') ?>"><?= Text::_('COM_JLMS_MY_COURSES') ?></a></header>
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
  <?php if (!empty($selected->custom_fields)) : ?><dl id="jlms-lesson-custom-fields" class="jlms-custom-fields">
   <?php foreach ($selected->custom_fields as $field) : if (!$field['values']) { continue; } ?>
    <dt><?= $this->escape($field['label']) ?></dt><dd><?= $this->escape(implode(', ', $field['values'])) ?></dd>
   <?php endforeach; ?>
  </dl><?php endif; ?>
  <?php if ($selected->kind === 'video') : ?>
  <section id="jlms-video-region" aria-labelledby="jlms-lesson-title">
   <p id="jlms-player-status" role="status" aria-live="polite"></p>
   <div id="jlms-player" class="jlms-player" data-title="<?= $this->escape($selected->title) ?>"></div>
  </section>
  <?php elseif ($selected->kind === 'audio' && !empty($selected->audio_attachment)) : ?>
  <section id="jlms-audio-region" aria-labelledby="jlms-lesson-title">
   <audio id="jlms-audio-player" controls preload="metadata" data-resume-ms="<?= (int) ($selected->resume_ms ?? 0) ?>" aria-label="<?= $this->escape($selected->title) ?>">
    <source src="<?= Route::_('index.php?option=com_jlms&task=attachment.audio&format=raw&id=' . (int) $selected->audio_attachment['id'] . '&lesson_id=' . (int) $selected->id . '&enrollment_id=' . (int) $item['enrollment_id']) ?>" type="<?= $this->escape($selected->audio_attachment['mime']) ?>">
   </audio>
   <p id="jlms-audio-progress-status" role="status" aria-live="polite"></p>
   <form id="jlms-complete-form" method="post" action="<?= Route::_('index.php?option=com_jlms') ?>">
    <input type="hidden" name="task" value="lesson.complete">
    <input type="hidden" name="jform[enrollment_id]" value="<?= (int) $item['enrollment_id'] ?>">
    <input type="hidden" name="jform[lesson_id]" value="<?= (int) $selected->id ?>">
    <input type="hidden" name="jform[idempotency_key]" value="<?= $this->escape($this->mutationKey) ?>">
    <?= HTMLHelper::_('form.token') ?><button class="btn btn-primary" type="submit"><?= Text::_('COM_JLMS_MARK_COMPLETE') ?></button>
   </form>
  </section>
  <?php elseif ($selected->kind === 'quiz') : ?>
  <a id="jlms-lesson-quiz" class="btn btn-primary" href="<?= Route::_('index.php?option=com_jlms&view=quiz&id='.(int)$item['enrollment_id'].'&lesson_id='.(int)$selected->id) ?>"><?= Text::_('COM_JLMS_QUIZ_OPEN') ?></a>
  <?php else : ?>
  <form id="jlms-complete-form" method="post" action="<?= Route::_('index.php?option=com_jlms') ?>">
   <input type="hidden" name="task" value="lesson.complete">
   <input type="hidden" name="jform[enrollment_id]" value="<?= (int) $item['enrollment_id'] ?>">
   <input type="hidden" name="jform[lesson_id]" value="<?= (int) $selected->id ?>">
   <input type="hidden" name="jform[idempotency_key]" value="<?= $this->escape($this->mutationKey) ?>">
   <?= HTMLHelper::_('form.token') ?><button class="btn btn-primary" type="submit"><?= Text::_('COM_JLMS_MARK_COMPLETE') ?></button>
  </form>
  <?php endif; ?>
 <div id="jlms-material-groups">
 <?php foreach (['course' => 'jlms-classroom-course-materials', 'lesson' => 'jlms-lesson-resources'] as $scope => $region) : $files = $this->materials[$scope]; ?>
 <section id="<?= $region ?>" class="jlms-material-list"><h3><?= Text::_($scope === 'course' ? 'COM_JLMS_COURSE_MATERIALS' : 'COM_JLMS_LESSON_MATERIALS') ?></h3>
 <?php foreach ($files['items'] as $file) : ?>
  <article id="jlms-material-<?= (int) $file->id ?>" class="jlms-material">
   <h4 class="jlms-material__title"><?= $this->escape($file->title) ?></h4><p><?= $this->escape($file->description ?? '') ?></p>
   <p class="jlms-material__meta"><?= $this->escape($file->mime_type) ?> · <?= (int) $file->byte_size ?> B</p>
   <a class="jlms-material__action" href="<?= Route::_('index.php?option=com_jlms&task=attachment.download&format=raw&id=' . (int) $file->id . '&enrollment_id=' . (int) $item['enrollment_id']) ?>"><?= Text::_('COM_JLMS_DOWNLOAD') ?></a>
  </article>
 <?php endforeach; ?>
 <?php if (!$files['items']) : ?><p><?= Text::_('COM_JLMS_NO_MATERIALS') ?></p><?php endif; ?>
 <?php $materialsBase = 'index.php?option=com_jlms&view=classroom&id=' . (int) $item['enrollment_id'] . '&lesson_id=' . (int) $selected->id . '&' . $scope . '_material_page='; ?>
 <nav aria-label="<?= Text::_('COM_JLMS_PAGINATION') ?>">
 <?php if ($files['page'] > 0) : ?><a href="<?= Route::_($materialsBase . ($files['page'] - 1)) ?>"><?= Text::_('JPREV') ?></a><?php endif; ?>
 <?php if (($files['page'] + 1) * 20 < $files['total']) : ?><a href="<?= Route::_($materialsBase . ($files['page'] + 1)) ?>"><?= Text::_('JNEXT') ?></a><?php endif; ?>
 </nav></section>
 <?php endforeach; ?></div>
 </article></div>
 <a id="jlms-classroom-back" href="<?= Route::_('index.php?option=com_jlms&view=course&id=' . (int) $item['course_id']) ?>"><?= Text::_('COM_JLMS_COURSE_PRESENTATION') ?></a>
</section>
