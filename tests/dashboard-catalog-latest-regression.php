<?php
declare(strict_types=1);

// CAT-02: An enrollment for a different visible course must not disappear
// because the first course has more than 50 historical editions.
$db->transactionStart();
try {
    $latestEnglish = 0;
    for ($i = 0; $i < 51; $i++) {
        $latestEnglish = $seed('catalog-overflow-' . $i);
    }
    $states = $service->catalogStates($owner, 'en-GB', [
        (int) $f['course_id'],
        (int) $categoryFixture['italianCourse'],
    ]);
    dashExpect(count($states) === 2, 'catalog state covers every requested course beyond 50 historical enrollments');
    dashExpect($states[(int) $f['course_id']]['id'] === $latestEnglish,
        'catalog displays the latest enrollment for the English course');
    dashExpect($states[(int) $categoryFixture['italianCourse']]['id'] === $italian,
        'catalog retains the older Italian course enrollment despite English overflow');
    $foreign = $service->catalogStates($other, 'en-GB', [
        (int) $f['course_id'],
        (int) $categoryFixture['italianCourse'],
    ]);
    dashExpect(!isset($foreign[(int) $categoryFixture['italianCourse']]),
        'other learner never inherits the current owner enrollment state');
} finally {
    $db->transactionRollback();
}
