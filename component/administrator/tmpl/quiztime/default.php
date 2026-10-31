<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('behavior.formvalidator');$i=$this->item;
$base='index.php?option=com_jlms&view=quiztime&id='.(int)$i['course_id'].'&lesson_id='.(int)$i['lesson_id'];
?>
<section id="jlms-quiz-time-editor"><h1><?= Text::_('COM_JLMS_QUIZ_TIME_TITLE') ?></h1>
 <h2><?= $this->escape($i['quiz']->course_title) ?> · <?= $this->escape($i['quiz']->lesson_title) ?></h2>
 <p class="alert alert-info" id="jlms-quiz-time-policy"><?= Text::_('COM_JLMS_QUIZ_TIME_POLICY') ?></p>
 <p><?= Text::_('COM_JLMS_QUIZ_TIME_BASE') ?>: <?= (int)$i['quiz']->time_limit_seconds ?> s</p>
 <table id="jlms-quiz-time-enrollments" class="table"><caption><?= Text::_('COM_JLMS_QUIZ_TIME_SELECT') ?></caption><thead><tr><th><?= Text::_('JGLOBAL_USERNAME') ?></th><th>ID</th><th><?= Text::_('JSTATUS') ?></th></tr></thead><tbody>
 <?php foreach($i['candidates']as$e): ?><tr><td><a href="<?= Route::_($base.'&enrollment_id='.(int)$e->id) ?>"><?= $this->escape($e->learner_name) ?></a></td><td><?= (int)$e->id ?></td><td><?= $this->escape($e->status) ?></td></tr><?php endforeach; ?>
 </tbody></table>
 <nav><?php if($i['more']): $last=end($i['candidates']); ?><a href="<?= Route::_($base.'&after='.(int)$last->id) ?>"><?= Text::_('JNEXT') ?></a><?php endif; ?><a href="<?= Route::_($base) ?>"><?= Text::_('COM_JLMS_QUIZ_TIME_FIRST') ?></a></nav>
 <?php if($i['enrollment']): ?>
 <p><a id="jlms-quiz-review-link" href="<?= Route::_('index.php?option=com_jlms&view=quizreview&id='.(int)$i['course_id'].'&lesson_id='.(int)$i['lesson_id'].'&enrollment_id='.(int)$i['enrollment']->id) ?>"><?= Text::_('COM_JLMS_QUIZ_REVIEW_TITLE') ?></a></p>
 <h2><?= $this->escape($i['enrollment']->learner_name) ?> · #<?= (int)$i['enrollment']->id ?></h2>
 <?php if($i['active']): ?><p id="jlms-quiz-time-active" class="alert alert-warning"><?= Text::_('COM_JLMS_QUIZ_TIME_ACTIVE') ?> #<?= (int)$i['active']->id ?> · <?= (int)$i['active']->time_seconds ?> s · <?= $this->escape($i['active']->deadline_at??Text::_('COM_JLMS_QUIZ_UNLIMITED')) ?> UTC</p><?php endif; ?>
 <form id="jlms-quiz-time-form" method="post" class="form-validate" action="<?= Route::_('index.php?option=com_jlms') ?>">
 <?= $this->form->renderFieldset('details') ?><input type="hidden" name="task" value="quiztime.save"><?= HTMLHelper::_('form.token') ?><button type="submit" class="btn btn-primary"><?= Text::_('JSAVE') ?></button></form>
 <table id="jlms-quiz-time-history" class="table"><caption><?= Text::_('COM_JLMS_QUIZ_TIME_HISTORY') ?></caption><thead><tr><th><?= Text::_('COM_JLMS_QUIZ_TIME_REVISION') ?></th><th><?= Text::_('COM_JLMS_QUIZ_TIME_MINUTES') ?></th><th><?= Text::_('COM_JLMS_QUIZ_TIME_REASON_LABEL') ?></th><th><?= Text::_('JAUTHOR') ?></th><th><?= Text::_('JDATE') ?></th></tr></thead><tbody>
 <?php foreach($i['history']as$h): ?><tr><td><?= (int)$h->revision_no ?></td><td><?= intdiv((int)$h->extra_seconds,60) ?></td><td><?= $this->escape($h->reason) ?></td><td><?= (int)$h->actor_user_id ?></td><td><?= $this->escape($h->created_at) ?> UTC</td></tr><?php endforeach; ?>
 </tbody></table><?php endif; ?>
</section>
