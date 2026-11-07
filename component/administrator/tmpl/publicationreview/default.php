<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$item = $this->item; $course = $item['course']; $version = $item['version'];
$back = Route::link('administrator', 'index.php?option=com_jlms&view=curriculum&id=' . (int)$course->id . '&version_id=' . (int)$version->id);
$labels = ['modules'=>'COM_JLMS_PUBLICATION_REVIEW_MODULES', 'lessons'=>'COM_JLMS_PUBLICATION_REVIEW_LESSONS', 'quizzes'=>'COM_JLMS_PUBLICATION_REVIEW_QUIZZES', 'materials'=>'COM_JLMS_MATERIALS'];
$changeLabels = ['added'=>'COM_JLMS_PUBLICATION_REVIEW_ADDED', 'changed'=>'COM_JLMS_PUBLICATION_REVIEW_CHANGED', 'removed'=>'COM_JLMS_PUBLICATION_REVIEW_REMOVED'];
?>
<section id="jlms-publication-review">
 <h1><?= $this->escape($course->title) ?></h1>
 <h2><?= Text::_('COM_JLMS_PUBLICATION_REVIEW_HEADING') ?></h2>
 <p><?= Text::sprintf('COM_JLMS_PUBLICATION_REVIEW_VERSIONS', (int)$item['source']->version_no, (int)$version->version_no) ?></p>
 <p class="alert alert-info"><?= Text::_('COM_JLMS_PUBLICATION_REVIEW_EFFECT') ?></p>
 <div class="table-responsive"><table class="table" id="jlms-publication-changes">
  <caption><?= Text::_('COM_JLMS_PUBLICATION_REVIEW_CHANGES') ?></caption>
  <thead><tr><th scope="col"><?= Text::_('COM_JLMS_PUBLICATION_REVIEW_CONTENT') ?></th><?php foreach ($changeLabels as $label) : ?><th scope="col"><?= Text::_($label) ?></th><?php endforeach; ?></tr></thead>
  <tbody><?php foreach ($labels as $kind=>$label) : ?><tr><th scope="row"><?= Text::_($label) ?></th><?php foreach ($changeLabels as $change=>$unused) : ?><td data-kind="<?= $kind ?>" data-change="<?= $change ?>"><?= (int)$item['changes'][$kind][$change] ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
 </table></div>
 <p><?= Text::_('COM_JLMS_PUBLICATION_REVIEW_RULES') ?>: <strong><?= Text::_($item['rules_changed'] ? 'JYES' : 'JNO') ?></strong></p>
 <?php foreach ($labels as $kind=>$label) : if (!$item['changes'][$kind]['details']) { continue; } ?>
 <details><summary><?= Text::_($label) ?> — <?= Text::_('COM_JLMS_PUBLICATION_REVIEW_DETAILS') ?></summary>
  <ul><?php foreach ($item['changes'][$kind]['details'] as $entry) : ?><li><?= Text::_($changeLabels[$entry['kind']]) ?>: <?= $this->escape($entry['title']) ?></li><?php endforeach; ?></ul>
  <?php if (array_sum(array_intersect_key($item['changes'][$kind],$changeLabels)) > 100) : ?><p><?= Text::_('COM_JLMS_PUBLICATION_REVIEW_LIMIT') ?></p><?php endif; ?>
 </details>
 <?php endforeach; ?>
 <h2 class="h4"><?= Text::_('COM_JLMS_PUBLICATION_REVIEW_ENROLLMENTS') ?></h2>
 <p><?= Text::_('COM_JLMS_PUBLICATION_REVIEW_COUNTS_NOTICE') ?></p>
 <?php if ($item['enrollments']) : ?><ul><?php foreach ($item['enrollments'] as $row) : ?><li><?= Text::sprintf('COM_JLMS_PUBLICATION_REVIEW_ASSIGNED', (int)$row['version_no'], (int)$row['total']) ?></li><?php endforeach; ?></ul>
 <?php else : ?><p><?= Text::_('COM_JLMS_PUBLICATION_REVIEW_NO_ENROLLMENTS') ?></p><?php endif; ?>
 <p><?= Text::_('COM_JLMS_PUBLICATION_REVIEW_VALIDATION') ?></p>
 <form method="post" id="jlms-publication-confirm-form" action="<?= Route::link('administrator','index.php?option=com_jlms') ?>">
  <?php foreach (['course_id'=>$course->id,'version_id'=>$version->id,'revision'=>$course->revision,'expected_version_revision'=>$version->revision,'transition_id'=>$item['transition_id'],'review_ticket'=>$item['ticket']] as $name=>$value) : ?>
  <input type="hidden" name="jform[<?= $name ?>]" value="<?= $this->escape((string)$value) ?>">
  <?php endforeach; ?>
  <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="jlms-publication-confirm" name="jform[publication_confirmation]" value="1" required><label class="form-check-label" for="jlms-publication-confirm"><?= Text::_('COM_JLMS_PUBLICATION_REVIEW_CONFIRM') ?></label></div>
  <input type="hidden" name="task" value="curriculum.transition"><?= HTMLHelper::_('form.token') ?>
  <button class="btn btn-primary" type="submit"><?= Text::_('COM_JLMS_PUBLISH_UPDATE') ?></button>
  <a class="btn btn-secondary" href="<?= $back ?>"><?= Text::_('JCANCEL') ?></a>
 </form>
</section>
