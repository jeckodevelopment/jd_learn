<?php

declare(strict_types=1);

// Isolated contract test for public metadata: no Joomla installation required.
namespace Joomla\CMS\Router {
    final class Route
    {
        public static function _(string $url, bool $xhtml = true): string
        {
            if ($xhtml) {
                throw new \RuntimeException('Canonical route must not be XHTML-encoded');
            }
            return '/it/corsi/42:' . rawurlencode('prova');
        }
    }
}

namespace {
    use Jlms\Tests\Fixtures\Cat04PublicMetadata\PublicDocument;

    require __DIR__ . '/fixtures/cat04-public-uri.php';
    require __DIR__ . '/fixtures/cat04-public-document.php';
    require __DIR__ . '/../component/site/src/Helper/PublicMetadataHelper.php';


    use Jlms\Component\Jlms\Site\Helper\PublicMetadataHelper;

    $doc = new PublicDocument();
    PublicMetadataHelper::apply(
        $doc,
        '  Titolo italiano  ',
        '<p>Descrizione <strong>breve</strong></p>',
        'index.php?option=com_jlms&view=course&id=42',
        'images/locandina storica.png'
    );
    $canonical = 'https://example.test/it/corsi/42:prova';
    if ($doc->head['canonical'] !== $canonical || $doc->meta['property:og:url'] !== $canonical) {
        throw new \RuntimeException('Canonical/Open Graph mismatch');
    }
    if ($doc->title !== 'Titolo italiano' || $doc->description !== 'Descrizione breve') {
        throw new \RuntimeException('Title or description was not sanitized');
    }
    if (
        $doc->meta['property:og:image'] !== 'https://example.test/images/locandina%20storica.png'
        || $doc->meta['name:twitter:image'] !== $doc->meta['property:og:image']
    ) {
        throw new \RuntimeException('Social image path not canonical and encoded');
    }
    if ($doc->meta['name:twitter:title'] !== $doc->meta['property:og:title']) {
        throw new \RuntimeException('Social titles differ');
    }
    foreach (['https://cdn.example.test/picture.png', 'images/../private.png', '//evil.test/a.png'] as $invalid) {
        $bad = new PublicDocument();
        PublicMetadataHelper::apply($bad, 'Test', '', 'index.php?option=com_jlms&view=course&id=42', $invalid);
        if (isset($bad->meta['property:og:image']) || isset($bad->meta['name:twitter:image'])) {
            throw new \RuntimeException('Unsafe social image accepted');
        }
    }
    echo "CAT-04 SEO canonical, social image and metadata contract: OK\n";
}
