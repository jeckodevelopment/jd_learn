<?php
declare(strict_types=1);
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** FE-06: optional local links, never a replacement for the Joomla site menu. */
$app = Factory::getApplication();
$active = $app->getMenu()->getActive();
$currentView = (string) ($currentView ?? '');
$personalViews = [
    'dashboard' => 'COM_JLMS_MY_TRAINING',
    'mycourses' => 'COM_JLMS_MY_COURSES',
    'mycertificates' => 'COM_JLMS_MY_CERTIFICATES',
    'preferences' => 'COM_JLMS_PREFERENCES',
];
$menuQuery = $active ? (array) $active->query : [];
$matchingMenu = ($menuQuery['option'] ?? '') === 'com_jlms'
    && ($menuQuery['view'] ?? '') === $currentView;
$enabled = isset($personalViews[$currentView])
    && $matchingMenu
    && (string) $active->getParams()->get('show_personal_navigation', '0') === '1';
if ($enabled) :
    ?>
<nav id="jlms-personal-navigation" class="jlms-personal-navigation" aria-label="<?= Text::_('COM_JLMS_PERSONAL_NAV_LABEL') ?>">
 <ul class="jlms-personal-navigation__list">
    <?php foreach ($personalViews as $view => $languageKey) : ?>
  <li class="jlms-personal-navigation__item">
        <?php if ($view === $currentView) : ?>
    <span class="jlms-personal-navigation__current" aria-current="page"><?= Text::_($languageKey) ?></span>
   <?php else : ?>
    <a class="jlms-personal-navigation__link" href="<?= Route::_('index.php?option=com_jlms&view=' . $view) ?>"><?= Text::_($languageKey) ?></a>
   <?php endif; ?>
  </li>
 <?php endforeach; ?>
 </ul>
</nav>
<?php endif; ?>
