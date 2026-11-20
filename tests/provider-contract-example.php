<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../docs/examples/video-provider-contract/ExampleProvider.php';

use Jlms\Contracts\Video\VideoProviderRegistry;
use Jlms\Examples\Video\ExampleProvider;

$registry = new VideoProviderRegistry();
$registration = require __DIR__ . '/../docs/examples/video-provider-contract/register.php';
$registration($registry);
$descriptors = $registry->descriptors();
if (count($descriptors) !== 1 || $descriptors[0]->key !== 'exampleprovider'
    || $descriptors[0]->contractMajor !== 1 || $descriptors[0]->configured) {
    throw new RuntimeException('Example descriptor mismatch');
}
$instance = $registry->get('exampleprovider');
if (!$instance instanceof ExampleProvider || $registry->get('exampleprovider') !== $instance) {
    throw new RuntimeException('Example registry lazy factory or cache mismatch');
}
try {
    $instance->fetchMetadata(
        new \Jlms\Contracts\Video\VideoSource('exampleprovider', 'unavailable', 1),
        new \Jlms\Contracts\Video\MetadataContext(1, 'it-IT', false)
    );
    throw new RuntimeException('Example must not promise metadata');
} catch (DomainException $expected) {
    // Deliberately closed: this is a registry integration example, not a fifth player.
}
try {
    $registration($registry);
    throw new RuntimeException('Duplicate key accepted');
} catch (DomainException $expected) {
}
try {
    (new VideoProviderRegistry())->register(
        new \Jlms\Contracts\Video\ProviderDescriptor('wrongmajor', 'EXAMPLE', 2, false),
        static fn (): ExampleProvider => new ExampleProvider()
    );
    throw new RuntimeException('Incompatible major accepted');
} catch (DomainException $expected) {
}
require_once __DIR__ . '/../docs/examples/video-provider-contract/plugin/src/Provider/Provider.php';
$pluginAdapter = new \Jlms\Plugin\Jlmsvideo\Exampleprovider\Provider\Provider();
if ($pluginAdapter->getDescriptor()->key !== 'exampleprovider'
    || $pluginAdapter->getDescriptor()->contractMajor !== 1
    || $pluginAdapter->getDescriptor()->configured) {
    throw new RuntimeException('Joomla skeleton descriptor mismatch');
}
$manifest = simplexml_load_file(__DIR__ . '/../docs/examples/video-provider-contract/plugin/exampleprovider.xml');
if (!$manifest || (string) $manifest['group'] !== 'jlmsvideo'
    || (string) $manifest->namespace !== 'Jlms\Plugin\Jlmsvideo\Exampleprovider') {
    throw new RuntimeException('Sample Joomla plugin manifest is not aligned with source namespace');
}
echo "PBASE-03: registry and Joomla plugin skeleton contract OK\n";
