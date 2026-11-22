<?php
declare(strict_types=1);

require __DIR__ . '/../component/site/src/Helper/CatalogSearch.php';

use Jlms\Component\Jlms\Site\Helper\CatalogSearch;

$expected = [
    'opera' => '%opera%',
    '100%' => '%100!%%',
    'unit_1' => '%unit!_1%',
    'a!b' => '%a!!b%',
    '50!_%' => '%50!!!_!%%',
    // SECDEV-03: injection-looking input stays literal, including LIKE wildcards.
    "' OR 1=1 --" => "%' OR 1=1 --%",
    "'; DROP TABLE courses; --" => "%'; DROP TABLE courses; --%",
    '%_! OR 1=1' => '%!%!_!! OR 1=1%',
];
foreach ($expected as $search => $pattern) {
    if (CatalogSearch::pattern($search) !== $pattern) {
        throw new RuntimeException('Literal catalog search pattern mismatch: ' . $search);
    }
}
echo "CAT-01 literal search LIKE escaping: OK\n";
