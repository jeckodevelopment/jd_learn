<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$r = $this->result; $f = $r['filters'];
?>
<section id="jlms-admin-certificates">
 <p><?= Text::_('COM_JLMS_CERTIFICATE_LIST_INTRO') ?></p>
 <form method="get" action="<?= Route::_('index.php') ?>">
  <input type="hidden" name="option" value="com_jlms"><input type="hidden" name="view" value="certificates">
  <label for="jlms-certificates-q"><?= Text::_('COM_JLMS_CERTIFICATE_SEARCH') ?></label>
  <input id="jlms-certificates-q" type="search" name="q" maxlength="120" value="<?= $this->escape($f['q']) ?>">
  <label for="jlms-certificates-status"><?= Text::_('COM_JLMS_CERTIFICATE_FILTER_STATUS') ?></label>
  <select id="jlms-certificates-status" name="status"><option value=""><?= Text::_('JALL') ?></option>
   <?php foreach (['ready' => 'COM_JLMS_CERTIFICATE_STATE_READY', 'pending_render' => 'COM_JLMS_CERTIFICATE_STATE_PENDING', 'revoked' => 'COM_JLMS_CERTIFICATE_STATE_REVOKED'] as $value => $label) : ?>
    <option value="<?= $value ?>"<?= $f['status'] === $value ? ' selected' : '' ?>><?= Text::_($label) ?></option>
   <?php endforeach; ?>
  </select><button class="btn btn-primary" type="submit"><?= Text::_('JSEARCH_FILTER_SUBMIT') ?></button>
 </form>
 <?php if (!$r['items']) : ?><p class="alert alert-info"><?= Text::_('COM_JLMS_CERTIFICATE_NO_RESULTS') ?></p><?php else : ?>
 <table class="table itemList"><caption class="visually-hidden"><?= Text::_('COM_JLMS_CERTIFICATES') ?></caption>
  <thead><tr><th scope="col"><?= Text::_('COM_JLMS_CERTIFICATE_STUDENT') ?></th><th scope="col"><?= Text::_('COM_JLMS_CERTIFICATE_COURSE') ?></th><th scope="col"><?= Text::_('COM_JLMS_CERTIFICATE_CODE') ?></th><th scope="col"><?= Text::_('JSTATUS') ?></th><th scope="col"><?= Text::_('COM_JLMS_CERTIFICATE_ISSUED') ?></th></tr></thead>
  <tbody><?php foreach ($r['items'] as $item) : ?><tr>
   <th scope="row"><a href="<?= Route::_('index.php?option=com_jlms&view=certificate&id=' . (int) $item['id']) ?>"><?= $this->escape($item['student_name']) ?></a></th>
   <td><?= $this->escape($item['course_title']) ?></td><td><?= $this->escape($item['serial']) ?></td>
   <td><?= Text::_($item['status'] === 'revoked' ? 'COM_JLMS_CERTIFICATE_STATE_REVOKED' : ($item['status'] === 'ready' ? 'COM_JLMS_CERTIFICATE_STATE_READY' : 'COM_JLMS_CERTIFICATE_STATE_PENDING')) ?></td>
   <td><?= $this->escape($item['issued_at']) ?></td>
  </tr><?php endforeach; ?></tbody>
 </table><?php endif; ?>
 <p><?= Text::sprintf('COM_JLMS_CERTIFICATE_RESULT_COUNT', $r['total']) ?></p>
 <nav aria-label="<?= Text::_('JGLOBAL_PAGINATION') ?>">
  <?php if ($r['offset'] > 0) : ?><a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_jlms&view=certificates&q=' . rawurlencode($f['q']) . '&status=' . rawurlencode($f['status']) . '&offset=' . max(0, $r['offset'] - $r['limit'])) ?>"><?= Text::_('JPREV') ?></a><?php endif; ?>
  <?php if ($r['offset'] + $r['limit'] < $r['total']) : ?><a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_jlms&view=certificates&q=' . rawurlencode($f['q']) . '&status=' . rawurlencode($f['status']) . '&offset=' . ($r['offset'] + $r['limit'])) ?>"><?= Text::_('JNEXT') ?></a><?php endif; ?>
 </nav>
</section>
