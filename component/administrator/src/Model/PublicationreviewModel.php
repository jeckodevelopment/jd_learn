<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class PublicationreviewModel extends BaseDatabaseModel
{
    public function getItem(): array
    {
        $app = Factory::getApplication();
        $app->setHeader('Cache-Control', 'private, no-store', true);
        $course = $app->getInput()->getInt('id', 0); $version = $app->getInput()->getInt('version_id', 0);
        $plan = $app->getUserState('com_jlms.publication.review.' . $course . '.' . $version);
        if (!is_array($plan) || (int)$plan['actor'] !== (int)$app->getIdentity()->id || (int)$plan['expires'] < time()) {
            throw new \RuntimeException(Text::_('COM_JLMS_PUBLICATION_REVIEW_REQUIRED'), 409);
        }
        try {
            $impact = $app->bootComponent('com_jlms')->getPublicationService()->updateImpact($app->getIdentity(), $course, $version, (int)$plan['transition']);
            if (!$impact || !hash_equals($plan['fingerprint'], $impact['fingerprint'])) { throw new \DomainException('COM_JLMS_PUBLICATION_REVIEW_STALE'); }
            return $impact + ['ticket'=>$plan['ticket']];
        } catch (\DomainException $error) {
            $code = match ($error->getMessage()) { 'JERROR_ALERTNOAUTHOR' => 403, 'COM_JLMS_RESOURCE_NOT_FOUND' => 404, default => 409 };
            throw new \RuntimeException(Text::_($error->getMessage()), $code);
        }
    }
}
