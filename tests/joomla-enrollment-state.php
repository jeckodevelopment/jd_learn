<?php

declare(strict_types=1);


/** Run after Joomla's CLI installer and extension:install in an isolated CI site. */
$root = realpath($argv[1] ?? '');
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new \RuntimeException('Disposable Joomla site required.');
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

$actor=$app->getIdentity();$service=$component->getEnrollmentStateService();$learning=$component->getLearningService();$checks=0;
function stateExpect(bool $ok,string $message):void{global$checks;if(!$ok)throw new \RuntimeException($message);$checks++;}
function stateDenied(callable $call,int $code,string $message):void{try{$call();}catch(\RuntimeException $e){stateExpect($e->getCode()===$code,$message.': '.$e->getMessage());return;}throw new \RuntimeException($message);}
function stateKey():string{$k=bin2hex(random_bytes(16));return substr($k,0,8).'-'.substr($k,8,4).'-4'.substr($k,13,3).'-a'.substr($k,17,3).'-'.substr($k,20);}
function stateCommand(array $i,string $reason='PRIVATE_STATE_REASON'):array{return [(int)$i['course']->id,$i['enrollment_id'],$i['impact']['enrollment_revision'],$i['impact']['target_status'],$reason,stateKey(),$i['impact']['hash'],true];}
$path=dirname($root).'/enrollment-state-fixture.json';
if(($argv[2]??'')==='worker'){
 echo "ready\n";fflush(STDOUT);fgets(STDIN);$command=json_decode(file_get_contents(dirname($root).'/enrollment-state-race.json'),true,16,JSON_THROW_ON_ERROR);
 if(($argv[3]??'')==='different')$command[5]=stateKey();
 try{$id=$service->apply($actor,...$command);echo 'ok:'.$id."\n";}catch(\RuntimeException $e){if($e->getCode()!==409)throw $e;echo "conflict\n";}exit;
}
$f=json_decode(file_get_contents(dirname($root).'/dashboard-fixture.json'),true,16,JSON_THROW_ON_ERROR);
$rf=json_decode(file_get_contents(dirname($root).'/roster-fixture.json'),true,16,JSON_THROW_ON_ERROR);$course=(int)$f['course'];$enrollment=(int)$f['active'];$owner=new \Joomla\CMS\User\User($f['owner']);$manager=new \Joomla\CMS\User\User($rf['manager']);
$count=static fn()=>(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollment_state_changes WHERE enrollment_id='.$enrollment)->loadResult();
$stable=static fn(int $id)=>$db->setQuery('SELECT user_id,subject_key,course_id,course_version_id,edition_id,required_total,completed_total,started_at,completed_at,last_activity_at,last_lesson_id,starts_at,ends_at FROM #__jlms_enrollments WHERE id='.$id)->loadAssoc();
if(($argv[2]??'')==='retained'){
 $saved=json_decode(file_get_contents($path),true,16,JSON_THROW_ON_ERROR);
 stateExpect($count()===$saved['history_count'],'state history retained');
 stateExpect($stable($enrollment)===$saved['stable'],'same enrollment and learning evidence retained');
 stateExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__action_logs WHERE extension='com_jlms.enrollmentstate' AND item_id=".(int)$saved['first_event'])->loadResult()===1,'native state audit retained');
 echo json_encode(['enrollment_state_retention_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;exit;
}
$before=$stable($enrollment);$groups=$db->setQuery('SELECT user_id,group_id FROM #__user_usergroup_map ORDER BY user_id,group_id')->loadAssocList();
$i=$service->editor($manager,$course,$enrollment,'suspended');$command=stateCommand($i);$event=$service->apply($manager,...$command);
stateExpect($service->editor($actor,$course,$enrollment)['impact']['previous_status']==='suspended','authorized suspension persisted');
stateExpect($service->apply($manager,...$command)===$event&&$count()===1,'same transport retry creates one event');
stateDenied(fn()=>$learning->resumeTarget($owner,'en-GB',$enrollment),403,'suspension denies classroom resume');
stateDenied(fn()=>$learning->complete($owner,'en-GB',$enrollment,$f['lesson'],bin2hex(random_bytes(16))),403,'suspension denies new learning writes');
$changed=$command;$changed[4]='Changed reason';stateDenied(fn()=>$service->apply($manager,...$changed),409,'UUID reuse with changed payload rejected');
$next=$service->editor($actor,$course,$enrollment,'active');$reactivate=stateCommand($next);
$broken=new \Jlms\Component\Jlms\Administrator\Application\EnrollmentStateService($db,$learning,$component->getEnrollmentRosterService(),static function():void{throw new \RuntimeException('COM_JLMS_ENROLLMENT_STATE_AUDIT_FAILED',503);});
stateDenied(fn()=>$broken->apply($actor,...$reactivate),503,'audit outage is a failed operation');
stateExpect($count()===1&&$service->editor($actor,$course,$enrollment)['impact']['previous_status']==='suspended','audit failure rolls back state and event');
$service->apply($actor,...$reactivate);
stateExpect($learning->resumeTarget($owner,'en-GB',$enrollment)['lesson_id']===$f['lesson'],'reactivation resumes same learning history');
stateExpect($service->apply($manager,...$command)===$event&&$service->editor($actor,$course,$enrollment)['impact']['previous_status']==='active','late replay does not reapply historical suspension');
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$manager->id,(int)$rf['group']);
try{stateDenied(fn()=>$service->apply($manager,...$command),403,'revoked rights deny even historical replay');}
finally{\Joomla\CMS\User\UserHelper::addUserToGroup((int)$manager->id,(int)$rf['group']);}
stateDenied(fn()=>$service->editor($owner,$course,$enrollment),403,'student cannot manage pedagogical state');
stateDenied(fn()=>$service->editor(new \Joomla\CMS\User\User(),$course,$enrollment),401,'anonymous state access denied');
stateDenied(fn()=>$service->editor($manager,(int)$rf['other_course'],$f['italian']),403,'course-specific management scope');
stateDenied(fn()=>$service->editor($actor,$course,$f['italian']),404,'foreign enrollment cannot cross course');
$i=$service->editor($actor,$course,$enrollment,'suspended');$base=stateCommand($i);
foreach([[7,false,400],[3,'cancelled',400],[4,'',422],[4,'<script>',422],[4,str_repeat('x',501),422],[6,str_repeat('0',64),409]]as[$index,$value,$code]){$bad=$base;$bad[$index]=$value;stateDenied(fn()=>$service->apply($actor,...$bad),$code,'malformed or stale command rejected');}
stateExpect($count()===2,'failed commands create no events');
$legacy=$service->editor($actor,$course,(int)$f['completed'],'suspended');$legacyBefore=$stable((int)$f['completed']);$service->apply($actor,...stateCommand($legacy));$service->apply($actor,...stateCommand($service->editor($actor,$course,(int)$f['completed'],'active')));
stateExpect($stable((int)$f['completed'])===$legacyBefore,'completed history and expired window preserved');
stateDenied(fn()=>$learning->resumeTarget($owner,'en-GB',(int)$f['completed']),403,'reactivation does not extend expired window');
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$owner->id,(int)$f['group']);
try{$service->apply($actor,...stateCommand($service->editor($actor,$course,$enrollment,'suspended')));$service->apply($actor,...stateCommand($service->editor($actor,$course,$enrollment,'active')));stateDenied(fn()=>$learning->resumeTarget($owner,'en-GB',$enrollment),403,'reactivation does not grant missing entitlement');}
finally{\Joomla\CMS\User\UserHelper::addUserToGroup((int)$owner->id,(int)$f['group']);}
// Real parallel requests share the enrollment lock and the preview revision.
foreach([false,true]as$different){
 $race=stateCommand($service->editor($actor,$course,$enrollment,'suspended'));file_put_contents(dirname($root).'/enrollment-state-race.json',json_encode($race,JSON_THROW_ON_ERROR));$startCount=$count();$workers=[];
 foreach(['same',$different?'different':'same']as$mode){$pipes=[];$proc=proc_open([PHP_BINARY,__FILE__,$root,'worker',$mode],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($proc))throw new \RuntimeException('Worker failed');$workers[]=[$proc,$pipes];}
 foreach($workers as[$proc,$pipes]){stream_set_timeout($pipes[1],30);if(trim((string)fgets($pipes[1]))!=='ready')throw new \RuntimeException('Worker bootstrap failed');}
 $db->transactionStart();$db->setQuery('SELECT id FROM #__jlms_enrollments WHERE id='.$enrollment.' FOR UPDATE')->loadResult();foreach($workers as[$proc,$pipes]){fwrite($pipes[0],"go\n");fclose($pipes[0]);}usleep(200000);$db->transactionCommit();$results=[];
 foreach($workers as[$proc,$pipes]){$out=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($proc)!==0)throw new \RuntimeException($err.$out);$results[]=$out;}
 stateExpect($count()===$startCount+1,'parallel requests write one state transition');
 stateExpect($different?(count(array_filter($results,static fn($v)=>$v==='conflict'))===1):($results[0]===$results[1]&&str_starts_with($results[0],'ok:')),'parallel CAS and retry outcomes');
 $service->apply($actor,...stateCommand($service->editor($actor,$course,$enrollment,'active')));
}
stateExpect($before===$stable($enrollment),'all state changes preserve learning history and assignment');
stateExpect($groups===$db->setQuery('SELECT user_id,group_id FROM #__user_usergroup_map ORDER BY user_id,group_id')->loadAssocList(),'state changes never assign native groups');
$log=$db->setQuery("SELECT message FROM #__action_logs WHERE extension='com_jlms.enrollmentstate' AND item_id=".$event)->loadResult();$message=json_decode($log,true,16,JSON_THROW_ON_ERROR);
stateExpect($message['previous_status']==='active'&&$message['target_status']==='suspended'&&$message['reason_reference']===$event&&!str_contains($log,'PRIVATE_STATE_REASON'),'native audit uses minimal before/after and reason reference');
// A fresh learner/quiz isolates runtime suspension checks from historical fixtures.
$qf=json_decode(file_get_contents(dirname($root).'/quiz-attempts-fixture.json'),true,16,JSON_THROW_ON_ERROR);
$user=new \Joomla\CMS\User\User();$data=['name'=>'CI Enrollment State Quiz','username'=>'cienrollmentstatequiz','email'=>'cienrollmentstatequiz@example.invalid','password'=>'Ci-only-Learner-Password-2026','password2'=>'Ci-only-Learner-Password-2026','groups'=>[2,$f['group']],'block'=>0,'activation'=>''];if(!$user->bind($data)||!$user->save())throw new \RuntimeException($user->getError());$user=new \Joomla\CMS\User\User((int)$user->id);
$qe=$learning->start($user,'en-GB',$qf['course_id'],bin2hex(random_bytes(16)));$runtime=$component->getQuizAttemptService();$attempt=$runtime->start($user,'en-GB',$qe,$qf['lesson_id'],stateKey());$aid=$attempt['attempt']['id'];
$answers=json_encode([$attempt['questions'][0]['id']=>['first']],JSON_THROW_ON_ERROR);$runtime->mutate($user,'en-GB',$qe,$qf['lesson_id'],$aid,1,stateKey(),$answers,false);
$quizBefore=$db->setQuery('SELECT status,revision,rules_snapshot_json,deadline_at FROM #__jlms_attempts WHERE id='.$aid)->loadAssoc();$savedAnswers=$db->setQuery('SELECT aa.* FROM #__jlms_attempt_answers AS aa INNER JOIN #__jlms_attempt_questions AS q ON q.id=aa.attempt_question_id WHERE q.attempt_id='.$aid)->loadAssocList();
$qi=$service->editor($actor,$qf['course_id'],$qe,'suspended');stateExpect($qi['impact']['open_attempts']===1,'preview reports an open quiz');$service->apply($actor,...stateCommand($qi));
stateDenied(fn()=>$runtime->mutate($user,'en-GB',$qe,$qf['lesson_id'],$aid,2,stateKey(),$answers,false),403,'suspension denies quiz save');
stateDenied(fn()=>$runtime->mutate($user,'en-GB',$qe,$qf['lesson_id'],$aid,2,stateKey(),$answers,true),403,'suspension denies quiz submit');
$service->apply($actor,...stateCommand($service->editor($actor,$qf['course_id'],$qe,'active')));
stateExpect($quizBefore===$db->setQuery('SELECT status,revision,rules_snapshot_json,deadline_at FROM #__jlms_attempts WHERE id='.$aid)->loadAssoc(),'reactivation never resets quiz status revision snapshot or deadline');
stateExpect($savedAnswers===$db->setQuery('SELECT aa.* FROM #__jlms_attempt_answers AS aa INNER JOIN #__jlms_attempt_questions AS q ON q.id=aa.attempt_question_id WHERE q.attempt_id='.$aid)->loadAssocList(),'saved quiz responses retained');
// Stale preview after an authorized quiz save cannot suspend on an outdated impact.
$stale=stateCommand($service->editor($actor,$qf['course_id'],$qe,'suspended'));$runtime->mutate($user,'en-GB',$qe,$qf['lesson_id'],$aid,2,stateKey(),$answers,false);
stateDenied(fn()=>$service->apply($actor,...$stale),409,'quiz progress invalidates preview');
$runtime->mutate($user,'en-GB',$qe,$qf['lesson_id'],$aid,3,stateKey(),$answers,true);
$serialized=json_encode($service->editor($actor,$course,$enrollment),JSON_THROW_ON_ERROR);stateExpect(!str_contains($serialized,'subject_key')&&!str_contains($serialized,'rules_json'),'editor excludes internal subject and grading data');
file_put_contents($path,json_encode(['course'=>$course,'enrollment'=>$enrollment,'owner'=>$f['owner'],'lesson'=>$f['lesson'],'first_event'=>$event,'history_count'=>$count(),'stable'=>$stable($enrollment),'manager'=>$rf['manager'],'manager_group'=>$rf['group'],'http_enrollment'=>$f['other_enrollment'],'cancelled'=>$f['cancelled'],'foreign_enrollment'=>$f['italian'],'other_course'=>$rf['other_course']],JSON_THROW_ON_ERROR));
echo json_encode(['enrollment_state_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;
