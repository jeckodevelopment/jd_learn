<?php

declare(strict_types=1);
/** Explicit one-enrollment migration; run only against the disposable Joomla CI installation. */
$root=realpath($argv[1]??'');
if(!$root||getenv('JLMS_TEST_DISPOSABLE')!=='1')throw new RuntimeException('Disposable Joomla site required.');
$_SERVER['HTTP_HOST']='localhost';$_SERVER['SERVER_NAME']='localhost';$_SERVER['SERVER_PORT']='80';$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REQUEST_URI']='/administrator/index.php?option=com_jlms';$_SERVER['SCRIPT_NAME']='/administrator/index.php';$_SERVER['PHP_SELF']='/administrator/index.php';$_SERVER['SCRIPT_FILENAME']=$root.'/administrator/index.php';
define('_JEXEC',1);define('JPATH_BASE',$root.'/administrator');require $root.'/administrator/includes/defines.php';require $root.'/administrator/includes/framework.php';
define('JPATH_COMPONENT',JPATH_ADMINISTRATOR.'/components/com_jlms');define('JPATH_COMPONENT_ADMINISTRATOR',JPATH_COMPONENT);define('JPATH_COMPONENT_SITE',JPATH_SITE.'/components/com_jlms');
use Joomla\CMS\Factory;use Joomla\CMS\Application\AdministratorApplication;use Joomla\CMS\Session\Session;use Joomla\Session\SessionInterface;
use Joomla\Database\DatabaseInterface;use Joomla\CMS\User\UserFactoryInterface;
$container=Factory::getContainer();$container->alias('session.web','session.web.administrator')->alias('session','session.web.administrator')->alias(Session::class,'session.web.administrator')->alias(SessionInterface::class,'session.web.administrator');
$app=$container->get(AdministratorApplication::class);Factory::$application=$app;$app->loadLanguage($container->get(\Joomla\CMS\Language\LanguageFactoryInterface::class)->createLanguage('en-GB',false));$app->createExtensionNamespaceMap();
$db=$container->get(DatabaseInterface::class);$uid=(int)$db->setQuery("SELECT id FROM #__users WHERE username='cioperator'")->loadResult();if(!$uid)throw new RuntimeException('Test operator missing.');
$actor=$container->get(UserFactoryInterface::class)->loadUserById($uid);$app->loadIdentity($actor);$app->getSession()->set('user',$actor);$component=$app->bootComponent('com_jlms');$checks=0;
function migrationExpect(bool $ok,string $message):void{global$checks;if(!$ok)throw new RuntimeException($message);$checks++;}
function migrationRow(string $table,int $id):object{global$db;return $db->setQuery('SELECT * FROM #__jlms_'.$table.' WHERE id='.$id)->loadObject();}
function migrationDenied(callable $fn,int $code,string $message):void{try{$fn();}catch(RuntimeException $e){migrationExpect($e->getCode()===$code,$message.': '.$e->getMessage());return;}throw new RuntimeException($message);}
$fixture=dirname($root).'/enrollment-migration-fixture.json';$mode=$argv[2]??'';
if($mode==='retained'){
 $f=json_decode(file_get_contents($fixture),true,16,JSON_THROW_ON_ERROR);$source=migrationRow('enrollments',$f['source']);$target=migrationRow('enrollments',$f['target']);
 migrationExpect($source->status==='migrated','source enrollment remains archived after migration');migrationExpect((int)$target->course_version_id===$f['target_version'],'target assignment remains in the current version');
 migrationExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollment_migrations WHERE source_enrollment_id='.$f['source'].' AND target_enrollment_id='.$f['target'])->loadResult()===1,'one immutable migration record remains');

 $retainedCertificate=(array)migrationRow('certificates',(int)$f['certificate']);
 $retainedDocument=(array)migrationRow('certificate_documents',(int)$f['certificate_document']);
 migrationExpect((int)$retainedCertificate['enrollment_id']===(int)$f['source']
     && (int)$retainedCertificate['course_version_id']===(int)$f['source_version']
     && (int)$retainedDocument['certificate_id']===(int)$f['certificate'],
     'upgrade retains original ownership and source version for historical certificate');
 migrationExpect(hash('sha256',json_encode([$retainedCertificate,$retainedDocument],JSON_THROW_ON_ERROR))===$f['certificate_source_hash'],
     'upgrade retains certificate and immutable document snapshot byte-for-byte');

 echo json_encode(['enrollment_migration_retained_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;return;
}
$cat=(int)$db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
$model=$component->getMVCFactory()->createModel('Course','Administrator',['ignore_request'=>true]);
if(!$model->save(['id'=>0,'title'=>'Enrollment migration fixture','alias'=>'enrollment-migration-fixture','catid'=>$cat,'language'=>'*','catalog_access'=>1,'content_access'=>1,'enrollment_mode'=>'auto','presentation_description'=>'<p>Migration fixture description.</p>','summary'=>'Migration fixture']))throw new RuntimeException('Migration fixture course could not be saved: '.$model->getError());
$course=(int)$model->getState('course.id');$author=$component->getCurriculumService();$publication=$component->getPublicationService();$learning=$component->getLearningService();
$version=(int)$author->createDraft($actor,$course,(int)migrationRow('courses',$course)->revision);$scope=static fn():array=>['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>migrationRow('course_versions',$version)->revision];
$module=$author->saveModule($actor,$scope()+['id'=>0,'title'=>'Migration chapter']);
$lesson=$author->saveLesson($actor,$scope()+['id'=>0,'module_id'=>$module,'title'=>'Completed source lesson','kind'=>'text','required'=>'1','body'=>'<p>Completed.</p>']);
$transition=(int)$publication->transitions($actor,$course,$version)[0]->id;
$publication->transition($actor,$course,$version,(int)migrationRow('courses',$course)->revision,(int)migrationRow('course_versions',$version)->revision,$transition);
$sourceVersion=$version;$enrollment=$learning->start($actor,'en-GB',$course,str_repeat('m',40));
$now=gmdate('Y-m-d H:i:s');$progress=(object)['enrollment_id'=>$enrollment,'course_version_id'=>$sourceVersion,'lesson_id'=>$lesson,'source_revision'=>(int)migrationRow('lessons',$lesson)->source_revision,
 'coverage_json'=>'[]','covered_ms'=>0,'resume_ms'=>0,'state'=>'completed','completed_at'=>$now,'last_activity_at'=>$now,'revision'=>1];$db->insertObject('#__jlms_lesson_progress',$progress);
$version=(int)$author->createDraft($actor,$course,(int)migrationRow('courses',$course)->revision);$outline=$author->outline($actor,$course,$version);
$targetLesson=(int)$db->setQuery('SELECT t.id FROM #__jlms_lessons AS s INNER JOIN #__jlms_lessons AS t ON t.logical_key=s.logical_key WHERE s.id='.$lesson.' AND t.course_version_id='.$version)->loadResult();
if(!$targetLesson)throw new RuntimeException('Copied version did not preserve the lesson equivalence key.');
$transition=(int)$publication->transitions($actor,$course,$version)[0]->id;$review=$publication->updateImpact($actor,$course,$version,$transition);
$publication->transition($actor,$course,$version,(int)migrationRow('courses',$course)->revision,(int)migrationRow('course_versions',$version)->revision,$transition,$review['fingerprint']);
$service=$component->getEnrollmentMigrationService();$mapping=[$lesson=>$targetLesson];$plan=$service->preview($actor,$course,$enrollment,$mapping);
// SEC-P1: a session identity captured before an administrator blocks the
// account must not retain read/preview or mutation access. No migration rows
// or enrollment state may change when the access check denies these calls.
$originalBlock=(int)$db->setQuery('SELECT block FROM #__users WHERE id='.$uid)->loadResult();
$sourceStatusBeforeBlock=(string)migrationRow('enrollments',$enrollment)->status;
$migrationsBeforeBlock=(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollment_migrations WHERE source_enrollment_id='.$enrollment)->loadResult();
try {
 $db->setQuery('UPDATE #__users SET block=1 WHERE id='.$uid)->execute();
 migrationDenied(fn()=>$service->preview($actor,$course,$enrollment,$mapping),403,'blocked stale actor cannot preview migration');
 migrationDenied(fn()=>$service->apply($actor,$course,$enrollment,1,$mapping,'Blocked actor attempt','00000000-0000-4000-8000-000000000001',str_repeat('0',64),true),403,'blocked stale actor cannot apply migration');
 migrationExpect((string)migrationRow('enrollments',$enrollment)->status===$sourceStatusBeforeBlock,'blocked access does not mutate source enrollment');
 migrationExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollment_migrations WHERE source_enrollment_id='.$enrollment)->loadResult()===$migrationsBeforeBlock,'blocked access does not create migration record');
} finally {
 $db->setQuery('UPDATE #__users SET block='.$originalBlock.' WHERE id='.$uid)->execute();
}
migrationExpect($service->preview($actor,$course,$enrollment,$mapping)['hash']===$plan['hash'],'migration preview restored after unblocking the operator');

migrationExpect(count($plan['transfers'])===1&&$plan['transfers'][0]['target_lesson_id']===$targetLesson,'preview maps only the explicit completed non-quiz lesson equivalence');
migrationExpect($plan['blocked']===null,'valid single enrollment migration is enabled');
$emptyPlan=$service->preview($actor,$course,$enrollment,[]);
migrationExpect($emptyPlan['transfers']===[]&&count($emptyPlan['unmapped'])===1,'unmapped completion stays only on the source version');
migrationDenied(fn()=>$service->preview($actor,$course,$enrollment,[$lesson=>999999]),422,'lesson IDs outside the destination version are rejected');
// LCM-01: inactive courses must not migrate students even with a fresh admin preview.
foreach ([0, 2, -2] as $inactiveState) {
 $lifecycleRecord=(object)['id'=>$course,'state'=>$inactiveState];
 $db->updateObject('#__jlms_courses', $lifecycleRecord, 'id');
 $blockedPlan=$service->preview($actor,$course,$enrollment,$mapping);
 migrationExpect($blockedPlan['blocked']==='COM_JLMS_LEARNING_DENIED','inactive course disables migration preview');
 migrationDenied(fn()=>$service->apply($actor,$course,$enrollment,(int)migrationRow('enrollments',$enrollment)->revision,
     $mapping,'Inactive migration refused','1e6d0f21-8c37-4a42-b57a-185fe9d0c001',$blockedPlan['hash'],true),409,
     'inactive course rejects migration even with current impact hash');
 migrationExpect(migrationRow('enrollments',$enrollment)->status==='active','blocked migration preserves active enrollment');
}
$lifecycleRecord=(object)['id'=>$course,'state'=>1];
$db->updateObject('#__jlms_courses', $lifecycleRecord, 'id');
migrationExpect($service->preview($actor,$course,$enrollment,$mapping)['blocked']===null,'republished course restores migration eligibility');
// AUT-04: in-progress quizzes must block migration without creating any target
// assignment or changing the source, even if a valid preview was already issued.
$beforeBlockedMigrationCount=(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollment_migrations WHERE source_enrollment_id='.$enrollment)->loadResult();
$beforeBlockedSource=(array)migrationRow('enrollments',$enrollment);
$probeQuiz=(object)['lesson_id'=>$lesson,'course_version_id'=>$sourceVersion,
    'selection_json'=>'{}','created_at'=>gmdate('Y-m-d H:i:s'),'modified_at'=>gmdate('Y-m-d H:i:s')];
$db->insertObject('#__jlms_quizzes',$probeQuiz,'id');
$probeAttempt=(object)['enrollment_id'=>$enrollment,'course_version_id'=>$sourceVersion,'quiz_id'=>(int)$probeQuiz->id,
    'attempt_no'=>1,'status'=>'open','rules_snapshot_json'=>'{}','max_points'=>100,
    'started_at'=>gmdate('Y-m-d H:i:s')];
$db->insertObject('#__jlms_attempts',$probeAttempt,'id');
$activeAttempt=(object)['enrollment_id'=>$enrollment,'quiz_id'=>(int)$probeQuiz->id,'attempt_id'=>(int)$probeAttempt->id];
$db->insertObject('#__jlms_active_attempts',$activeAttempt);
try {
 $attemptPlan=$service->preview($actor,$course,$enrollment,$mapping);
 migrationExpect($attemptPlan['blocked']==='COM_JLMS_MIGRATION_OPEN_ATTEMPT','open quiz attempt blocks version migration');
 migrationDenied(fn()=>$service->apply($actor,$course,$enrollment,(int)$beforeBlockedSource['revision'],
     $mapping,'Open quiz migration denied','4e6d0f21-8c37-4a42-b57a-185fe9d0c001',$attemptPlan['hash'],true),
     409,'open quiz cannot be migrated even after explicit confirmation');
 migrationExpect((array)migrationRow('enrollments',$enrollment)===$beforeBlockedSource,'open-quiz denial preserves source enrollment exactly');
 migrationExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollment_migrations WHERE source_enrollment_id='.$enrollment)->loadResult()===$beforeBlockedMigrationCount,
     'open-quiz denial does not create migration records');
} finally {
 $db->setQuery('DELETE FROM #__jlms_active_attempts WHERE enrollment_id='.(int)$enrollment.' AND quiz_id='.(int)$probeQuiz->id)->execute();
 $db->setQuery('DELETE FROM #__jlms_attempts WHERE id='.(int)$probeAttempt->id)->execute();
 $db->setQuery('DELETE FROM #__jlms_quizzes WHERE id='.(int)$probeQuiz->id)->execute();
}
migrationExpect($service->preview($actor,$course,$enrollment,$mapping)['blocked']===null,'closing quiz attempt restores migration eligibility');
// A destination edition at capacity must also fail without partial migration.
// Use capacity zero on the disposable edition and restore the original setting.
$destinationEdition=(int)$plan['target_edition_id'];
$editionBefore=$db->setQuery('SELECT capacity FROM #__jlms_editions WHERE id='.$destinationEdition)->loadResult();
$db->setQuery('UPDATE #__jlms_editions SET capacity=0 WHERE id='.$destinationEdition)->execute();
try {
 $fullPlan=$service->preview($actor,$course,$enrollment,$mapping);
 migrationExpect($fullPlan['blocked']==='COM_JLMS_MIGRATION_EDITION_FULL','full target edition blocks migration');
 migrationDenied(fn()=>$service->apply($actor,$course,$enrollment,(int)$beforeBlockedSource['revision'],
     $mapping,'Edition full migration denied','5e6d0f21-8c37-4a42-b57a-185fe9d0c001',$fullPlan['hash'],true),
     409,'confirmed migration cannot bypass edition capacity');
 migrationExpect((array)migrationRow('enrollments',$enrollment)===$beforeBlockedSource,'capacity denial preserves source enrollment');
} finally {
 $db->setQuery('UPDATE #__jlms_editions SET capacity=' . ($editionBefore===null?'NULL':(int)$editionBefore)
     .' WHERE id='.$destinationEdition)->execute();
}
migrationExpect($service->preview($actor,$course,$enrollment,$mapping)['blocked']===null,'restored edition capacity re-enables migration');
// Saved equivalences can be used only by explicitly selecting that preview mode.
$approved=(object)['source_lesson_id'=>$lesson,'target_lesson_id'=>$targetLesson,
 'policy_json'=>'{"transfer":"completion_only"}','approved_by'=>$uid,'approved_at'=>gmdate('Y-m-d H:i:s')];
$db->insertObject('#__jlms_version_equivalences',$approved);
$savedPlan=$service->preview($actor,$course,$enrollment,[],true);
migrationExpect($savedPlan['mapping']===$mapping && count($savedPlan['transfers'])===1,
    'saved equivalence maps exactly one completed lesson when explicitly requested');
migrationExpect($service->preview($actor,$course,$enrollment,[],false)['transfers']===[],
    'saved equivalences are never applied implicitly');

// AUT-04: a pre-existing historical certificate document belongs to the source
// version, even if this one enrollment is later explicitly transferred. This
// fixture tests persistence and ownership, not the issuance eligibility path.
$templateId=(int)$db->setQuery('SELECT id FROM #__jlms_certificate_templates WHERE state=1 ORDER BY id LIMIT 1')->loadResult();
migrationExpect($templateId>0,'default immutable certificate template available for history retention');
$historyCertificate=(object)['enrollment_id'=>$enrollment,'course_version_id'=>$sourceVersion,
    'template_id'=>$templateId,'current_document_id'=>null,'issued_at'=>$now,'revision'=>1];
$db->insertObject('#__jlms_certificates',$historyCertificate,'id');
$identityJson=json_encode(['user_id'=>(int)$actor->id,'course_id'=>$course,
    'course_version_id'=>$sourceVersion,'certificate_history_test'=>true],JSON_THROW_ON_ERROR);
$templateJson=(string)$db->setQuery('SELECT template_json FROM #__jlms_certificate_templates WHERE id='.$templateId)->loadResult();
$historyDocument=(object)['certificate_id'=>(int)$historyCertificate->id,'document_no'=>1,
    'serial'=>'AUT04-'.strtoupper(bin2hex(random_bytes(12))),'identity_snapshot_json'=>$identityJson,
    'template_snapshot_json'=>$templateJson,'status'=>'pending_render','issued_at'=>$now,'revision'=>1];
$db->insertObject('#__jlms_certificate_documents',$historyDocument,'id');
$db->setQuery('UPDATE #__jlms_certificates SET current_document_id='.(int)$historyDocument->id.
    ' WHERE id='.(int)$historyCertificate->id)->execute();
$certificateBefore=(array)migrationRow('certificates',(int)$historyCertificate->id);
$documentBefore=(array)migrationRow('certificate_documents',(int)$historyDocument->id);
$versionBefore=(array)migrationRow('course_versions',$sourceVersion);

$beforeProgress=(array)$db->setQuery('SELECT * FROM #__jlms_lesson_progress WHERE enrollment_id='.$enrollment.' AND lesson_id='.$lesson)->loadObject();
$sourceBefore=migrationRow('enrollments',$enrollment);$key='2e6d0f21-8c37-4a42-b57a-185fe9d0c001';
migrationDenied(fn()=>$service->apply($actor,$course,$enrollment,(int)$sourceBefore->revision,$mapping,'Learner requested transfer',$key,str_repeat('0',64),true),409,'stale preview hash prevents any migration write');
migrationDenied(fn()=>$service->apply($actor,$course,$enrollment,(int)$sourceBefore->revision,['not-an-id'=>'5foo'],'Learner requested transfer','3e6d0f21-8c37-4a42-b57a-185fe9d0c001',$plan['hash'],true),400,'service rejects malformed mapping values without relying on controller validation');
$target=$service->apply($actor,$course,$enrollment,(int)$sourceBefore->revision,$mapping,'Learner requested transfer',$key,$plan['hash'],true);
$afterSource=migrationRow('enrollments',$enrollment);$afterProgress=(array)$db->setQuery('SELECT * FROM #__jlms_lesson_progress WHERE enrollment_id='.$enrollment.' AND lesson_id='.$lesson)->loadObject();
$targetRow=migrationRow('enrollments',$target);$targetProgress=$db->setQuery('SELECT * FROM #__jlms_lesson_progress WHERE enrollment_id='.$target.' AND lesson_id='.$targetLesson)->loadObject();
migrationExpect($afterSource->status==='migrated'&&(int)$afterSource->revision===(int)$sourceBefore->revision+1,'source is marked migrated and its revision advances');
migrationExpect($beforeProgress===$afterProgress,'original lesson progress remains byte-for-byte unchanged');

migrationExpect((array)migrationRow('certificates',(int)$historyCertificate->id)===$certificateBefore,
    'explicit migration does not reassign or rewrite historical certificate ownership');
migrationExpect((array)migrationRow('certificate_documents',(int)$historyDocument->id)===$documentBefore,
    'issued document identity, source version snapshot and serial survive migration byte-for-byte');
migrationExpect((array)migrationRow('course_versions',$sourceVersion)===$versionBefore,
    'source published course version remains frozen after migration');
migrationExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_certificates WHERE enrollment_id='.(int)$target)->loadResult()===0,
    'destination version does not implicitly claim or reissue source certificate');

migrationExpect($targetRow->status==='completed'&&(int)$targetRow->completed_total===1&&(int)$targetRow->required_total===1,'target enrollment completion is recalculated from transferred required completions');
migrationExpect($targetProgress&&$targetProgress->state==='completed'&&(int)$targetProgress->covered_ms===0,'only completion state transfers, not video coverage');
$targetReplay=$service->apply($actor,$course,$enrollment,(int)$sourceBefore->revision,$mapping,'Learner requested transfer',$key,$plan['hash'],true);
migrationExpect($targetReplay===$target,'identical retry returns the same target enrollment');
migrationDenied(fn()=>$service->apply($actor,$course,$enrollment,(int)$sourceBefore->revision,$mapping,'Different reason',$key,$plan['hash'],true),409,'same idempotency scope with changed request conflicts');
migrationDenied(fn()=>$service->preview(new \Joomla\CMS\User\User(),$course,$enrollment,$mapping),401,'anonymous migration preview denied');
$jobId=(int)$db->setQuery('SELECT job_id FROM #__jlms_enrollment_migrations WHERE source_enrollment_id='.$enrollment)->loadResult();
migrationExpect((string)$db->setQuery('SELECT status FROM #__jlms_jobs WHERE id='.$jobId)->loadResult()==='succeeded','single migration is recorded as a completed immutable job');
file_put_contents($fixture,json_encode(['course'=>$course,'source'=>$enrollment,'target'=>$target,'source_version'=>$sourceVersion,'target_version'=>$version,'certificate'=>(int)$historyCertificate->id,
    'certificate_document'=>(int)$historyDocument->id,'certificate_source_hash'=>hash('sha256',json_encode([$certificateBefore,$documentBefore],JSON_THROW_ON_ERROR))],JSON_THROW_ON_ERROR));
echo json_encode(['enrollment_migration_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;
