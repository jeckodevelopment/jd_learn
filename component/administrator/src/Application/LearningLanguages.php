<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;

final class LearningLanguages
{
    public static function available(User $actor, string $uiLanguage): array
    {
        if (!Multilanguage::isEnabled()) { return [$uiLanguage => true]; }
        $installed = LanguageHelper::getInstalledLanguages(0);
        $levels = array_map('intval', $actor->getAuthorisedViewLevels());
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $q = $db->createQuery()->select('lang_code')->from('#__languages')->where('published = 1')
            ->whereIn('access', array_values(array_unique(array_merge([0], $levels))));
        $languages = [];
        foreach ($db->setQuery($q)->loadColumn() as $tag) {
            if (isset($installed[$tag])) { $languages[$tag] = true; }
        }
        return $languages;
    }
}
