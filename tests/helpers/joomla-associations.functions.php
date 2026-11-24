<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\VideoProviderRegistry;

function assocExpect(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new \RuntimeException($message);
    }
    $checks++;
}

function assocData(int $id): array
{
    global $db;
    return (array) $db->setQuery(('SELECT id,title,alias,catid,language,catalog_access,content_' .
        'access,enrollment_mode,summary,revision FROM #__jlms_courses WHERE id=') . $id)->loadObject();
}

function assocSave(array $data): bool
{
    global $factory;
    $model = $factory->createModel('Course', 'Administrator', ['ignore_request' => true]);
    $saved = $model->save($data);
    if (!$saved) {
        fwrite(STDERR, 'Association save rejected: ' . json_encode($model->getErrors(), JSON_THROW_ON_ERROR) . PHP_EOL);
    }
    return $saved;
}

function assocRows(): string
{
    global $db;
    return json_encode(
        $db->setQuery("SELECT * FROM #__associations WHERE context='com_jlms.item' ORDER BY id")->loadObjectList(),
        JSON_THROW_ON_ERROR
    );
}
