<?php

declare(strict_types=1);

\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
$course = $this->item['course'];
$presentation = $this->item['presentation'] ?? [];
$fieldOutput = [];
foreach ([1, 2, 3] as $position) {
    $fieldOutput[$position] = \Joomla\Component\Fields\Administrator\Helper\FieldsHelper::render('com_jlms.course', 'fields.render', [
        'item' => $course, 'context' => 'com_jlms.course', 'fields' => $this->item['fields'][$position] ?? [],
    ]);
}
?>
<article id="jlms-course" class="jlms-course" data-course-id="<?= (int) $course->id ?>">
 <header id="jlms-course-header"><h1 id="jlms-course-title"><?= $this->escape($course->title) ?></h1>
  <a id="jlms-course-category" href="<?= Route::_('index.php?option=com_jlms&view=category&id=' . (int) $course->catid) ?>"><?= $this->escape($course->category_title) ?></a>
 </header>
 <?php if (trim($fieldOutput[1]) !== '') : ?><div id="jlms-course-fields-after-title" class="jlms-course-fields"><?= $fieldOutput[1] /* Prepared and rendered by native com_fields layouts. */ ?></div><?php endif; ?>
 <?php if (!empty($presentation['image'])) : ?>
 <div id="jlms-course-cover" class="jlms-course-cover"><img src="<?= $this->escape(\Joomla\CMS\Uri\Uri::root() . implode('/', array_map('rawurlencode', explode('/', $presentation['image'])))) ?>" alt="<?= $this->escape($presentation['image_alt']) ?>" loading="lazy" decoding="async"></div>
 <?php endif; ?>
 <?php if (trim($fieldOutput[2]) !== '') : ?><div id="jlms-course-fields-before-content" class="jlms-course-fields"><?= $fieldOutput[2] ?></div><?php endif; ?>
 <section id="jlms-course-summary"><h2><?= Text::_('COM_JLMS_COURSE_PRESENTATION') ?></h2><p><?= nl2br($this->escape((string) $course->summary)) ?></p></section>
 <?php if (!empty($presentation['description'])) : ?>
 <section id="jlms-course-description" class="jlms-course-description"><h2><?= Text::_('COM_JLMS_COURSE_DESCRIPTION') ?></h2><div><?= $presentation['description'] /* Strictly allowlisted by CoursePresentation::fromSnapshot. */ ?></div></section>
 <?php endif; ?>
 <?php foreach (['objectives' => 'COM_JLMS_COURSE_OBJECTIVES', 'audience' => 'COM_JLMS_COURSE_AUDIENCE', 'prerequisite_text' => 'COM_JLMS_COURSE_PREREQUISITE_TEXT'] as $field => $label) : ?>
 <?php if (!empty($presentation[$field])) : ?>
 <section id="jlms-course-<?= str_replace('_', '-', $field) ?>" class="jlms-course-presentation-section"><h2><?= Text::_($label) ?></h2><p><?= nl2br($this->escape($presentation[$field])) ?></p></section>
 <?php endif; ?>
 <?php endforeach; ?>
 <?php if (!empty($presentation['difficulty']) || !empty($presentation['estimated_minutes'])) : ?>
 <section id="jlms-course-facts"><h2><?= Text::_('COM_JLMS_COURSE_FACTS') ?></h2><dl>
 <?php if (!empty($presentation['difficulty'])) : ?><dt><?= Text::_('COM_JLMS_COURSE_DIFFICULTY') ?></dt><dd id="jlms-course-difficulty"><?= Text::_(['beginner' => 'COM_JLMS_COURSE_BEGINNER', 'intermediate' => 'COM_JLMS_COURSE_INTERMEDIATE', 'advanced' => 'COM_JLMS_COURSE_ADVANCED'][$presentation['difficulty']]) ?></dd><?php endif; ?>
 <?php if (!empty($presentation['estimated_minutes'])) : ?><dt><?= Text::_('COM_JLMS_COURSE_ESTIMATED_MINUTES') ?></dt><dd id="jlms-course-estimated-duration"><?= Text::sprintf('COM_JLMS_COURSE_DURATION_VALUE', (int) $presentation['estimated_minutes']) ?></dd><?php endif; ?>
 </dl></section>
 <?php endif; ?>
 <?php if (trim($fieldOutput[3]) !== '') : ?><div id="jlms-course-fields-after-content" class="jlms-course-fields"><?= $fieldOutput[3] ?></div><?php endif; ?>
 <?php if (!empty($this->item['teachers'])) : ?><section id="jlms-course-teachers"><h2><?= Text::_('COM_JLMS_TEACHERS') ?></h2>
 <?php foreach ($this->item['teachers'] as $teacher) : ?><article id="jlms-course-teacher-<?= (int) $teacher['id'] ?>" class="jlms-course-teacher"><h3><a href="<?= Route::_('index.php?option=com_jlms&view=teacher&id=' . (int) $teacher['id']) ?>"><?= $this->escape($teacher['title']) ?></a></h3>
 <?php if ($teacher['image'] !== '') : ?><img src="<?= $this->escape(\Joomla\CMS\Uri\Uri::root() . implode('/',array_map('rawurlencode',explode('/',$teacher['image'])))) ?>" alt="<?= $this->escape($teacher['image_alt']) ?>" loading="lazy" decoding="async"><?php endif; ?>
 <p><?= nl2br($this->escape($teacher['bio'])) ?></p><?php if ($teacher['website'] !== '') : ?><a href="<?= $this->escape($teacher['website']) ?>" rel="noopener noreferrer"><?= Text::_('COM_JLMS_TEACHER_WEBSITE') ?></a><?php endif; ?></article><?php endforeach; ?></section><?php endif; ?>
 <?php if (!empty($this->item['calendar'])) : $calendar=$this->item['calendar']; ?>
 <section id="jlms-course-calendar"><h2><?= Text::_('COM_JLMS_CALENDAR') ?></h2><p><?= Text::_('COM_JLMS_CALENDAR_ZONE') ?>: <?= $this->escape($calendar['timezone']) ?></p><dl>
 <?php foreach (['publish_up'=>'COM_JLMS_CALENDAR_PUBLISH_UP','publish_down'=>'COM_JLMS_CALENDAR_PUBLISH_DOWN','starts_at'=>'COM_JLMS_CALENDAR_START','ends_at'=>'COM_JLMS_CALENDAR_END'] as $key=>$label) : if ($calendar[$key]===null) { continue; } ?>
 <dt><?= Text::_($label) ?></dt><dd><?= $this->escape(\Jlms\Component\Jlms\Administrator\Application\CourseSchedule::display($calendar[$key],$calendar['timezone'],true)) ?></dd><?php endforeach; ?></dl>
 <p><?= Text::_('COM_JLMS_CALENDAR_POLICY') ?></p></section><?php endif; ?>
 <aside id="jlms-course-access" class="jlms-course-access"><p><?= Text::_($this->hasContentAccess ? 'COM_JLMS_CONTENT_ACCESS_ALLOWED' : 'COM_JLMS_CONTENT_ACCESS_REQUIRED') ?></p><?php if ($this->isGuest) : ?>
  <a id="jlms-course-login" href="<?= Route::_('index.php?option=com_users&view=login&return=' . base64_encode('index.php?option=com_jlms&view=course&id=' . (int) $course->id)) ?>"><?= Text::_('COM_JLMS_LOGIN_TO_LEARN') ?></a>
 <?php elseif ($this->hasContentAccess && $this->canStartWindow && $course->enrollment_mode === 'auto') : ?>
  <form id="jlms-start-form" method="post" action="<?= Route::_('index.php?option=com_jlms') ?>">
   <input type="hidden" name="task" value="enrollment.start">
   <input type="hidden" name="jform[course_id]" value="<?= (int) $course->id ?>">
   <input type="hidden" name="jform[idempotency_key]" value="<?= $this->escape($this->mutationKey) ?>">
   <label class="jlms-course-consent"><input type="checkbox" name="jform[course_announcement_consent]" value="1"> <?= Text::_('COM_JLMS_MAIL_CONSENT_ENROLLMENT_CHECKBOX') ?></label>
   <?= HTMLHelper::_('form.token') ?><button type="submit" class="btn btn-primary"><?= Text::_('COM_JLMS_START_RESUME') ?></button>
  </form>
 <?php elseif ($this->hasContentAccess && !$this->canStartWindow) : ?><p id="jlms-course-window-closed"><?= Text::_('COM_JLMS_CALENDAR_CLOSED') ?></p>
 <?php elseif ($this->hasContentAccess) : ?><p><?= Text::_('COM_JLMS_ENROLLMENT_MODE_PENDING') ?></p><?php endif; ?></aside>
 <section id="jlms-course-outline"><h2><?= Text::_('COM_JLMS_CURRICULUM') ?></h2>
 <?php foreach ($this->item['modules'] as $module) : ?>
  <section id="jlms-module-<?= (int) $module->id ?>" class="jlms-module"><h3 class="jlms-module__title"><?= $this->escape($module->title) ?></h3>
   <ol class="jlms-module__lessons"><?php foreach ($this->item['lessons'][(int) $module->id] ?? [] as $lesson) : ?>
    <li id="jlms-lesson-<?= (int) $lesson->id ?>" class="jlms-lesson"><span class="jlms-lesson__title"><?= $this->escape($lesson->title) ?></span> <span class="jlms-lesson__requirement"><?= Text::_($lesson->required ? 'COM_JLMS_REQUIRED' : 'COM_JLMS_OPTIONAL') ?></span><?php if (!empty($this->item['public_previews']) && (int) $lesson->preview === 1) : ?> <a class="jlms-lesson__public-preview" href="<?= Route::_('index.php?option=com_jlms&view=lessonpreview&course_id=' . (int) $course->id . '&lesson_id=' . (int) $lesson->id) ?>"><?= Text::_('COM_JLMS_OPEN_PUBLIC_PREVIEW') ?></a><?php endif; ?></li>
   <?php endforeach; ?></ol>
  </section>
 <?php endforeach; ?></section>
<?php if (!empty($this->item['public_materials']['items'])) : ?><section id="jlms-course-materials"><h2><?= Text::_('COM_JLMS_COURSE_MATERIALS') ?></h2>
 <ul class="jlms-material-list"><?php foreach ($this->item['public_materials']['items'] as $material) : ?><li id="jlms-material-<?= (int) $material->id ?>" class="jlms-material">
  <span class="jlms-material__title"><?= $this->escape($material->title) ?></span>
  <?php if (trim((string) $material->description) !== '') : ?><p class="jlms-material__meta"><?= nl2br($this->escape($material->description)) ?></p><?php endif; ?>
  <span class="jlms-material__meta"><?= $this->escape($material->original_name) ?> · <?= $this->escape($material->mime_type) ?> · <?= number_format((int) $material->byte_size / 1048576, 2) ?> MiB</span>
  <a class="jlms-material__action" href="<?= Route::_('index.php?option=com_jlms&task=attachment.publicpreview&format=raw&course_id=' . (int) $course->id . '&id=' . (int) $material->id) ?>"><?= Text::_('COM_JLMS_MATERIAL_PREVIEW_DOWNLOAD') ?></a>
 </li><?php endforeach; ?></ul>
 <?php if ($this->item['public_materials']['page'] > 0 || ($this->item['public_materials']['page'] + 1) * $this->item['public_materials']['limit'] < $this->item['public_materials']['total']) : ?><nav aria-label="<?= Text::_('COM_JLMS_PAGINATION') ?>">
  <?php if ($this->item['public_materials']['page'] > 0) : ?><a href="<?= Route::_('index.php?option=com_jlms&view=course&id=' . (int) $course->id . '&course_material_page=' . ($this->item['public_materials']['page'] - 1)) ?>"><?= Text::_('JPREV') ?></a><?php endif; ?>
  <?php if (($this->item['public_materials']['page'] + 1) * $this->item['public_materials']['limit'] < $this->item['public_materials']['total']) : ?><a href="<?= Route::_('index.php?option=com_jlms&view=course&id=' . (int) $course->id . '&course_material_page=' . ($this->item['public_materials']['page'] + 1)) ?>"><?= Text::_('JNEXT') ?></a><?php endif; ?>
 </nav><?php endif; ?></section><?php endif; ?>
 <a id="jlms-course-back" href="<?= Route::_('index.php?option=com_jlms&view=courses') ?>"><?= Text::_('COM_JLMS_ALL_COURSES') ?></a>
</article>
