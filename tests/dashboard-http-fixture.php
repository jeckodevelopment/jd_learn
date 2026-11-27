<?php

declare(strict_types=1);

$root = realpath($argv[1] ?? '');
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new \RuntimeException('Disposable Joomla site required');
}
$db = new \PDO(
    getenv('JLMS_TEST_DSN'),
    getenv('JLMS_TEST_USER'),
    getenv('JLMS_TEST_PASSWORD'),
    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
);
$f = json_decode(file_get_contents(dirname($root) . '/dashboard-fixture.json'), true, 16, JSON_THROW_ON_ERROR);
$state = dirname($root) . '/dashboard-http-plugin.json';
$mode = $argv[2] ?? '';
if ($mode === 'enable') {
    file_put_contents($state, json_encode($db->query(("SELECT enabled,params FROM joomla_extensions WHERE "
                . "type='plugin' AND folder='system' AND element='languagefilter'"))
                    ->fetch(\PDO::FETCH_ASSOC), JSON_THROW_ON_ERROR));
    $db->exec(("UPDATE joomla_extensions SET "
                . "enabled=1,params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.detect_bro" .
                    "wser',0,'$.remove_default_prefix',0) WHERE type='plugin' AND folder='sys" .
                    "tem' AND element='languagefilter'"));
} elseif ($mode === 'disable') {
    $s = json_decode(file_get_contents($state), true, 16, JSON_THROW_ON_ERROR);
    $q = $db->prepare(("UPDATE joomla_extensions SET enabled=?,params=? WHERE type='plugin' AND folder='system' "
                . "AND element='languagefilter'"));
    $q->execute([$s['enabled'],$s['params']]);
    $q = $db->prepare("UPDATE joomla_users SET block=0 WHERE id=? AND username='cidashboard'");
    $q->execute([$f['owner']]);
    $q = $db->prepare('INSERT IGNORE INTO joomla_user_usergroup_map(user_id,group_id) VALUES(?,?)');
    $q->execute([$f['owner'],$f['group']]);
} elseif ($mode === 'navigation_on') {
    $menuId = (int)$f['menus']['en-GB'];
    $q = $db->prepare('SELECT params FROM joomla_menu WHERE id=?');
    $q->execute([$menuId]);
    $old = $q->fetchColumn();
    if ($old === false) {
        throw new \RuntimeException('FE-06 fixture menu missing');
    }
    file_put_contents(dirname($root) . '/dashboard-navigation-original.json', json_encode(['id'
        => $menuId,'params' => $old], JSON_THROW_ON_ERROR));
    $params = json_decode((string)$old, true) ?: [];
    $params['show_personal_navigation'] = 1;
    $q = $db->prepare('UPDATE joomla_menu SET params=? WHERE id=?');
    $q->execute([json_encode($params, JSON_THROW_ON_ERROR),$menuId]);
} elseif ($mode === 'navigation_off') {
    $saved = json_decode(
        file_get_contents(dirname($root) . '/dashboard-navigation-original.json'),
        true,
        8,
        JSON_THROW_ON_ERROR
    );
    $q = $db->prepare('UPDATE joomla_menu SET params=? WHERE id=?');
    $q->execute([$saved['params'],$saved['id']]);
} elseif ($mode === 'snapshot') {
    $q = $db->prepare(('SELECT id,revision,completed_total,started_at,completed_at,last_activity_at,last_lesson_id '
                . 'FROM joomla_jlms_enrollments WHERE user_id=? ORDER BY id'));
    $q->execute([$f['owner']]);
    $rows = $q->fetchAll(\PDO::FETCH_ASSOC);
    $q = $db->prepare(('SELECT COUNT(*) FROM joomla_jlms_attempts WHERE enrollment_id IN (SELECT id FROM '
                . 'joomla_jlms_enrollments WHERE user_id=?)'));
    $q->execute([$f['owner']]);
    echo json_encode(['enrollments' => $rows,'attempts' => (int)$q->fetchColumn()], JSON_THROW_ON_ERROR);
} elseif (in_array($mode, ['revoke','grant','block','unblock'], true)) {
    $q = $db->prepare("SELECT username FROM joomla_users WHERE id=?");
    $q->execute([$f['owner']]);
    if ($q->fetchColumn() !== 'cidashboard') {
        throw new \RuntimeException('Unexpected fixture user');
    }
    if ($mode === 'revoke') {
        $q = $db->prepare('DELETE FROM joomla_user_usergroup_map WHERE user_id=? AND group_id=?');
        $q->execute([$f['owner'],$f['group']]);
    } elseif ($mode === 'grant') {
        $q = $db->prepare('INSERT IGNORE INTO joomla_user_usergroup_map(user_id,group_id) VALUES(?,?)');
        $q->execute([$f['owner'],$f['group']]);
    } else {
        $q = $db->prepare('UPDATE joomla_users SET block=? WHERE id=?');
        $q->execute([$mode === 'block' ? 1 : 0,$f['owner']]);
    }
} elseif (in_array($mode, ['viewlevel_revoke','viewlevel_restore'], true)) {
    $levelId = (int) ($f['access_level_id'] ?? 0);
    if ($levelId < 1) {
        throw new \RuntimeException('Dashboard fixture viewing access level missing');
    }
    $q = $db->prepare('SELECT rules FROM joomla_viewlevels WHERE id=?');
    $q->execute([$levelId]);
    $rules = $q->fetchColumn();
    if ($rules === false) {
        throw new \RuntimeException('Dashboard fixture viewing access level not found');
    }
    $state = dirname($root) . '/dashboard-http-viewlevel-original.json';
    if ($mode === 'viewlevel_revoke') {
        $q = $db->prepare('SELECT 1 FROM joomla_user_usergroup_map WHERE user_id=? AND group_id=?');
        $q->execute([$f['owner'],$f['group']]);
        if (!$q->fetchColumn()) {
            throw new \RuntimeException('Viewing access test requires the owner to remain in its Joomla group');
        }
        file_put_contents($state, json_encode(['id' => $levelId,'rules' => $rules], JSON_THROW_ON_ERROR));
        $q = $db->prepare("UPDATE joomla_viewlevels SET rules='[]' WHERE id=?");
        $q->execute([$levelId]);
    } else {
        $saved = json_decode(file_get_contents($state), true, 8, JSON_THROW_ON_ERROR);
        if ((int) $saved['id'] !== $levelId) {
            throw new \RuntimeException('Dashboard fixture viewing access level changed unexpectedly');
        }
        $q = $db->prepare('UPDATE joomla_viewlevels SET rules=? WHERE id=?');
        $q->execute([$saved['rules'],$levelId]);
    }
} elseif (in_array($mode, ['course_off','course_on'], true)) {
    $q = $db->prepare('UPDATE joomla_jlms_courses SET state=? WHERE id=?');
    $q->execute([$mode === 'course_off' ? 0 : 1,$f['course']]);
} elseif (in_array($mode, ['language_missing','language_restore'], true)) {
    $q = $db->prepare('UPDATE joomla_jlms_courses SET language=? WHERE id=?');
    $q->execute([$mode === 'language_missing' ? 'zz-ZZ' : 'en-GB',$f['course']]);
} else {
    throw new \RuntimeException('Unknown fixture mode');
}
