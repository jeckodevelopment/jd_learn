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
function deadlineExpect(bool $ok,string $message):void{global$checks;if(!$ok)throw new \RuntimeException($message);$checks++;}
function deadlineReject(callable $work,int $code,string $message):void{try{$work();}catch(\RuntimeException $error){deadlineExpect($error->getCode()===$code,$message.': '.$error->getMessage().' / '.$error->getCode());return;}throw new \RuntimeException($message);}
function deadlineKey():string{$s=bin2hex(random_bytes(16));return substr($s,0,8).'-'.substr($s,8,4).'-4'.substr($s,13,3).'-a'.substr($s,17,3).'-'.substr($s,20);}
function deadlineUser(string $name,int $group):\Joomla\CMS\User\User{$u=new \Joomla\CMS\User\User();$d=['name'=>'CI '.$name,'username'=>$name,'email'=>$name.'@example.invalid','password'=>'Ci-only-Learner-Password-2026','password2'=>'Ci-only-Learner-Password-2026','groups'=>[2,$group],'block'=>0,'activation'=>''];if(!$u->bind($d)||!$u->save())throw new \RuntimeException((string)$u->getError());return new \Joomla\CMS\User\User((int)$u->id);}
function deadlineCommand(array $i,string $reason='PRIVATE_DEADLINE_REASON'):array{return [$i['course_id'],$i['lesson_id'],(int)$i['enrollment']->id,(int)$i['attempt']->id,$i['impact']['enrollment_revision'],$i['impact']['attempt_revision'],$i['minutes'],$reason,deadlineKey(),$i['impact']['hash'],true];}
$actor=$app->getIdentity();$learning=$component->getLearningService();$now=time();
$audit=static function(\Joomla\CMS\User\User $actor,array $message)use($db):void{(new \Jlms\Component\Jlms\Administrator\Infrastructure\AssessmentAudit($db))->record($actor,$message,'quizextend');};
$deadline=new \Jlms\Component\Jlms\Administrator\Application\QuizDeadlineService($db,$component->getQuizTimeService(),$audit,static function()use(&$now):int{return $now;});
$runtime=new \Jlms\Component\Jlms\Administrator\Application\QuizAttemptService($db,$learning,$component->getQuizSnapshotService(),static function()use(&$now):int{return $now;},$component->getQuizTimeService());
$f=json_decode(file_get_contents(dirname($root).'/quiz-attempts-fixture.json'),true,16,JSON_THROW_ON_ERROR);$course=$f['course_id'];$lesson=$f['lesson_id'];$path=dirname($root).'/quiz-deadline-fixture.json';
if(($argv[2]??'')==='worker'){
 echo "ready\n";fflush(STDOUT);fgets(STDIN);$race=json_decode(file_get_contents(dirname($root).'/quiz-deadline-race.json'),true,16,JSON_THROW_ON_ERROR);$now=$race['clock'];
 try{if(($argv[3]??'')==='expire'){$runtime->expireDue(100);echo "expired\n";}
 else{$command=$race['command'];if(($argv[3]??'')==='extend2')$command[8]=deadlineKey();$deadline->extend($actor,...$command);echo "ok\n";}}
 catch(\RuntimeException $error){if($error->getCode()!==409)throw $error;echo "conflict\n";}exit(0);
}
if(($argv[2]??'')==='retained'){
 $t=json_decode(file_get_contents($path),true,16,JSON_THROW_ON_ERROR);$i=$deadline->editor($actor,$course,$lesson,$t['enrollment_id'],$t['attempt_id']);
 deadlineExpect(count($i['history'])===2,'append-only deadline history survives upgrade');
 $raw=$db->setQuery('SELECT rules_snapshot_json,deadline_at FROM #__jlms_attempts WHERE id='.(int)$t['attempt_id'])->loadObject();deadlineExpect(hash('sha256',json_encode($raw,JSON_THROW_ON_ERROR))===$t['snapshot_hash'],'frozen rules and effective deadline survive upgrade');
 deadlineExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__action_logs WHERE extension='com_jlms.quizextend' AND item_id=".(int)$t['extension_id'])->loadResult()===1,'native extension audit survives upgrade');
 echo json_encode(['quiz_deadline_upgrade_checks'=>$checks,'status'=>'passed']).PHP_EOL;exit(0);
}
$group=(int)$db->setQuery("SELECT id FROM #__usergroups WHERE title='CI learning entitlement'")->loadResult();$u=deadlineUser('ciquizdeadline',$group);$enrollment=$learning->start($u,'en-GB',$course,bin2hex(random_bytes(16)));
$a=$runtime->start($u,'en-GB',$enrollment,$lesson,deadlineKey());$attempt=$a['attempt']['id'];$original=$db->setQuery('SELECT rules_snapshot_json,deadline_at,revision FROM #__jlms_attempts WHERE id='.$attempt)->loadObject();
$cancelBefore=$component->getQuizReviewService()->editor($actor,$course,$lesson,$enrollment,$attempt,false);
$i=$deadline->editor($actor,$course,$lesson,$enrollment,$attempt,2);$command=deadlineCommand($i);
deadlineExpect($i['impact']['new_deadline']===$a['attempt']['deadline']+120&&$i['impact']['blocked']===null,'preview adds to current deadline');
deadlineReject(fn()=>$deadline->editor($u,$course,$lesson,$enrollment,$attempt),403,'learner cannot read manager preview');
deadlineReject(fn()=>$deadline->extend($u,...$command),403,'learner cannot extend own deadline');
$bad=$command;$bad[10]=false;deadlineReject(fn()=>$deadline->extend($actor,...$bad),400,'explicit confirmation required');
$bad=$command;$bad[7]='';deadlineReject(fn()=>$deadline->extend($actor,...$bad),422,'operational reason required');
$bad=$command;$bad[6]=1441;deadlineReject(fn()=>$deadline->extend($actor,...$bad),400,'per-operation duration bound enforced');
$id=$deadline->extend($actor,...$command);deadlineExpect($deadline->extend($actor,...$command)===$id,'same UUID replays without adding time twice');
$bad=$command;$bad[7]='Changed reason';deadlineReject(fn()=>$deadline->extend($actor,...$bad),409,'changed replay body rejected');
$r=$runtime->state($u,'en-GB',$enrollment,$lesson);deadlineExpect($r['time_seconds']===180&&$r['original_time_seconds']===60&&$r['extension_seconds']===120&&$r['attempt']['revision']===1,'effective time changes without changing answer CAS revision');
deadlineExpect(!str_contains(json_encode($r,JSON_THROW_ON_ERROR),'PRIVATE_DEADLINE_REASON'),'learner never receives internal extension reason');
$raw=$db->setQuery('SELECT rules_snapshot_json FROM #__jlms_attempts WHERE id='.$attempt)->loadResult();deadlineExpect($raw===$original->rules_snapshot_json,'frozen rules remain byte-for-byte unchanged');
$compact=$runtime->state($u,'en-GB',$enrollment,$lesson,$attempt,true);deadlineExpect($compact['questions']===[]&&$compact['attempt']['deadline']===$r['attempt']['deadline'],'compact synchronization carries the current deadline without question bodies');
$answers=[];foreach($a['questions']as$q)$answers[$q['id']]=$q['kind']==='multiple'?['first','second']:['first'];
$stale=deadlineCommand($deadline->editor($actor,$course,$lesson,$enrollment,$attempt,1));$runtime->mutate($u,'en-GB',$enrollment,$lesson,$attempt,1,deadlineKey(),json_encode($answers,JSON_THROW_ON_ERROR),false);
deadlineReject(fn()=>$deadline->extend($actor,...$stale),409,'concurrent answer save invalidates the administrative preview');
$now+=61;$run=$runtime->expireDue(100);deadlineExpect($runtime->state($u,'en-GB',$enrollment,$lesson)['attempt']['state']==='open','passing the original deadline does not close an extended attempt');
$i=$deadline->editor($actor,$course,$lesson,$enrollment,$attempt,1);$secondCommand=deadlineCommand($i);$deadline->extend($actor,...$secondCommand);
deadlineExpect($deadline->extend($actor,...$command)===$id,'old successful replay after a later extension remains a no-op');
$broken=new \Jlms\Component\Jlms\Administrator\Application\QuizDeadlineService($db,$component->getQuizTimeService(),static function():void{throw new \RuntimeException('COM_JLMS_QUIZ_EXTEND_AUDIT_FAILED',503);},static function()use(&$now):int{return $now;});
$fail=deadlineCommand($deadline->editor($actor,$course,$lesson,$enrollment,$attempt,1));deadlineReject(fn()=>$broken->extend($actor,...$fail),503,'audit failure rolls extension back');
deadlineExpect(count($deadline->editor($actor,$course,$lesson,$enrollment,$attempt)['history'])===2,'failed extension leaves no history row');
$bad=$fail;$bad[9]=str_repeat('0',64);deadlineReject(fn()=>$deadline->extend($actor,...$bad),409,'forged impact rejected');
$now=(int)$a['attempt']['started_at']+240;$expiry=$runtime->expireDue(100);$r=$runtime->state($u,'en-GB',$enrollment,$lesson);
deadlineExpect($r['attempt']['state']==='expired'&&$r['attempt']['score_points']===5&&$r['course_completed'],'effective deadline grades previously saved answers once');
deadlineReject(fn()=>$deadline->extend($actor,...$fail),409,'elapsed/closed attempt cannot be extended or reopened');
deadlineExpect($deadline->extend($actor,...$secondCommand)>0,'successful replay remains idempotent after closure');
// An elapsed but not yet materialized deadline is just as authoritative as a closed attempt.
$now+=61;$late=$runtime->start($u,'en-GB',$enrollment,$lesson,deadlineKey());$lateCommand=deadlineCommand($deadline->editor($actor,$course,$lesson,$enrollment,$late['attempt']['id'],1));$now+=60;
deadlineReject(fn()=>$deadline->extend($actor,...$lateCommand),409,'deadline boundary blocks extension even before worker closure');
// Verify native course denial and revocation on an already successful replay.
$managerGroup=(int)$db->setQuery("SELECT id FROM #__usergroups WHERE title='CI assessment time manager'")->loadResult();$managerId=(int)$db->setQuery("SELECT id FROM #__users WHERE username='ciquiztimemanager'")->loadResult();
\Joomla\CMS\User\UserHelper::addUserToGroup($managerId,$managerGroup);$manager=new \Joomla\CMS\User\User($managerId);
$now=time();$managed=$runtime->start($u,'en-GB',$enrollment,$f['practice_id'],deadlineKey());$mc=deadlineCommand($deadline->editor($manager,$course,$f['practice_id'],$enrollment,$managed['attempt']['id'],1));
$asset=new \Joomla\CMS\Table\Asset($db);$asset->load((int)$db->setQuery('SELECT asset_id FROM #__jlms_courses WHERE id='.$course)->loadResult());$oldRules=$asset->rules;$asset->rules=json_encode(['jlms.assessment.correct'=>[(string)$managerGroup=>false]],JSON_THROW_ON_ERROR);$asset->store();
deadlineReject(fn()=>$deadline->extend($manager,...$mc),403,'course-specific denial applies to direct extension');$asset->rules=$oldRules;$asset->store();
$deadline->extend($manager,...$mc);\Joomla\CMS\User\UserHelper::removeUserFromGroup($managerId,$managerGroup);
deadlineReject(fn()=>$deadline->extend($manager,...$mc),403,'successful extension replay reloads revoked manager permissions');
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$u->id,$group);deadlineReject(fn()=>$runtime->state($u,'en-GB',$enrollment,$f['practice_id']),403,'extension never bypasses Joomla access revocation');\Joomla\CMS\User\UserHelper::addUserToGroup((int)$u->id,$group);
// A submitted attempt is never reopened by a preview prepared while it was open.
$submittedUser=deadlineUser('ciquizsubmittedextend',$group);$submittedEnrollment=$learning->start($submittedUser,'en-GB',$course,bin2hex(random_bytes(16)));
$submitted=$runtime->start($submittedUser,'en-GB',$submittedEnrollment,$lesson,deadlineKey());$submittedCommand=deadlineCommand($deadline->editor($actor,$course,$lesson,$submittedEnrollment,$submitted['attempt']['id'],1));
$runtime->mutate($submittedUser,'en-GB',$submittedEnrollment,$lesson,$submitted['attempt']['id'],1,deadlineKey(),'{}',true);
deadlineReject(fn()=>$deadline->extend($actor,...$submittedCommand),409,'submission winning the lock prevents a later extension');
// Build a native, legitimately untimed published quiz rather than editing frozen database evidence.
$author=$component->getCurriculumService();$quizzes=$component->getQuizAuthoringService();$bank=json_decode(file_get_contents(dirname($root).'/quiz-authoring-fixture.json'),true,16,JSON_THROW_ON_ERROR);
$cat=(int)$db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();$level=(int)$db->setQuery("SELECT id FROM #__viewlevels WHERE title='CI learning access'")->loadResult();
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);if(!$model->save(['id'=>0,'revision'=>1,'title'=>'Untimed extension fixture','alias'=>'untimed-extension-fixture','catid'=>$cat,'language'=>'*','catalog_access'=>1,'content_access'=>$level,'enrollment_mode'=>'auto','summary'=>'Untimed guard']))throw new \RuntimeException($model->getError());
$uc=(int)$model->getState('course.id');$uv=$author->createDraft($actor,$uc,1);$scope=static fn():array=>['course_id'=>$uc,'version_id'=>$uv,'expected_version_revision'=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$uv)->loadResult()];
$um=$author->saveModule($actor,$scope()+['id'=>0,'title'=>'Untimed module']);$ul=$quizzes->save($actor,$scope()+['lesson_id'=>0,'revision'=>1,'module_id'=>$um,'title'=>'Untimed quiz','required'=>'1','assessment'=>'graded','threshold'=>'70','max_attempts'=>'1','time_minutes'=>'0','retry_minutes'=>'0','selection_mode'=>'fixed','shuffle_questions'=>'0','shuffle_options'=>'0','feedback_policy'=>'never','questions'=>[['revision_id'=>$bank['first_revision'],'points'=>'1']]]);
$publication=$component->getPublicationService();$publication->transition($actor,$uc,$uv,(int)$db->setQuery('SELECT revision FROM #__jlms_courses WHERE id='.$uc)->loadResult(),$scope()['expected_version_revision'],(int)$publication->transitions($actor,$uc)[0]->id);
$ue=$learning->start($u,'en-GB',$uc,bin2hex(random_bytes(16)));$ua=$runtime->start($u,'en-GB',$ue,$ul,deadlineKey());$ui=$deadline->editor($actor,$uc,$ul,$ue,$ua['attempt']['id'],1);
deadlineExpect($ui['impact']['blocked']==='COM_JLMS_QUIZ_EXTEND_UNTIMED','untimed attempts have no artificial extension deadline');
deadlineReject(fn()=>$deadline->extend($actor,...deadlineCommand($ui)),409,'untimed extension is rejected server-side');
// Two fresh users isolate concurrency tests from the sequential fixture's simulated clock.
$runRace=static function(string $name,bool $expiry)use($db,$root,$group,$learning,$runtime,$deadline,$actor,$course,$lesson,&$now):void{
 $now=time();$user=deadlineUser($name,$group);$e=$learning->start($user,'en-GB',$course,bin2hex(random_bytes(16)));$a=$runtime->start($user,'en-GB',$e,$lesson,deadlineKey());$cmd=deadlineCommand($deadline->editor($actor,$course,$lesson,$e,$a['attempt']['id'],1));
 $race=['command'=>$cmd,'clock'=>$expiry?$a['attempt']['deadline']:$now];file_put_contents(dirname($root).'/quiz-deadline-race.json',json_encode($race,JSON_THROW_ON_ERROR));$workers=[];
 foreach($expiry?['extend','expire']:['extend','extend2']as$mode){$pipes=[];$process=proc_open([PHP_BINARY,__FILE__,$root,'worker',$mode],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($process))throw new \RuntimeException('Worker failed');$workers[]=[$process,$pipes];}
 foreach($workers as[$process,$pipes]) {
    stream_set_timeout($pipes[1],30);
    $ready=trim((string)fgets($pipes[1]));
    if($ready!=='ready') {
        stream_set_blocking($pipes[1],false); stream_set_blocking($pipes[2],false);
        $detail=$ready.' '.stream_get_contents($pipes[1]).' '.stream_get_contents($pipes[2]);
        foreach($workers as[$child,$channels]) { proc_terminate($child); }
        throw new \RuntimeException('Worker barrier failed: '.$detail);
    }
}
 $db->transactionStart();$db->setQuery('SELECT id FROM #__jlms_enrollments WHERE id='.$e.' FOR UPDATE')->loadResult();foreach($workers as[$process,$pipes]){fwrite($pipes[0],"go\n");fclose($pipes[0]);}usleep(200000);$db->transactionCommit();$results=[];
 foreach($workers as[$process,$pipes]){$out=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($process)!==0)throw new \RuntimeException($err.$out);$results[]=$out;}sort($results);
 deadlineExpect($results===($expiry?['conflict','expired']:['conflict','ok']),$expiry?'expiry and extension cannot reopen an elapsed attempt':'two concurrent extensions from one preview add time once');
 $status=$db->setQuery('SELECT status FROM #__jlms_attempts WHERE id='.(int)$a['attempt']['id'])->loadResult();deadlineExpect($status===($expiry?'expired':'open'),'concurrent deadline operations leave consistent status');
};
$runRace('ciquizextendrace',false);$runRace('ciquizexpirerace',true);
// Native HTTP uses a separate enrollment, preserving previous fixtures.
$http=new \Joomla\CMS\User\User((int)$db->setQuery("SELECT id FROM #__users WHERE username='cilearnerhttp'")->loadResult());$httpEnrollment=$f['http_enrollment'];
$now=time();$httpA=$runtime->start($http,'en-GB',$httpEnrollment,$f['practice_id'],deadlineKey());
$raw=$db->setQuery('SELECT rules_snapshot_json,deadline_at FROM #__jlms_attempts WHERE id='.$attempt)->loadObject();
file_put_contents($path,json_encode(['course_id'=>$course,'lesson_id'=>$lesson,'enrollment_id'=>$enrollment,'attempt_id'=>$attempt,'extension_id'=>$id,'snapshot_hash'=>hash('sha256',json_encode($raw,JSON_THROW_ON_ERROR)),
 'http_enrollment'=>$httpEnrollment,'http_lesson'=>$f['practice_id'],'http_attempt'=>$httpA['attempt']['id']],JSON_THROW_ON_ERROR));
echo json_encode(['quiz_deadline_checks'=>$checks,'status'=>'passed']).PHP_EOL;
