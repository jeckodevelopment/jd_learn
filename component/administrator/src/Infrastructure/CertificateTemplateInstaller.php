<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Joomla\Database\DatabaseInterface;

/** Installs a safe starter template without overwriting an administrator's templates. */
final class CertificateTemplateInstaller
{
    public static function ensureDefault(DatabaseInterface $db): void
    {
        $query = $db->createQuery()->select('COUNT(*)')->from('#__jlms_certificate_templates')->where('state = 1');
        if ((int) $db->setQuery($query)->loadResult() > 0) {
            return;
        }

        $definition = ['schema' => 1, 'title' => 'Attestato di completamento',
            'subtitle' => 'ha completato il corso', 'locale' => 'it-IT'];
        $json = json_encode($definition, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $now = gmdate('Y-m-d H:i:s');
        $record = (object) ['title' => 'JD Learn — Attestato standard', 'version_no' => 1,
            'template_json' => $json, 'background_asset_id' => null, 'hash' => hash('sha256', $json),
            'state' => 1, 'created_by' => null, 'created_at' => $now, 'modified_by' => null, 'modified_at' => $now];
        $db->insertObject('#__jlms_certificate_templates', $record, 'id');
    }
}
