<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('behavior.formvalidator');
$data=$this->item['data'];$draft=$this->item['version']->status==='draft';
?>
<section id="jlms-quiz-editor"><h1><?= Text::_('COM_JLMS_QUIZ_EDITOR') ?></h1>
 <p class="alert alert-info"><?= Text::_('COM_JLMS_QUIZ_AUTHORING_NOTICE') ?></p>
 <p><a href="<?= Route::_('index.php?option=com_jlms&view=questionbank') ?>" target="_blank" rel="noopener"><?= Text::_('COM_JLMS_QUESTION_BANK') ?></a> · <a href="<?= Route::_('index.php?option=com_jlms&view=curriculum&id='.(int)$data['course_id'].'&version_id='.(int)$data['version_id']) ?>"><?= Text::_('COM_JLMS_CURRICULUM') ?></a></p>
 <?php if($draft): ?>
 <form id="jlms-quiz-form" method="post" class="form-validate" action="<?= Route::_('index.php?option=com_jlms') ?>"><?= $this->form->renderFieldset('details') ?><input type="hidden" name="task" value="quiz.save"><?= HTMLHelper::_('form.token') ?><button class="btn btn-primary" type="submit"><?= Text::_('JSAVE') ?></button></form>
 <?php if($data['lesson_id']): ?><form id="jlms-quiz-delete-form" method="post" action="<?= Route::_('index.php?option=com_jlms') ?>">
 <?php foreach(['course_id','version_id','lesson_id','expected_version_revision']as$key): ?><input type="hidden" name="jform[<?= $key ?>]" value="<?= (int)$data[$key] ?>"><?php endforeach; ?><input type="hidden" name="task" value="quiz.delete">
 <label><input type="checkbox" name="jform[confirmation]" value="1" required> <?= Text::_('COM_JLMS_CONFIRM_DELETE_LESSON') ?></label><?= HTMLHelper::_('form.token') ?><button class="btn btn-outline-danger" type="submit"><?= Text::_('JACTION_DELETE') ?></button></form><?php endif; ?>
 <?php else: ?><p><?= Text::_('COM_JLMS_VERSION_IMMUTABLE') ?></p><?php endif; ?>
</section>
