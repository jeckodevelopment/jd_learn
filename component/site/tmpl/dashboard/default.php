<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
?>
<section id="jlms-dashboard" class="jlms-dashboard">
 <header id="jlms-dashboard-header"><h1><?= Text::_('COM_JLMS_MY_COURSES') ?></h1><p><?= Text::_('COM_JLMS_DASHBOARD_INTRO') ?></p></header>
 <form id="jlms-dashboard-filters" method="get" action="<?= Route::_('index.php?option=com_jlms&view=dashboard') ?>">
  <?= $this->progressForm->renderField('progress') ?>
  <input type="hidden" name="option" value="com_jlms"><input type="hidden" name="view" value="dashboard">
  <button type="submit" class="btn btn-secondary"><?= Text::_('COM_JLMS_DASHBOARD_FILTER') ?></button>
 </form>
 <?php require __DIR__ . '/_courses.php'; ?>
 <?php if ($this->certificates) : ?>
 <section id="jlms-my-certificates" class="jlms-dashboard-group"><h2><?= Text::_('COM_JLMS_MY_CERTIFICATES') ?></h2>
  <div class="jlms-my-course-grid"><?php foreach ($this->certificates as $certificate) : ?>
   <article class="jlms-card jlms-my-course"><h3><?= $this->escape($certificate['title']) ?></h3>
    <p><?= Text::_('COM_JLMS_CERTIFICATE_ISSUED') ?>: <?= $this->escape($certificate['issued_at']) ?></p>
    <p><?= Text::_('COM_JLMS_CERTIFICATE_CODE') ?>: <?= $this->escape($certificate['serial']) ?></p>
    <?php if ($certificate['status'] === 'ready' || $certificate['status'] === 'pending_render') : ?>
     <a class="jlms-my-course__action" href="<?= Route::_('index.php?option=com_jlms&task=certificate.download&enrollment_id=' . (int) $certificate['enrollment_id']) ?>"><?= Text::_('COM_JLMS_CERTIFICATE_DOWNLOAD') ?></a>
    <?php endif; ?>
   </article>
  <?php endforeach; ?></div>
 </section><?php endif; ?>
 <section id="jlms-course-announcement-preferences" class="jlms-dashboard-group" aria-labelledby="jlms-course-announcement-title">
  <h2 id="jlms-course-announcement-title"><?= Text::_('COM_JLMS_MAIL_CONSENT_HEADING') ?></h2>
  <p><?= Text::_('COM_JLMS_MAIL_CONSENT_EXPLANATION') ?></p>
  <?php if ($this->announcementConsent === 'confirmed') : ?>
   <p id="jlms-mail-consent-state"><?= Text::_('COM_JLMS_MAIL_CONSENT_ACTIVE') ?></p>
   <form method="post" action="<?= Route::_('index.php?option=com_jlms&task=mailconsent.revoke', false) ?>">
    <button type="submit" class="btn btn-secondary"><?= Text::_('COM_JLMS_MAIL_CONSENT_REVOKE') ?></button>
    <?= HTMLHelper::_('form.token') ?>
   </form>
  <?php elseif ($this->announcementConsent === 'pending') : ?>
   <p id="jlms-mail-consent-state"><?= Text::_('COM_JLMS_MAIL_CONSENT_PENDING') ?></p>
  <?php else : ?>
   <form method="post" action="<?= Route::_('index.php?option=com_jlms&task=mailconsent.request', false) ?>">
    <label><input type="checkbox" name="consent" value="1" required> <?= Text::_('COM_JLMS_MAIL_CONSENT_CHECKBOX') ?></label>
    <button type="submit" class="btn btn-primary"><?= Text::_('COM_JLMS_MAIL_CONSENT_REQUEST') ?></button>
    <?= HTMLHelper::_('form.token') ?>
   </form>
  <?php endif; ?>
 </section>
 <nav id="jlms-dashboard-pagination" aria-label="<?= Text::_('COM_JLMS_PAGINATION') ?>"><?= $this->pagination->getPagesLinks() ?></nav>
 <a id="jlms-dashboard-catalog" href="<?= Route::_('index.php?option=com_jlms&view=courses') ?>"><?= Text::_('COM_JLMS_ALL_COURSES') ?></a>
</section>
