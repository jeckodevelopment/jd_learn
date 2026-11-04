<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Jlms\Component\Jlms\Administrator\Application\CoursePresentation;
?>
<section id="jlms-teacher-profile">
 <h1><?= $this->escape($this->profile->title) ?></h1>
 <?php if ($this->canEditProfile) : ?><p><a href="<?= Route::_('index.php?option=com_jlms&task=teacher.edit&id=' . (int) $this->profile->id) ?>"><?= Text::_('COM_JLMS_TEACHER_EDIT_PROFILE') ?></a></p><?php endif; ?>
 <?php if ($this->getLayout() !== 'courses') : ?>
 <?php if ($this->profile->image) : $imageParams = json_decode($this->profile->params_json, true); ?><img src="<?= $this->escape(\Joomla\CMS\Uri\Uri::root(true) . '/' . $this->profile->image) ?>" alt="<?= $this->escape($imageParams['image_alt'] ?? '') ?>" loading="lazy"><?php endif; ?>
 <div id="jlms-teacher-presentation"><?= nl2br($this->escape($this->profile->bio)) ?></div>
 <dl id="jlms-teacher-fields">
 <?php foreach ($this->profile->fields as $field) : ?>
  <?php if ((string) $field->value === '') { continue; } ?>
  <dt><?= $this->escape($field->label) ?></dt><dd><?= CoursePresentation::html((string) $field->value) ?></dd>
 <?php endforeach; ?>
 </dl>
 <?php endif; ?>
 <h2><?= Text::_('COM_JLMS_TEACHER_COURSES') ?></h2>
 <ul id="jlms-teacher-courses">
 <?php foreach ($this->items as $course) : ?>
  <li><a href="<?= Route::_('index.php?option=com_jlms&view=course&id=' . (int) $course->id) ?>"><?= $this->escape($course->title) ?></a></li>
 <?php endforeach; ?>
 </ul>
 <?php if (!$this->items) : ?><p><?= Text::_('COM_JLMS_TEACHER_NO_COURSES') ?></p><?php endif; ?>
 <?= $this->pagination->getPagesLinks() ?>
</section>
