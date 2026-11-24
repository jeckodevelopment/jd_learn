<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
?>
<section id="jlms-course-announcement-preferences"
    class="jlms-dashboard-group"
    aria-labelledby="jlms-course-announcement-title">
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
    <label><input type="checkbox"
    name="consent"
    value="1" required> <?= Text::_('COM_JLMS_MAIL_CONSENT_CHECKBOX') ?></label>
    <button type="submit" class="btn btn-primary"><?= Text::_('COM_JLMS_MAIL_CONSENT_REQUEST') ?></button>
      <?= HTMLHelper::_('form.token') ?>
   </form>
  <?php endif; ?>
 </section>
