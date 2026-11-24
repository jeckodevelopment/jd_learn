<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$course = $this->item['course'];
$lesson = $this->item['lesson'];
$materials = $this->item['materials']['items'] ?? [];
?>
<article id="jlms-public-lesson-preview" class="jlms-course" data-course-id="<?= (int) $course->id ?>">
 <p class="alert alert-info"><?= Text::_('COM_JLMS_PUBLIC_PREVIEW_NOTICE') ?></p>
 <h1><?= $this->escape($lesson->title) ?></h1>
 <div class="jlms-lesson__body"><?= $lesson->body /* Filtered when saved by the authoring service. */ ?></div>
 <?php if ($materials !== []) : ?>
 <section class="jlms-public-preview-materials"><h2><?= Text::_('COM_JLMS_PUBLIC_PREVIEW_MATERIALS') ?></h2>
  <ul><?php foreach ($materials as $material) :
        ?><li><a href="<?=
             Route::_('index.php?option=com_jlms&task=attachment.publiclessonpreview&format=raw&course_id='
            . (int) $course->id . '&lesson_id=' . (int) $lesson->id . '&id=' . (int) $material->id) ?>"><?= $this
            ->escape($material->title) ?></a><?php if ($material->description !== '') :
    ?> — <?= $this->escape($material->description) ?><?php
            endif; ?></li><?php
      endforeach; ?></ul>
 </section>
 <?php endif; ?>
 <p><a href="<?= Route::_('index.php?option=com_jlms&view=course&id=' . (int) $course
     ->id) ?>"><?= Text::_('COM_JLMS_BACK_TO_COURSE') ?></a></p>
</article>
