<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Course;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public array $item;
    public bool $hasContentAccess = false;
    public bool $isGuest = true;
    public string $mutationKey;

    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
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
        $this->getDocument()->setTitle($this->item['course']->title);
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
