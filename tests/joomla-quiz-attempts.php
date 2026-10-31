<?php

declare(strict_types=1);


/** Run after Joomla's CLI installer and extension:install in an isolated CI site. */
$root = realpath($argv[1] ?? '');
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new \RuntimeException('Disposable Joomla site required.');
}
// Set fixture plugin state before Joomla caches its enabled-plugin list.
// The retained-data mode must not alter state, so it can detect upgrade regressions.
if (($argv[2] ?? '') !== 'retained') {
    $fixtureDb = new \PDO(getenv('JLMS_TEST_DSN'), getenv('JLMS_TEST_USER'), getenv('JLMS_TEST_PASSWORD'), [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    $fixtureDb->exec("UPDATE joomla_extensions SET enabled=1 WHERE type='plugin' AND (folder='jlmsvideo' OR (folder='task' AND element='jlms'))");
    $fixtureDb->exec("UPDATE joomla_extensions SET params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.mail_to_user',0) WHERE type='plugin' AND folder='user' AND element='joomla'");
    $fixtureDb->exec("UPDATE joomla_extensions SET params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.playback_enabled',1) WHERE type='plugin' AND folder='jlmsvideo' AND element IN ('youtube','vimeo')");
    $fixtureDb->exec("UPDATE joomla_extensions SET params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.video_external_policy','click') WHERE type='component' AND element='com_jlms'");
    unset($fixtureDb);
}

// Simulate the web request context required by AdministratorApplication in a CLI smoke test.
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/administrator/index.php?option=com_jlms';
$_SERVER['SCRIPT_NAME'] = '/administrator/index.php';
$_SERVER['PHP_SELF'] = '/administrator/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/administrator/index.php';
\define('_JEXEC', 1);
\define('JPATH_BASE', $root . '/administrator');
require $root . '/administrator/includes/defines.php';
require $root . '/administrator/includes/framework.php';
\define('JPATH_COMPONENT', JPATH_ADMINISTRATOR . '/components/com_jlms');
\define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_COMPONENT);
\define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/com_jlms');

use Joomla\CMS\Factory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\VideoProviderRegistry;

$container = Factory::getContainer();
$container->alias('session.web', 'session.web.administrator')
    ->alias('session', 'session.web.administrator')
    ->alias(Session::class, 'session.web.administrator')
    ->alias(SessionInterface::class, 'session.web.administrator');
$app = $container->get(AdministratorApplication::class);
Factory::$application = $app;
$app->loadLanguage($container->get(\Joomla\CMS\Language\LanguageFactoryInterface::class)->createLanguage('en-GB', false));
$app->createExtensionNamespaceMap();
$db = $container->get(DatabaseInterface::class);
$userId = (int) $db->setQuery("SELECT id FROM #__users WHERE username = 'cioperator'")->loadResult();
if (!$userId) {
    throw new \RuntimeException('Test Joomla operator missing.');
}
$app->loadIdentity($container->get(UserFactoryInterface::class)->loadUserById($userId));
$component = $app->bootComponent('com_jlms');
$factory = $component->getMVCFactory();




$checks=0;
function attemptExpect(bool $ok,string $message):void{global$checks;if(!$ok)throw new \RuntimeException($message);$checks++;}
function attemptReject(callable $call,int $code,string $message):void{try{$call();}catch(\RuntimeException $error){attemptExpect($error->getCode()===$code,$message.': '.$error->getMessage().' / '.$error->getCode());return;}throw new \RuntimeException($message);}
function attemptKey():string{$s=bin2hex(random_bytes(16));return substr($s,0,8).'-'.substr($s,8,4).'-4'.substr($s,13,3).'-a'.substr($s,17,3).'-'.substr($s,20);}
function attemptAnswers(array $dto,bool $correct=true):string{
    $a=[];foreach($dto['questions']as$q)$a[$q['id']]=$correct?($q['kind']==='multiple'?['first','second']:['first']):[];
    return json_encode($a,JSON_THROW_ON_ERROR);
}
$learning=$component->getLearningService();$authoring=$component->getCurriculumService();$publication=$component->getPublicationService();$quizzes=$component->getQuizAuthoringService();
$actor=$app->getIdentity();$student=new \Joomla\CMS\User\User((int)$db->setQuery("SELECT id FROM #__users WHERE username='cilearner'")->loadResult());
$other=new \Joomla\CMS\User\User((int)$db->setQuery("SELECT id FROM #__users WHERE username='cilearnerother'")->loadResult());
$now=time();$service=new \Jlms\Component\Jlms\Administrator\Application\QuizAttemptService($db,$learning,$component->getQuizSnapshotService(),static function()use(&$now):int{return $now;},$component->getQuizTimeService());
$fixturePath=dirname($root).'/quiz-attempts-fixture.json';
if(($argv[2]??'')==='worker'){
    echo "ready\n";fflush(STDOUT);fgets(STDIN);
    $f=json_decode(file_get_contents($fixturePath),true,16,JSON_THROW_ON_ERROR);
    $workerUser=new \Joomla\CMS\User\User($f['concurrent_user']);$r=$service->start($workerUser,'en-GB',$f['concurrent_enrollment'],$f['lesson_id'],attemptKey());echo $r['attempt']['id'].PHP_EOL;exit(0);
}
if(($argv[2]??'')==='retained'){
    $f=json_decode(file_get_contents($fixturePath),true,16,JSON_THROW_ON_ERROR);$r=$service->state($student,'en-GB',$f['enrollment_id'],$f['lesson_id']);
    attemptExpect($r['course_completed']&&$r['best']['score_points']===$r['best']['max_points'],'quiz completion and best score survive upgrade');
    attemptExpect(hash('sha256',json_encode($db->setQuery('SELECT * FROM #__jlms_attempt_questions WHERE attempt_id='.(int)$f['passed_attempt'].' ORDER BY id')->loadAssocList(),JSON_THROW_ON_ERROR))===$f['snapshot_hash'],'public and private attempt snapshots and grades survive upgrade');
    attemptExpect((int)$db->setQuery("SELECT enabled FROM #__extensions WHERE folder='task' AND element='jlms'")->loadResult()===1,'task plugin enablement survives upgrade');
    echo json_encode(['quiz_runtime_upgrade'=>'passed','checks'=>$checks]).PHP_EOL;exit(0);
}
$bank=json_decode(file_get_contents(dirname($root).'/quiz-authoring-fixture.json'),true,16,JSON_THROW_ON_ERROR);
$level=(int)$db->setQuery("SELECT id FROM #__viewlevels WHERE title='CI learning access'")->loadResult();
$group=(int)$db->setQuery("SELECT id FROM #__usergroups WHERE title='CI learning entitlement'")->loadResult();
$catid=(int)$db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
if(!$model->save(['id'=>0,'revision'=>1,'title'=>'Quiz runtime fixture','alias'=>'quiz-runtime-fixture','catid'=>$catid,'language'=>'*','catalog_access'=>1,'content_access'=>$level,'enrollment_mode'=>'auto','summary'=>'Quiz runtime']))throw new \RuntimeException($model->getError());
$course=(int)$model->getState('course.id');$version=$authoring->createDraft($actor,$course,1);
$scope=static fn():array=>['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$version)->loadResult()];
$module=$authoring->saveModule($actor,$scope()+['id'=>0,'title'=>'Quiz runtime chapter']);
$base=['lesson_id'=>0,'revision'=>1,'module_id'=>$module,'title'=>'Graded quiz','required'=>'1','assessment'=>'graded','threshold'=>'60.01','max_attempts'=>'3','time_minutes'=>'1','retry_minutes'=>'1','selection_mode'=>'random','draw_count'=>'2','shuffle_questions'=>'1','shuffle_options'=>'1','feedback_policy'=>'pass_or_exhausted','questions'=>[['revision_id'=>$bank['first_revision'],'points'=>'3'],['revision_id'=>$bank['other_revision'],'points'=>'2']]];
$lesson=$quizzes->save($actor,$scope()+$base);
$practice=$quizzes->save($actor,$scope()+array_replace($base,['title'=>'Practice quiz','required'=>'0','assessment'=>'practice','threshold'=>'0','feedback_policy'=>'after_submit','max_attempts'=>'1','retry_minutes'=>'0']));
$never=$quizzes->save($actor,$scope()+array_replace($base,['title'=>'Hidden feedback quiz','required'=>'0','feedback_policy'=>'never','max_attempts'=>'1','retry_minutes'=>'0']));
$tfQuestion=$component->getQuestionBankService()->save($actor,['id'=>0,'revision'=>1,'catid'=>$bank['category_id'],'language'=>'*','state'=>'1','kind'=>'true_false','prompt'=>'True or false?','options'=>[['option_id'=>'first','label'=>'True','correct'=>'1'],['option_id'=>'second','label'=>'False','correct'=>'0']],'default_points'=>'7','explanation'=>'True.']);
$tfRevision=(int)$db->setQuery('SELECT current_revision_id FROM #__jlms_questions WHERE id='.$tfQuestion)->loadResult();
$tfLesson=$quizzes->save($actor,$scope()+array_replace($base,['title'=>'True false exercise','required'=>'0','assessment'=>'practice','feedback_policy'=>'after_submit','max_attempts'=>'1','retry_minutes'=>'0','selection_mode'=>'fixed','questions'=>[['revision_id'=>$tfRevision,'points'=>'7']]]));
$subset=$quizzes->save($actor,$scope()+array_replace($base,['title'=>'Subset quiz','required'=>'0','max_attempts'=>'0','retry_minutes'=>'0','questions'=>[...$base['questions'],['revision_id'=>$tfRevision,'points'=>'7']]]));
$publication->transition($actor,$course,$version,(int)$db->setQuery('SELECT revision FROM #__jlms_courses WHERE id='.$course)->loadResult(),$scope()['expected_version_revision'],(int)$publication->transitions($actor,$course)[0]->id);
attemptExpect($db->setQuery('SELECT status FROM #__jlms_course_versions WHERE id='.$version)->loadResult()==='published','valid quiz publishes through native workflow');
attemptReject(fn()=>$quizzes->save($actor,$scope()+array_replace($base,['lesson_id'=>$lesson])),409,'published quiz immutable');
$enrollment=$learning->start($student,'en-GB',$course,bin2hex(random_bytes(16)));
attemptReject(fn()=>$learning->complete($student,'en-GB',$enrollment,$lesson,bin2hex(random_bytes(16))),403,'manual completion cannot bypass quiz');
$pre=$service->state($student,'en-GB',$enrollment,$lesson);attemptExpect($pre['can_start']&&$pre['questions']===[]&&$pre['used_attempts']===0,'reading before start does not consume attempt or expose questions');
attemptReject(fn()=>$service->start($other,'en-GB',$enrollment,$lesson,attemptKey()),404,'foreign enrollment rejected');
attemptReject(fn()=>$service->start(new \Joomla\CMS\User\User(),'en-GB',$enrollment,$lesson,attemptKey()),401,'guest rejected');
$key=attemptKey();$a=$service->start($student,'en-GB',$enrollment,$lesson,$key);$aid=$a['attempt']['id'];
attemptExpect(count($a['questions'])===2&&$a['attempt']['max_points']===5&&$a['attempt']['deadline']===$now+60,'server snapshot and timer');
attemptExpect(!str_contains(json_encode($a,JSON_THROW_ON_ERROR),'"correct"')&&!str_contains(json_encode($a,JSON_THROW_ON_ERROR),'PRIVATE_CORRECT_EXPLANATION'),'open DTO never leaks grading or explanations');
attemptExpect($service->start($student,'en-GB',$enrollment,$lesson,$key)['attempt']['id']===$aid&&$service->start($student,'en-GB',$enrollment,$lesson,attemptKey())['used_attempts']===1,'key replay and new tab share one active attempt');
attemptReject(fn()=>$service->start($student,'en-GB',$enrollment,$practice,$key),409,'start key is bound to its lesson and enrollment');
attemptExpect($service->state($student,'en-GB',$enrollment,$lesson)['questions']===$a['questions'],'draw and order stable on resume');
$mut=attemptKey();$answers=attemptAnswers($a);$save=$service->mutate($student,'en-GB',$enrollment,$lesson,$aid,1,$mut,$answers,false);
attemptExpect($save['attempt']['revision']===2&&$save['questions']===[]&&$service->state($student,'en-GB',$enrollment,$lesson)['questions'][0]['selected']!==[],'answers saved with CAS revision');
attemptExpect($service->mutate($student,'en-GB',$enrollment,$lesson,$aid,1,$mut,$answers,false)['replay'],'identical save replay');
attemptReject(fn()=>$service->mutate($student,'en-GB',$enrollment,$lesson,$aid,1,$mut,'{}',false),409,'same mutation key different body rejected');
attemptReject(fn()=>$service->mutate($student,'en-GB',$enrollment,$lesson,$aid,1,attemptKey(),'{}',false),409,'stale tab cannot overwrite answers');
attemptReject(fn()=>$service->mutate($student,'en-GB',$enrollment,$lesson,$aid,2,attemptKey(),'{"999999":["first"]}',false),422,'foreign snapshot question rejected');
$qid=$a['questions'][0]['id'];attemptReject(fn()=>$service->mutate($student,'en-GB',$enrollment,$lesson,$aid,2,attemptKey(),json_encode([$qid=>[['bad']]],JSON_THROW_ON_ERROR),false),422,'nested option value rejected without warning');
$student->getAuthorisedViewLevels();\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$student->id,$group);
attemptReject(fn()=>$service->mutate($student,'en-GB',$enrollment,$lesson,$aid,1,$mut,$answers,false),403,'current access checked even on save replay');
attemptReject(fn()=>$service->start($student,'en-GB',$enrollment,$lesson,$key),403,'current access checked on start replay');
\Joomla\CMS\User\UserHelper::addUserToGroup((int)$student->id,$group);
$partial=[];foreach($a['questions']as$q)$partial[$q['id']]=['first'];
$failed=$service->mutate($student,'en-GB',$enrollment,$lesson,$aid,2,attemptKey(),json_encode($partial,JSON_THROW_ON_ERROR),true);
attemptExpect($failed['attempt']['score_points']===3&&!$failed['attempt']['passed']&&!$failed['feedback_available']&&!$failed['course_completed'],'60 percent fails exact 60.01 threshold; partial multiple selection earns zero and feedback stays closed');
attemptReject(fn()=>$service->start($student,'en-GB',$enrollment,$lesson,attemptKey()),409,'retry interval enforced');
$now+=61;$a=$service->start($student,'en-GB',$enrollment,$lesson,attemptKey());$submit=attemptKey();$body=attemptAnswers($a);
$passed=$service->mutate($student,'en-GB',$enrollment,$lesson,$a['attempt']['id'],1,$submit,$body,true);
attemptExpect($passed['attempt']['passed']&&$passed['course_completed']&&$passed['course_percent']===100&&$passed['feedback_available'],'exact set grading passes and completes course');
attemptExpect($service->mutate($student,'en-GB',$enrollment,$lesson,$a['attempt']['id'],1,$submit,$body,true)['replay'],'final submit idempotent');
attemptExpect((int)$db->setQuery('SELECT completed_total FROM #__jlms_enrollments WHERE id='.$enrollment)->loadResult()===1,'completion counts once');
$passedAttempt=$a['attempt']['id'];$completedAt=$db->setQuery('SELECT completed_at FROM #__jlms_enrollments WHERE id='.$enrollment)->loadResult();
$now+=61;$a=$service->start($student,'en-GB',$enrollment,$lesson,attemptKey());
attemptExpect(!$service->state($student,'en-GB',$enrollment,$lesson,$passedAttempt)['feedback_available'],'no historical answer key while another attempt is active');
$now+=60;$expired=$service->mutate($student,'en-GB',$enrollment,$lesson,$a['attempt']['id'],1,attemptKey(),attemptAnswers($a),true);
attemptExpect($expired['attempt']['state']==='expired'&&$expired['attempt']['score_points']===0&&$expired['best']['score_points']===5,'late answers ignored and best score retained');
attemptExpect($expired['feedback_available']&&$expired['remaining_attempts']===0,'feedback after finite exhaustion');
attemptReject(fn()=>$service->start($student,'en-GB',$enrollment,$lesson,attemptKey()),409,'attempt limit enforced');
attemptExpect($db->setQuery('SELECT completed_at FROM #__jlms_enrollments WHERE id='.$enrollment)->loadResult()===$completedAt,'first course completion timestamp immutable');
$a=$service->start($student,'en-GB',$enrollment,$practice,attemptKey());$p=$service->mutate($student,'en-GB',$enrollment,$practice,$a['attempt']['id'],1,attemptKey(),'{}',true);
attemptExpect($p['feedback_available']&&$p['attempt']['score_points']===0,'practice immediate feedback without requiring correct answers');
$a=$service->start($student,'en-GB',$enrollment,$never,attemptKey());$p=$service->mutate($student,'en-GB',$enrollment,$never,$a['attempt']['id'],1,attemptKey(),attemptAnswers($a),true);
attemptExpect(!$p['feedback_available']&&$p['attempt']['passed'],'never policy withholds feedback even after pass/exhaustion');
$a=$service->start($student,'en-GB',$enrollment,$tfLesson,attemptKey());
$p=$service->mutate($student,'en-GB',$enrollment,$tfLesson,$a['attempt']['id'],1,attemptKey(),attemptAnswers($a),true);
attemptExpect($p['attempt']['score_points']===7&&$p['attempt']['passed'],'true false is graded through immutable runtime snapshot');
$a=$service->start($student,'en-GB',$enrollment,$subset,attemptKey());
attemptExpect(count($a['questions'])===2&&$service->state($student,'en-GB',$enrollment,$subset)['questions']===$a['questions'],'proper subset extraction is stable on resume');
$p=$service->mutate($student,'en-GB',$enrollment,$subset,$a['attempt']['id'],1,attemptKey(),'{}',true);
attemptExpect(!$p['feedback_available']&&$p['remaining_attempts']===null,'unlimited failed attempts never exhaust into feedback');
// Validation is scoped to this attempt's immutable public option identifiers.
$validation=$service->start($student,'en-GB',$enrollment,$subset,attemptKey());$validationId=$validation['attempt']['id'];
$firstQ=$validation['questions'][0];
attemptReject(fn()=>$service->mutate($student,'en-GB',$enrollment,$subset,$validationId,1,attemptKey(),json_encode([$firstQ['id']=>['first','first']],JSON_THROW_ON_ERROR),false),422,'duplicate option identifiers rejected');
attemptReject(fn()=>$service->mutate($student,'en-GB',$enrollment,$subset,$validationId,1,attemptKey(),'null',false),422,'non-map answer document rejected');
attemptReject(fn()=>$service->mutate($student,'en-GB',$enrollment,$subset,$validationId,1,attemptKey(),str_repeat('x',524289),false),413,'answer payload bound enforced before decoding');
attemptReject(fn()=>$service->mutate($student,'en-GB',$enrollment,$practice,$validationId,1,attemptKey(),'{}',true),404,'attempt from another quiz cannot be submitted through this lesson');
$service->mutate($student,'en-GB',$enrollment,$subset,$validationId,1,attemptKey(),'{}',true);
// Separate user for timer grace and native scheduler execution; no test mutates a customer's site.
$otherEnrollment=$learning->start($other,'en-GB',$course,bin2hex(random_bytes(16)));$a=$service->start($other,'en-GB',$otherEnrollment,$lesson,attemptKey());
$r=$service->mutate($other,'en-GB',$otherEnrollment,$lesson,$a['attempt']['id'],1,attemptKey(),attemptAnswers($a),false);
$u=(object)['id'=>$otherEnrollment,'ends_at'=>gmdate('Y-m-d H:i:s',time()-1)];$db->updateObject('#__jlms_enrollments',$u,'id');
attemptExpect($service->state($other,'en-GB',$otherEnrollment,$lesson)['attempt']['state']==='open','existing attempt may finish after educational window ends');
attemptReject(fn()=>$service->start($other,'en-GB',$otherEnrollment,$practice,attemptKey()),403,'window grace does not allow new attempts');
// A worker finishes only saved answers even when the current account has been blocked.
$u=(object)['id'=>(int)$other->id,'block'=>1];$db->updateObject('#__users',$u,'id');$now+=60;
$run=$service->expireDue(100);attemptExpect($run['closed']===1&&$run['failed']===0,'bounded worker expires saved answers for blocked account');
attemptExpect($service->expireDue(100)['closed']===0,'duplicate expiry worker does not count twice');
$u->block=0;$db->updateObject('#__users',$u,'id');
$u=(object)['id'=>$otherEnrollment,'ends_at'=>null];$db->updateObject('#__jlms_enrollments',$u,'id',true);
// Execute the distributed routine through Joomla's native event and Task object.
$a=$service->start($other,'en-GB',$otherEnrollment,$practice,attemptKey());
$u=(object)['id'=>$a['attempt']['id'],'deadline_at'=>gmdate('Y-m-d H:i:s',time()-1)];$db->updateObject('#__jlms_attempts',$u,'id');
$app->bootComponent('com_scheduler');
$options=\Joomla\Component\Scheduler\Administrator\Helper\SchedulerHelper::getTaskOptions();
$option=$options->findOption('jlms.quiz.expire');attemptExpect($option!==null,'native Scheduler discovers the base-package routine');
$record=(object)['id'=>0,'type'=>'jlms.quiz.expire','params'=>'{"limit":100}','taskOption'=>$option];
$task=new \Joomla\Component\Scheduler\Administrator\Task\Task($record);
$event=new \Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent('onExecuteTask',['subject'=>$task,'params'=>(object)['limit'=>100]]);
$app->getDispatcher()->dispatch('onExecuteTask',$event);
attemptExpect(($event->getResultSnapshot()['status']??null)===\Joomla\Component\Scheduler\Administrator\Task\Status::OK,'native task event executes the plugin successfully');
attemptExpect($db->setQuery('SELECT status FROM #__jlms_attempts WHERE id='.(int)$a['attempt']['id'])->loadResult()==='expired','scheduled routine closes due attempt without browser');
$httpUser=new \Joomla\CMS\User\User((int)$db->setQuery("SELECT id FROM #__users WHERE username='cilearnerhttp'")->loadResult());
$httpEnrollment=$learning->start($httpUser,'en-GB',$course,bin2hex(random_bytes(16)));
// Concurrency uses one enrollment and two independent database connections/processes.
$concurrentUser=new \Joomla\CMS\User\User();
$userData=['name'=>'CI Concurrent quiz','username'=>'ciquizconcurrent','email'=>'ciquizconcurrent@example.invalid','password'=>'Ci-only-Learner-Password-2026','password2'=>'Ci-only-Learner-Password-2026','groups'=>[2,$group],'block'=>0,'activation'=>''];
if(!$concurrentUser->bind($userData)||!$concurrentUser->save())throw new \RuntimeException($concurrentUser->getError());
$concurrentUser=new \Joomla\CMS\User\User((int)$concurrentUser->id);
$concurrentEnrollment=$learning->start($concurrentUser,'en-GB',$course,bin2hex(random_bytes(16)));
$f=['course_id'=>$course,'lesson_id'=>$lesson,'practice_id'=>$practice,'never_id'=>$never,'enrollment_id'=>$enrollment,'http_enrollment'=>$httpEnrollment,'passed_attempt'=>$passedAttempt,'concurrent_user'=>(int)$concurrentUser->id,'concurrent_enrollment'=>$concurrentEnrollment];
$snapshotHash=hash('sha256',json_encode($db->setQuery('SELECT * FROM #__jlms_attempt_questions WHERE attempt_id='.$passedAttempt.' ORDER BY id')->loadAssocList(),JSON_THROW_ON_ERROR));$f['snapshot_hash']=$snapshotHash;
file_put_contents($fixturePath,json_encode($f,JSON_THROW_ON_ERROR));
$workers=[];
for($i=0;$i<2;$i++){$pipes=[];$process=proc_open([PHP_BINARY,__FILE__,$root,'worker'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($process))throw new \RuntimeException('Worker not started');$workers[]=[$process,$pipes];}
foreach($workers as[$process,$pipes])if(trim((string)fgets($pipes[1]))!=='ready')throw new \RuntimeException('Worker barrier failed');
$db->transactionStart();$db->setQuery('SELECT id FROM #__jlms_enrollments WHERE id='.$concurrentEnrollment.' FOR UPDATE')->loadResult();
foreach($workers as[$process,$pipes]){fwrite($pipes[0],"go\n");fclose($pipes[0]);}
usleep(200000);$db->transactionCommit();
$ids=[];foreach($workers as[$process,$pipes]){$out=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($process)!==0)throw new \RuntimeException('Worker failure '.$err.$out);$ids[]=$out;}
attemptExpect($ids[0]===$ids[1]&&(int)$ids[0]>0,'concurrent starts allocate one attempt');
attemptExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_active_attempts WHERE enrollment_id='.$concurrentEnrollment)->loadResult()===1,'one unique active slot after concurrent starts');
echo json_encode(['quiz_runtime_checks' =>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;
