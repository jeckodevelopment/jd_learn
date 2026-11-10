<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Course;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Jlms\Component\Jlms\Site\Helper\PublicMetadataHelper;

final class HtmlView extends BaseHtmlView
{
    public array $item;
    public bool $hasContentAccess = false;
    public bool $isGuest = true;
    public bool $canStartWindow = false;
    public string $mutationKey;

    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
        $this->canStartWindow=(bool) ($this->item['start_window_open'] ?? false);
        $actor = Factory::getApplication()->getIdentity();
        $this->isGuest = !(int) $actor->id;
        $this->mutationKey = bin2hex(random_bytes(16));
        if (!$this->isGuest) {
            try {
                $actor = Factory::getApplication()->bootComponent('com_jlms')->getLearningService()->freshActor($actor);
            } catch (\RuntimeException $error) {
                $actor = new \Joomla\CMS\User\User();
            }
        }
        $this->hasContentAccess = (bool) $actor->id && !$actor->block && in_array((int) $this->item['course']->content_access, array_map('intval', $actor->getAuthorisedViewLevels()), true);
        $presentation = $this->item['presentation'] ?? [];
        $title = ($presentation['seo_title'] ?? '') ?: $this->item['course']->title;
        $guest = Factory::getUser(0);
        $guestLevels = array_map('intval', $guest->getAuthorisedViewLevels());
        $discoverable = in_array((int) $this->item['course']->catalog_access, $guestLevels, true)
            && in_array((int) $this->item['course']->content_access, $guestLevels, true);
        if ($discoverable) {
            PublicMetadataHelper::apply(
                $this->getDocument(),
                (string) $title,
                (string) ($presentation['seo_description'] ?? ''),
                'index.php?option=com_jlms&view=course&id=' . (int) $this->item['course']->id,
                (string) ($presentation['image'] ?? '')
            );
        } else {
            $this->getDocument()->setTitle((string) $title);
            if (($presentation['seo_description'] ?? '') !== '') {
                $this->getDocument()->setDescription((string) $presentation['seo_description']);
            }
            $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        }
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
