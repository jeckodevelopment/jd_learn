<?php

declare(strict_types=1);

/** AUT-04/LES-01: a copy must fail before truncating the 1001st native value. */
require_once dirname(__DIR__) . '/component/administrator/src/Application/CourseVersionCopy.php';

$method = new ReflectionMethod(
    \Jlms\Component\Jlms\Administrator\Application\CourseVersionCopy::class,
    'requireCompleteFieldCopy'
);

foreach ([0, 1, 999, 1000] as $count) {
    $method->invoke(null, $count);
}

try {
    $method->invoke(null, 1001);
    throw new RuntimeException('Incomplete native field collection was accepted');
} catch (DomainException $error) {
    if ($error->getMessage() !== 'COM_JLMS_OUTLINE_LIMIT') {
        throw $error;
    }
}

$source = file_get_contents(dirname(__DIR__) . '/component/administrator/src/Application/CourseVersionCopy.php');
$fetch = strpos($source, '$rows = $this->db->setQuery($q, 0, 1001)->loadObjectList();');
$guard = strpos($source, 'self::requireCompleteFieldCopy(count($rows));', $fetch === false ? 0 : $fetch);
$copy = strpos($source, 'foreach ($rows as $row)', $guard === false ? 0 : $guard);
if ($fetch === false || $guard === false || $copy === false || !($fetch < $guard && $guard < $copy)) {
    throw new RuntimeException('Native field copy must check truncation before persisting any values');
}

echo "AUT-04/LES-01 native field-copy boundary: positive/negative checks passed\n";
