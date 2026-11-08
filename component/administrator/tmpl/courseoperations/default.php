<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$i=$this->item; $p=$i['impact']; $labels=['admissions_close'=>'COM_JLMS_COURSE_ADMISSIONS_CLOSE','admissions_open'=>'COM_JLMS_COURSE_ADMISSIONS_OPEN','delivery_suspend'=>'COM_JLMS_COURSE_DELIVERY_SUSPEND','delivery_resume'=>'COM_JLMS_COURSE_DELIVERY_RESUME'];
HTMLHelper::_('behavior.formvalidator');
?>
<section id="jlms-course-operations"><h1><?= Text::_('COM_JLMS_COURSE_OPERATIONS_TITLE') ?></h1>
<h2><?= $this->escape($i['course']->title) ?></h2>
<nav aria-label="<?= Text::_('COM_JLMS_COURSE_OPERATIONS_TITLE') ?>">
<?php foreach($labels as $op=>$label): ?><a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_jlms&view=courseoperations&id='.(int)$i['course']->id.'&operation='.$op) ?>"><?= Text::_($label) ?></a> <?php endforeach; ?>
</nav>
<h3><?= Text::_($labels[$p['operation']]) ?></h3>
<p><?= Text::_('COM_JLMS_COURSE_OPERATIONS_IMPACT') ?>: <?= (int)$p['learners'] ?> · <?= Text::_('COM_JLMS_COURSE_OPERATIONS_OPEN_QUIZZES') ?>: <?= (int)$p['open_attempts'] ?></p>
<?php if($p['blocked']): ?><p class="alert alert-info"><?= Text::_('COM_JLMS_COURSE_OPERATIONS_ALREADY_SET') ?></p><?php else: ?>
<form method="post" class="form-validate" action="<?= Route::_('index.php?option=com_jlms') ?>">
<?= $this->form->renderFieldset('details') ?><input type="hidden" name="task" value="courseoperations.apply"><?= HTMLHelper::_('form.token') ?>
<button type="submit" class="btn btn-primary"><?= Text::_('COM_JLMS_COURSE_OPERATIONS_CONFIRM_BUTTON') ?></button></form><?php endif; ?>
<h3><?= Text::_('COM_JLMS_COURSE_OPERATIONS_HISTORY') ?></h3><div class="table-responsive"><table class="table"><thead><tr><th>#</th><th><?= Text::_('JSTATUS') ?></th><th><?= Text::_('COM_JLMS_COURSE_OPERATIONS_REASON') ?></th><th><?= Text::_('JAUTHOR') ?></th><th><?= Text::_('JDATE') ?> UTC</th></tr></thead><tbody>
<?php foreach($i['history'] as $h): ?><tr><td><?= (int)$h->revision_no ?></td><td><?= Text::_($labels[$h->operation]??$h->operation) ?></td><td><?= $this->escape($h->reason) ?></td><td><?= (int)$h->actor_user_id ?></td><td><?= $this->escape($h->created_at) ?></td></tr><?php endforeach; ?>
</tbody></table></div><a href="<?= Route::_('index.php?option=com_jlms&view=enrollments&id='.(int)$i['course']->id) ?>"><?= Text::_('COM_JLMS_ENROLLMENTS_TITLE') ?></a></section>
