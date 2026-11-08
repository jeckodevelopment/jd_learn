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
migrationExpect(count($plan['transfers'])===1&&$plan['transfers'][0]['target_lesson_id']===$targetLesson,'preview maps only the explicit completed non-quiz lesson equivalence');
migrationExpect($plan['blocked']===null,'valid single enrollment migration is enabled');
$emptyPlan=$service->preview($actor,$course,$enrollment,[]);
migrationExpect($emptyPlan['transfers']===[]&&count($emptyPlan['unmapped'])===1,'unmapped completion stays only on the source version');
migrationDenied(fn()=>$service->preview($actor,$course,$enrollment,[$lesson=>999999]),422,'lesson IDs outside the destination version are rejected');
$beforeProgress=(array)$db->setQuery('SELECT * FROM #__jlms_lesson_progress WHERE enrollment_id='.$enrollment.' AND lesson_id='.$lesson)->loadObject();
$sourceBefore=migrationRow('enrollments',$enrollment);$key='2e6d0f21-8c37-4a42-b57a-185fe9d0c001';
migrationDenied(fn()=>$service->apply($actor,$course,$enrollment,(int)$sourceBefore->revision,$mapping,'Learner requested transfer',$key,str_repeat('0',64),true),409,'stale preview hash prevents any migration write');
migrationDenied(fn()=>$service->apply($actor,$course,$enrollment,(int)$sourceBefore->revision,['not-an-id'=>'5foo'],'Learner requested transfer','3e6d0f21-8c37-4a42-b57a-185fe9d0c001',$plan['hash'],true),400,'service rejects malformed mapping values without relying on controller validation');
$target=$service->apply($actor,$course,$enrollment,(int)$sourceBefore->revision,$mapping,'Learner requested transfer',$key,$plan['hash'],true);
$afterSource=migrationRow('enrollments',$enrollment);$afterProgress=(array)$db->setQuery('SELECT * FROM #__jlms_lesson_progress WHERE enrollment_id='.$enrollment.' AND lesson_id='.$lesson)->loadObject();
$targetRow=migrationRow('enrollments',$target);$targetProgress=$db->setQuery('SELECT * FROM #__jlms_lesson_progress WHERE enrollment_id='.$target.' AND lesson_id='.$targetLesson)->loadObject();
migrationExpect($afterSource->status==='migrated'&&(int)$afterSource->revision===(int)$sourceBefore->revision+1,'source is marked migrated and its revision advances');
migrationExpect($beforeProgress===$afterProgress,'original lesson progress remains byte-for-byte unchanged');
migrationExpect($targetRow->status==='completed'&&(int)$targetRow->completed_total===1&&(int)$targetRow->required_total===1,'target enrollment completion is recalculated from transferred required completions');
migrationExpect($targetProgress&&$targetProgress->state==='completed'&&(int)$targetProgress->covered_ms===0,'only completion state transfers, not video coverage');
$targetReplay=$service->apply($actor,$course,$enrollment,(int)$sourceBefore->revision,$mapping,'Learner requested transfer',$key,$plan['hash'],true);
migrationExpect($targetReplay===$target,'identical retry returns the same target enrollment');
migrationDenied(fn()=>$service->apply($actor,$course,$enrollment,(int)$sourceBefore->revision,$mapping,'Different reason',$key,$plan['hash'],true),409,'same idempotency scope with changed request conflicts');
migrationDenied(fn()=>$service->preview(new \Joomla\CMS\User\User(),$course,$enrollment,$mapping),401,'anonymous migration preview denied');
$jobId=(int)$db->setQuery('SELECT job_id FROM #__jlms_enrollment_migrations WHERE source_enrollment_id='.$enrollment)->loadResult();
migrationExpect((string)$db->setQuery('SELECT status FROM #__jlms_jobs WHERE id='.$jobId)->loadResult()==='succeeded','single migration is recorded as a completed immutable job');
file_put_contents($fixture,json_encode(['course'=>$course,'source'=>$enrollment,'target'=>$target,'source_version'=>$sourceVersion,'target_version'=>$version],JSON_THROW_ON_ERROR));
echo json_encode(['enrollment_migration_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;
