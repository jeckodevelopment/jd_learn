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
    $fixtureDb->exec("UPDATE joomla_extensions SET params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.video_external_policy','embed') WHERE type='component' AND element='com_jlms'");
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
function timeExpect(bool $ok,string $message):void{global$checks;if(!$ok)throw new \RuntimeException($message);$checks++;}
function timeReject(callable $work,int $code,string $message):void{try{$work();}catch(\RuntimeException $error){timeExpect($error->getCode()===$code,$message.': '.$error->getMessage().' / '.$error->getCode());return;}throw new \RuntimeException($message);}
function timeKey():string{$s=bin2hex(random_bytes(16));return substr($s,0,8).'-'.substr($s,8,4).'-4'.substr($s,13,3).'-a'.substr($s,17,3).'-'.substr($s,20);}
$actor=$app->getIdentity();$learning=$component->getLearningService();$time=$component->getQuizTimeService();
$f=json_decode(file_get_contents(dirname($root).'/quiz-attempts-fixture.json'),true,16,JSON_THROW_ON_ERROR);
$course=$f['course_id'];$lesson=$f['lesson_id'];$path=dirname($root).'/quiz-time-fixture.json';
$now=time();$runtime=new \Jlms\Component\Jlms\Administrator\Application\QuizAttemptService($db,$learning,$component->getQuizSnapshotService(),static function()use(&$now):int{return $now;},$time);
if(($argv[2]??'')==='retained'){
    $t=json_decode(file_get_contents($path),true,16,JSON_THROW_ON_ERROR);$e=$time->editor($actor,$course,$lesson,$t['enrollment_id']);
    timeExpect((int)$e['current']->revision_no===$t['last_revision']&&(int)$e['current']->extra_seconds===180,'allowance revisions survive repeat upgrade');
    $snapshot=$db->setQuery('SELECT rules_snapshot_json,deadline_at FROM #__jlms_attempts WHERE id='.(int)$t['attempt_id'])->loadObject();
    timeExpect(hash('sha256',json_encode($snapshot,JSON_THROW_ON_ERROR))===$t['snapshot_hash'],'attempt rule snapshot and deadline survive upgrade');
    timeExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__action_logs WHERE extension='com_jlms.quiztime' AND item_id=".(int)$t['first_adjustment'])->loadResult()===1,'native audit survives upgrade');
    echo json_encode(['quiz_time_upgrade_checks'=>$checks,'status'=>'passed']).PHP_EOL;exit(0);
}
$old=json_decode(file_get_contents(dirname($root).'/legacy-time-fixture.json'),true,16,JSON_THROW_ON_ERROR);
$oldUser=new \Joomla\CMS\User\User($old['user_id']);$oldState=$runtime->state($oldUser,'en-GB',$old['enrollment_id'],$old['lesson_id']);
$oldSnapshot=$db->setQuery('SELECT rules_snapshot_json,deadline_at FROM #__jlms_attempts WHERE id='.(int)$old['attempt_id'])->loadObject();
timeExpect($oldState['extra_time_seconds']===0&&$oldState['base_time_seconds']===600,'actual pre-upgrade snapshot reads with zero individual allowance');
timeExpect(hash('sha256',json_encode($oldSnapshot,JSON_THROW_ON_ERROR))===$old['snapshot_hash'],'upgrade preserves actual pre-0.11 snapshot and deadline');
$group=(int)$db->setQuery("SELECT id FROM #__usergroups WHERE title='CI learning entitlement'")->loadResult();
$user=new \Joomla\CMS\User\User();$data=['name'=>'CI extra time learner','username'=>'ciquiztimelearner','email'=>'ciquiztimelearner@example.invalid','password'=>'Ci-only-Learner-Password-2026','password2'=>'Ci-only-Learner-Password-2026','groups'=>[2,$group],'block'=>0,'activation'=>''];
if(!$user->bind($data)||!$user->save())throw new \RuntimeException($user->getError());$user=new \Joomla\CMS\User\User((int)$user->id);
$enrollment=$learning->start($user,'en-GB',$course,bin2hex(random_bytes(16)));$quiz=(int)$db->setQuery('SELECT id FROM #__jlms_quizzes WHERE lesson_id='.$lesson)->loadResult();
$pre=$runtime->state($user,'en-GB',$enrollment,$lesson);timeExpect($pre['time_seconds']===60&&$pre['extra_time_seconds']===0,'ordinary timer before individual authorization');
timeReject(fn()=>$time->editor($user,$course,$lesson,$enrollment),403,'learner cannot read management reasons or identities');
timeReject(fn()=>$time->save($user,$course,$lesson,$enrollment,0,2,'Unauthorized',timeKey()),403,'learner cannot authorize extra time');
timeReject(fn()=>$time->save($actor,$course,$lesson,$enrollment,0,2,'',timeKey()),422,'reason required');
timeReject(fn()=>$time->save($actor,$course,$lesson,$enrollment,0,1441,'Too long',timeKey()),400,'additional duration is bounded');
$key=timeKey();$id=$time->save($actor,$course,$lesson,$enrollment,0,2,'PRIVATE_TIME_REASON_REFERENCE',$key);
timeExpect($time->save($actor,$course,$lesson,$enrollment,0,2,'PRIVATE_TIME_REASON_REFERENCE',$key)===$id,'identical mutation replays without duplicate revision');
timeReject(fn()=>$time->save($actor,$course,$lesson,$enrollment,0,3,'PRIVATE_TIME_REASON_REFERENCE',$key),409,'different body with same mutation conflicts');
timeReject(fn()=>$time->save($actor,$course,$lesson,$enrollment,0,3,'Stale tab',timeKey()),409,'stale revision rejected');
timeReject(fn()=>$time->rules(['time_seconds'=>0],$enrollment,$quiz),422,'untimed quiz cannot acquire a finite additional duration');
$e=$time->editor($actor,$course,$lesson,$enrollment);timeExpect((int)$e['current']->revision_no===1&&count($e['history'])===1,'authorized append-only history');
$pre=$runtime->state($user,'en-GB',$enrollment,$lesson);timeExpect($pre['time_seconds']===180&&$pre['next_time_seconds']===180&&$pre['extra_time_seconds']===120,'prestart shows effective base plus allowance');
timeExpect(!str_contains(json_encode($pre,JSON_THROW_ON_ERROR),'PRIVATE_TIME_REASON_REFERENCE'),'learner DTO never exposes operational reasons');
$a=$runtime->start($user,'en-GB',$enrollment,$lesson,timeKey());$attempt=$a['attempt']['id'];timeExpect($a['attempt']['deadline']===$now+180,'deadline freezes additional seconds at start');
$original=$db->setQuery('SELECT rules_snapshot_json,deadline_at FROM #__jlms_attempts WHERE id='.$attempt)->loadObject();$rules=json_decode($original->rules_snapshot_json,true,16,JSON_THROW_ON_ERROR);
timeExpect($rules['base_time_seconds']===60&&$rules['extra_time_seconds']===120&&$rules['time_adjustment_id']===$id,'attempt stores individual rule provenance');
$time->save($actor,$course,$lesson,$enrollment,1,0,'Withdraw future allowance',timeKey());
$r=$runtime->state($user,'en-GB',$enrollment,$lesson);timeExpect($r['attempt']['deadline']===$a['attempt']['deadline']&&$r['time_seconds']===180&&$r['next_time_seconds']===60,'withdrawal changes future only, never current deadline');
$now+=61;$r=$runtime->state($user,'en-GB',$enrollment,$lesson);timeExpect($r['attempt']['state']==='open','base deadline does not expire an individually extended attempt');
$now+=119;$r=$runtime->mutate($user,'en-GB',$enrollment,$lesson,$attempt,1,timeKey(),'{}',true);timeExpect($r['attempt']['state']==='expired'&&$r['attempt']['score_points']===0,'effective deadline grades only saved answers');
$now+=61;$a=$runtime->start($user,'en-GB',$enrollment,$lesson,timeKey());timeExpect($a['attempt']['deadline']===$now+60&&$a['extra_time_seconds']===0,'next attempt uses the withdrawn allowance');
$time->save($actor,$course,$lesson,$enrollment,2,3,'Second future authorization',timeKey());
timeExpect($runtime->state($user,'en-GB',$enrollment,$lesson)['attempt']['deadline']===$a['attempt']['deadline'],'later allowance cannot rewrite an already open attempt');
// Existing 0.10 snapshots carry no extra-time fields; their actual deadline remains authoritative.
timeReject(fn()=>$runtime->state($user,'en-GB',$f['enrollment_id'],$lesson),404,'foreign enrollment still hidden');
$legacyOwner=new \Joomla\CMS\User\User((int)$db->setQuery("SELECT id FROM #__users WHERE username='cilearner'")->loadResult());
$legacy=$runtime->state($legacyOwner,'en-GB',$f['enrollment_id'],$lesson);timeExpect($legacy['attempt']['state']!=='open'&&$legacy['extra_time_seconds']===0,'ordinary historical attempts continue to read correctly');
// A missing audit must roll back the allowance revision.
$broken=new \Jlms\Component\Jlms\Administrator\Application\QuizTimeService($db,$learning,static function():void{throw new \RuntimeException('COM_JLMS_QUIZ_TIME_AUDIT_FAILED',503);});
timeReject(fn()=>$broken->save($actor,$course,$lesson,$enrollment,3,4,'Simulated audit outage',timeKey()),503,'audit failure is not a successful administrative change');
timeExpect((int)$time->editor($actor,$course,$lesson,$enrollment)['current']->revision_no===3,'audit failure rolls back data');
$log=$db->setQuery("SELECT message FROM #__action_logs WHERE extension='com_jlms.quiztime' AND item_id=".$id)->loadResult();$message=json_decode($log,true,16,JSON_THROW_ON_ERROR);
timeExpect($message['before_seconds']===0&&$message['after_seconds']===120&&$message['reason_reference']===$id&&!str_contains($log,'PRIVATE_TIME_REASON_REFERENCE'),'native Action Log records minimal before/after and reason reference');
$g=$app->bootComponent('com_users')->getMVCFactory()->createModel('Group','Administrator',['ignore_request'=>true]);
if(!$g->save(['id'=>0,'parent_id'=>2,'title'=>'CI assessment time manager']))throw new \RuntimeException($g->getError());$managerGroup=(int)$g->getState('group.id');
$asset=new \Joomla\CMS\Table\Asset($db);$asset->loadByName('com_jlms');$permissions=json_decode($asset->rules,true);
foreach(['core.manage','jlms.enrollments.manage','jlms.assessment.correct']as$action)$permissions[$action][(string)$managerGroup]=true;
$asset->rules=json_encode($permissions,JSON_THROW_ON_ERROR);$asset->store();
$manager=new \Joomla\CMS\User\User();$data=array_replace($data,['name'=>'CI Time Manager','username'=>'ciquiztimemanager','email'=>'ciquiztimemanager@example.invalid','password'=>'Ci-only-Learner-Password-2026','password2'=>'Ci-only-Learner-Password-2026','groups'=>[2,$managerGroup]]);
if(!$manager->bind($data)||!$manager->save())throw new \RuntimeException($manager->getError());$manager=new \Joomla\CMS\User\User((int)$manager->id);
timeExpect($time->editor($manager,$course,$lesson,$enrollment)['enrollment']->id==$enrollment,'dedicated native permissions allow management without author role');
$courseAsset=(int)$db->setQuery('SELECT asset_id FROM #__jlms_courses WHERE id='.$course)->loadResult();$asset->load($courseAsset);$oldRules=$asset->rules;
$asset->rules=json_encode(['jlms.assessment.correct'=>[(string)$managerGroup=>false]],JSON_THROW_ON_ERROR);$asset->store();
timeReject(fn()=>$time->editor($manager,$course,$lesson,$enrollment),403,'course-specific ACL denial overrides global management');
timeReject(fn()=>$time->save($manager,$course,$lesson,$enrollment,3,4,'Denied override',timeKey()),403,'direct POST service cannot bypass course denial');
$asset->rules=$oldRules;$asset->store();
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$manager->id,$managerGroup);
timeReject(fn()=>$time->editor($manager,$course,$lesson,$enrollment),403,'revoked manager rights are reloaded in same session');
// Never grant access by changing a pedagogical allowance.
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$user->id,$group);
$replay=$time->save($actor,$course,$lesson,$enrollment,0,2,'PRIVATE_TIME_REASON_REFERENCE',$key);timeExpect($replay===$id,'authorized administrative replay remains valid');
timeReject(fn()=>$runtime->state($user,'en-GB',$enrollment,$lesson),403,'allowance never bypasses a revoked Joomla level');
\Joomla\CMS\User\UserHelper::addUserToGroup((int)$user->id,$group);
$last=$time->editor($actor,$course,$lesson,$enrollment);timeExpect(count($last['history'])===3,'all previous values and reasons remain immutable');
file_put_contents($path,json_encode(['course_id'=>$course,'lesson_id'=>$lesson,'enrollment_id'=>$enrollment,'learner_id'=>(int)$user->id,'attempt_id'=>$attempt,'first_adjustment'=>$id,'last_revision'=>3,'snapshot_hash'=>hash('sha256',json_encode($original,JSON_THROW_ON_ERROR))],JSON_THROW_ON_ERROR));
echo json_encode(['quiz_time_checks'=>$checks,'status'=>'passed']).PHP_EOL;
