<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$course = $this->item['course'];
$lesson = $this->item['lesson'];
?>
<article id="jlms-public-lesson-preview" class="jlms-course" data-course-id="<?= (int) $course->id ?>">
 <p class="alert alert-info"><?= Text::_('COM_JLMS_PUBLIC_PREVIEW_NOTICE') ?></p>
 <h1><?= $this->escape($lesson->title) ?></h1>
 <div class="jlms-lesson__body"><?= $lesson->body /* Filtered when saved by the authoring service. */ ?></div>
 <p><a href="<?= Route::_('index.php?option=com_jlms&view=course&id=' . (int) $course->id) ?>"><?= Text::_('COM_JLMS_BACK_TO_COURSE') ?></a></p>
</article>
