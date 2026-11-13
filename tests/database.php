<?php

declare(strict_types=1);


/** Disposable CI database only. Never run against a live site. */
$dsn = getenv('JLMS_TEST_DSN');
if (!$dsn || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new \RuntimeException('Explicit disposable database opt-in required.');
}
$db = new \PDO($dsn, getenv('JLMS_TEST_USER'), getenv('JLMS_TEST_PASSWORD'), [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
$prefix = 'ci_';
$db->exec('CREATE TABLE `ci_categories` (`id` INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`)) ENGINE=InnoDB');
$db->exec('INSERT INTO `ci_categories` (`id`) VALUES (1)');
$sql = file_get_contents(dirname(__DIR__) . '/component/administrator/sql/install.mysql.utf8mb4.sql');
$sql = str_replace('#__', $prefix, preg_replace('/--[^\n]*/', '', $sql));
foreach (explode(';', $sql) as $query) {
    if (trim($query) !== '') {
        $db->exec($query);
    }
}
$tables = $db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'ci_jlms_%'")->fetchColumn();
if ((int) $tables !== 47) {
    throw new \RuntimeException('Schema table count mismatch');
}
$mailKeys = $db->query("SHOW INDEX FROM `ci_jlms_mail_queue` WHERE Key_name='uq_event_key'")->fetchAll();
if (count($mailKeys) !== 1 || (int) $mailKeys[0]['Non_unique'] !== 0) {
    throw new \RuntimeException('Mail queue event key is not unique');
}
$constraints = $db->query("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_TYPE='FOREIGN KEY'")->fetchColumn();
if ((int) $constraints !== 66) {
    throw new \RuntimeException('Schema foreign key count mismatch');
}
$db->beginTransaction();
$db->exec("INSERT INTO `ci_jlms_mail_consents` (`user_id`,`state`,`origin`,`policy_version`,`requested_at`,`confirmed_at`) VALUES (987654,'confirmed','dashboard','test',UTC_TIMESTAMP(),UTC_TIMESTAMP())");
$db->exec("INSERT INTO `ci_jlms_mail_unsubscribe_tokens` (`user_id`,`consent_confirmed_at`,`token_hash`,`created_at`,`expires_at`) VALUES (987654,UTC_TIMESTAMP(),REPEAT('a',64),UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 DAY))");
$db->exec("DELETE FROM `ci_jlms_mail_consents` WHERE `user_id`=987654");
if ((int) $db->query("SELECT COUNT(*) FROM `ci_jlms_mail_unsubscribe_tokens` WHERE `user_id`=987654")->fetchColumn() !== 0) { throw new \RuntimeException('Consent deletion did not cascade unsubscribe tokens'); }
$now = gmdate('Y-m-d H:i:s');
function insertRow(\PDO $db, string $table, array $data): int
{
    $columns = implode(',', array_map(static fn (string $name): string => '`' . $name . '`', array_keys($data)));
    $q = $db->prepare('INSERT INTO `ci_jlms_' . $table . '` (' . $columns . ') VALUES (' . implode(',', array_fill(0, count($data), '?')) . ')');
    $q->execute(array_values($data));
    return (int) $db->lastInsertId();
}
function constraintRejects(callable $work): void
{
    try {
        $work();
    } catch (\PDOException $expected) {
        if ($expected->getCode() === '23000') {
            return;
        }
        throw $expected;
    }
    throw new \RuntimeException('Expected database integrity violation');
}
$audit = ['created_at' => $now, 'modified_at' => $now];
$course = insertRow($db, 'courses', $audit + ['catid'=>1,'alias'=>'test','title'=>'Test','catalog_access'=>1,'content_access'=>2,'params_json'=>'{}']);
constraintRejects(fn () => $db->exec('DELETE FROM `ci_categories` WHERE `id`=1'));
$version = insertRow($db, 'course_versions', $audit + ['course_id'=>$course,'version_no'=>1,'title_snapshot'=>'Test','rules_json'=>'{}','presentation_json'=>'{}','fields_snapshot_json'=>'{}']);
$other = insertRow($db, 'course_versions', $audit + ['course_id'=>$course,'version_no'=>2,'title_snapshot'=>'Other','rules_json'=>'{}','presentation_json'=>'{}','fields_snapshot_json'=>'{}']);
$module = insertRow($db, 'modules', $audit + ['course_version_id'=>$version,'logical_key'=>'11111111-1111-4111-8111-111111111111','title'=>'Module','ordering'=>0,'params_json'=>'{}']);
$lessonData = $audit + ['course_version_id'=>$version,'module_id'=>$module,'logical_key'=>'22222222-2222-4222-8222-222222222222','title'=>'Lesson','alias'=>'lesson','kind'=>'text','ordering'=>0,'completion_rule'=>'manual','params_json'=>'{}','fields_snapshot_json'=>'{}'];
$lesson = insertRow($db, 'lessons', $lessonData);
$wrong = $lessonData;$wrong['course_version_id']=$other;
constraintRejects(fn () => insertRow($db, 'lessons', $wrong));
$edition = insertRow($db, 'editions', $audit + ['course_id'=>$course,'course_version_id'=>$version,'title'=>'Default','code'=>'default-v1']);
$subject='33333333-3333-4333-8333-333333333333';
insertRow($db, 'subjects', ['subject_key'=>$subject,'user_id'=>42,'created_at'=>$now]);
$enrolData=$audit+['user_id'=>42,'subject_key'=>$subject,'course_id'=>$course,'course_version_id'=>$version,'edition_id'=>$edition,'origin'=>'auto','required_total'=>1];
$enrollment=insertRow($db,'enrollments',$enrolData);
constraintRejects(fn () => insertRow($db,'enrollments',$enrolData));
$wrong=$enrolData;$wrong['course_version_id']=$other;
constraintRejects(fn () => insertRow($db,'enrollments',$wrong));
$change=['enrollment_id'=>$enrollment,'course_version_id'=>$other,'revision_no'=>1,'enrollment_revision'=>1,'previous_status'=>'active','target_status'=>'suspended','impact_hash'=>str_repeat('a',64),'reason'=>'Test','mutation_id'=>'44444444-4444-4444-a444-444444444444','request_hash'=>str_repeat('b',64),'created_at'=>$now];
constraintRejects(fn () => insertRow($db,'enrollment_state_changes',$change));
$change['course_version_id']=$version;insertRow($db,'enrollment_state_changes',$change);$change['revision_no']=2;
constraintRejects(fn () => insertRow($db,'enrollment_state_changes',$change));
$manual=['enrollment_id'=>$enrollment,'course_version_id'=>$other,'mutation_id'=>'55555555-5555-4555-a555-555555555555','request_hash'=>str_repeat('a',64),'claim_token'=>str_repeat('b',32),'impact_hash'=>str_repeat('c',64),'reason'=>'Test','created_at'=>$now];
constraintRejects(fn () => insertRow($db,'manual_enrollments',$manual));
$manual['course_version_id']=$version;insertRow($db,'manual_enrollments',$manual);$manual['enrollment_id']=null;$manual['course_version_id']=null;
constraintRejects(fn () => insertRow($db,'manual_enrollments',$manual));
$db->rollBack();
echo json_encode(['database'=>$db->getAttribute(\PDO::ATTR_SERVER_VERSION),'tables'=>(int)$tables,'foreign_keys'=>(int)$constraints,'integrity_cases'=>8,'status'=>'passed'], JSON_THROW_ON_ERROR) . PHP_EOL;
