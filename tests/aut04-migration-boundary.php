<?php

declare(strict_types=1);

/** AUT-04: saved lesson equivalences must never be silently truncated. */
require_once dirname(__DIR__) . '/component/administrator/src/Application/EnrollmentMigrationService.php';

$boundary = new ReflectionMethod(
    \Jlms\Component\Jlms\Administrator\Application\EnrollmentMigrationService::class,
    'requireCompleteSavedEquivalences'
);
foreach ([0, 1, 99, 100, 999, 1000] as $value) {
    $boundary->invoke(null, $value);
}
try {
    $boundary->invoke(null, 1001);
    throw new RuntimeException('Saved mapping overflow was accepted');
} catch (RuntimeException $error) {
    if ($error->getMessage() !== 'COM_JLMS_MIGRATION_LIMIT' || $error->getCode() !== 409) {
        throw $error;
    }
}
$source = file_get_contents(dirname(__DIR__) . '/component/administrator/src/Application/EnrollmentMigrationService.php');
$load = strpos($source, '$savedEquivalences = $this->db->setQuery($q, 0, 1001)->loadObjectList();');
$guard = strpos($source, 'self::requireCompleteSavedEquivalences(count($savedEquivalences));');
$use = strpos($source, 'foreach ($savedEquivalences as $eq)');
if ($load === false || $guard === false || $use === false || $load >= $guard || $guard >= $use) {
    throw new RuntimeException('Saved equivalence completeness check is not before mapping');
}
echo "AUT-04 saved equivalence completeness: 0-1000 allowed, 1001 rejected\n";
