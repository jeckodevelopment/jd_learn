<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$mailLanguage = Factory::getApplication()->getLanguage()->getTag();
$mailCatalog = \Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateVariableCatalog::class;
$mailIds = \Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateInstaller::templateIds();
?>
<section id="jlms-mail-variable-guide">
 <p><?= $this->escape(Text::_('COM_JLMS_MAIL_VARIABLE_GUIDE_INTRO')) ?></p>
 <p class="alert alert-info"><?= $this->escape(Text::_('COM_JLMS_MAIL_VARIABLE_GUIDE_WARNING')) ?></p>
 <?php foreach ($mailIds as $mailId) : ?>
        <?php
        $titleKey = 'COM_' . 'JLMS_MAIL_' . strtoupper(str_replace(['com_jlms.', '.'], ['', '_'], $mailId)) . '_TITLE';
        $translatedTitle = Text::_($titleKey);
        $displayTitle = $translatedTitle === $titleKey ? $mailId : $translatedTitle;
        ?>
 <details class="card mb-2">
  <summary class="card-header"><strong><?= $this->escape($displayTitle) ?></strong><span class="text-muted small"> — <code><?= $this->escape($mailId) ?></code></span></summary>
  <div class="card-body table-responsive">
   <table class="table table-sm table-striped">
    <caption><?= $this->escape($displayTitle) ?> — <?= $this->escape($mailId) ?></caption>
    <thead><tr>
     <th scope="col"><?= $this->escape(Text::_('COM_JLMS_MAIL_VARIABLE_GUIDE_TAG')) ?></th>
     <th scope="col"><?= $this->escape(Text::_('COM_JLMS_MAIL_VARIABLE_GUIDE_TYPE')) ?></th>
     <th scope="col"><?= $this->escape(Text::_('COM_JLMS_MAIL_VARIABLE_GUIDE_DESCRIPTION')) ?></th>
    </tr></thead>
    <tbody>
        <?php foreach ($mailCatalog::forTemplate($mailId, $mailLanguage) as $name => $details) : ?>
     <tr>
      <td><code><?= $this->escape('{' . $name . '}') ?></code></td>
      <td><?= $this->escape($details['type']) ?></td>
      <td><?= $this->escape($details['description']) ?></td>
     </tr>
        <?php endforeach; ?>
    </tbody>
   </table>
  </div>
 </details>
 <?php endforeach; ?>
</section>
