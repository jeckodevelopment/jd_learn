<?php

declare(strict_types=1);

namespace Jlms\Plugin\User\Jdlearnconsent\Extension;

use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Event\User\AfterSaveEvent;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

/** Adds voluntary double-opt-in consent to Joomla's native registration form. */
final class Jdlearnconsent extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepareForm' => 'onContentPrepareForm',
            'onUserAfterSave' => 'onUserAfterSave',
        ];
    }

    public function onContentPrepareForm(PrepareFormEvent $event): void
    {
        $form = $event->getForm();
        if ($form->getName() !== 'com_users.registration') {
            return;
        }

        FormHelper::addFormPath(JPATH_PLUGINS . '/user/jdlearnconsent/forms');
        $form->loadFile('registration');
    }

    public function onUserAfterSave(AfterSaveEvent $event): void
    {
        if (!$event->getIsNew() || !$event->getSavingResult()) {
            return;
        }

        $input = $this->getApplication()->getInput();
        if ($input->getCmd('option') !== 'com_users' || $input->getCmd('task') !== 'registration.register') {
            return;
        }

        $user = $event->getUser();
        if ((int) ($user['jdlearn']['course_announcements'] ?? 0) !== 1) {
            return;
        }

        $userId = (int) ($user['id'] ?? 0);
        $email = (string) ($user['email'] ?? '');
        $name = (string) ($user['name'] ?? '');
        if ($userId < 1 || $email === '') {
            return;
        }

        try {
            $this->getApplication()->bootComponent('com_jlms')
                ->getCourseAnnouncementConsent()
                ->request($userId, $email, $name, 'registration');
        } catch (\Throwable $error) {
            Log::add('Registration consent confirmation failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            $this->getApplication()->enqueueMessage(Text::_('PLG_USER_JDLEARNCONSENT_REQUEST_FAILED'), 'warning');
        }
    }
}
