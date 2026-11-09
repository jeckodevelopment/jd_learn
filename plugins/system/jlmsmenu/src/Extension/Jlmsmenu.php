<?php

declare(strict_types=1);

namespace Jlms\Plugin\System\Jlmsmenu\Extension;

use Joomla\CMS\Event\Menu\PreprocessMenuItemsEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

final class Jlmsmenu extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['onPreprocessMenuItems' => 'filterAdministratorMenu'];
    }

    public function filterAdministratorMenu(PreprocessMenuItemsEvent $event): void
    {
        if ($event->getContext() !== 'com_menus.administrator.module') {
            return;
        }

        $user = $this->getApplication()->getIdentity();
        $items = $event->getItems();
        $filtered = [];

        foreach ($items as $item) {
            $link = (string) ($item->link ?? '');
            $query = [];
            parse_str((string) parse_url(html_entity_decode($link, ENT_QUOTES | ENT_HTML5), PHP_URL_QUERY), $query);

            if (($query['option'] ?? '') !== 'com_jlms') {
                if (!$this->canManageNativeDestination($user, $query)) {
                    continue;
                }
                $filtered[] = $item;
                continue;
            }

            $view = (string) ($query['view'] ?? '');
            $allowed = match ($view) {
                'questionbank' => $user->authorise('jlms.quiz.manage', 'com_jlms'),
                'pathways' => $user->authorise('core.manage', 'com_jlms')
                    && $user->authorise('jlms.pathways.manage', 'com_jlms'),
                default => $user->authorise('core.manage', 'com_jlms'),
            };

            if ($allowed) {
                $filtered[] = $item;
            }
        }

        $event->updateItems($filtered);
    }

    private function canManageNativeDestination(object $user, array $query): bool
    {
        return match ($query['option'] ?? '') {
            'com_categories' => !str_starts_with((string) ($query['extension'] ?? ''), 'com_jlms.')
                || ($user->authorise('core.manage', 'com_categories')
                    && $user->authorise('core.manage', (string) $query['extension'])),
            'com_fields' => !str_starts_with((string) ($query['context'] ?? ''), 'com_jlms.')
                || ($user->authorise('core.manage', 'com_fields')
                    && $user->authorise('core.manage', (string) strtok((string) $query['context'], '.'))),
            'com_plugins' => (($query['filter']['folder'] ?? '') !== 'jlmsvideo'
                || $user->authorise('core.manage', 'com_plugins')),
            default => true,
        };
    }
}
