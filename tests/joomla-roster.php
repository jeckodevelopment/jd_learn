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

$actor=$app->getIdentity();$service=$component->getEnrollmentRosterService();$checks=0;
function rosterExpect(bool $ok,string $message):void{global$checks;if(!$ok)throw new \RuntimeException($message);$checks++;}
function rosterDenied(callable $fn,int $code,string $message):void{try{$fn();}catch(\RuntimeException $e){rosterExpect($e->getCode()===$code,$message.': '.$e->getMessage());return;}throw new \RuntimeException($message);}
$f=json_decode(file_get_contents(dirname($root).'/dashboard-fixture.json'),true,16,JSON_THROW_ON_ERROR);$course=(int)$f['course'];
$otherCourse=(int)$db->setQuery('SELECT course_id FROM #__jlms_enrollments WHERE id='.(int)$f['italian'])->loadResult();
$version=(int)$db->setQuery('SELECT course_version_id FROM #__jlms_enrollments WHERE id='.(int)$f['active'])->loadResult();
$read=static fn($who,array $filters=[])=>$db->setQuery($service->query($who,$course,$filters),0,50)->loadObjectList();
$path=dirname($root).'/roster-fixture.json';
if(($argv[2]??'')==='retained'){
 $saved=json_decode(file_get_contents($path),true,16,JSON_THROW_ON_ERROR);
 rosterExpect(count($read($actor))===$saved['total'],'roster history retained');
 rosterExpect(count($read($actor,['user'=>$f['owner'],'progress'=>'completed']))===1,'completion retained separately');
 rosterDenied(fn()=>$service->query(new \Joomla\CMS\User\User($saved['manager']),$otherCourse),403,'course scope retained');
 echo json_encode(['roster_retention_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;exit;
}
$before=$db->setQuery('SELECT id,revision,completed_total,completed_at,last_activity_at FROM #__jlms_enrollments ORDER BY id')->loadAssocList();
$rows=$read($actor);$allIds=array_map(static fn($e)=>(int)$e->id,$rows);
rosterExpect(count($rows)>=26,'course roster includes more than one student');
rosterExpect(in_array($f['active'],$allIds,true)&&in_array($f['other_enrollment'],$allIds,true),'authorized operator sees all course owners');
rosterExpect(!in_array($f['italian'],$allIds,true),'another course is excluded');
rosterExpect(count($read($actor,['user'=>$f['owner']]))===25,'exact native user ID filter');
$ownerUsername=(string)$db->setQuery('SELECT username FROM #__users WHERE id='.(int)$f['owner'])->loadResult();
$usernameMatches=$read($actor,['user'=>$f['owner'],'search'=>substr($ownerUsername,0,6)]);
rosterExpect(count($usernameMatches)===25,'search finds learner by username');
$ownerName=(string)$db->setQuery('SELECT name FROM #__users WHERE id='.(int)$f['owner'])->loadResult();
$searchFixtureName='Unique Roster Search Name';$ownerId=(int)$f['owner'];
$rename=$db->createQuery()->update('#__users')->set('name = :name')->where('id = :id')->bind(':name',$searchFixtureName)->bind(':id',$ownerId,\Joomla\Database\ParameterType::INTEGER);
$db->setQuery($rename)->execute();
try { rosterExpect(count($read($actor,['user'=>$f['owner'],'search'=>'Roster Search Name']))===25,'search finds learner by display name'); }
finally { $restore=$db->createQuery()->update('#__users')->set('name = :name')->where('id = :id')->bind(':name',$ownerName)->bind(':id',$ownerId,\Joomla\Database\ParameterType::INTEGER);$db->setQuery($restore)->execute(); }
rosterExpect(count($read($actor,['search'=>'%']))===0,'search treats LIKE wildcards literally');
rosterDenied(fn()=>$service->query($actor,$course,['search'=>str_repeat('x',101)]),400,'overlong learner search rejected');
rosterDenied(fn()=>$service->query($actor,$course,['search'=>['cidash']]),400,'non-string learner search rejected');
rosterExpect(count($read($actor,['user'=>$f['owner'],'status'=>'suspended']))===1,'status filter');
rosterExpect(count($read($actor,['user'=>$f['owner'],'progress'=>'completed']))===1,'completion filter independent of expiration');
rosterExpect(count($read($actor,['user'=>$f['owner'],'progress'=>'in_progress']))===1,'activity filter');
rosterExpect(count($read($actor,['user'=>$f['owner'],'progress'=>'not_started']))===23,'not started filter');
rosterExpect(count($read($actor,['user'=>$f['empty']]))===0,'empty filter has no writes');
rosterExpect(count($read($actor,['version'=>$version]))===count($rows),'version filter');
$foreignVersion=(int)$db->setQuery('SELECT course_version_id FROM #__jlms_enrollments WHERE id='.(int)$f['italian'])->loadResult();
rosterDenied(fn()=>$service->query($actor,$course,['version'=>$foreignVersion]),404,'version cannot cross course');
foreach([['status'=>"active' OR 1=1 --"],['progress'=>['completed']],['user'=>'1 OR 1=1'],['version'=>-1],['user'=>1.5]]as$filters)rosterDenied(fn()=>$service->query($actor,$course,$filters),400,'invalid filters fail closed');
$payload=json_encode($rows,JSON_THROW_ON_ERROR);
foreach(['rules_json','subject_key','email','password','LEARNING_PRIVATE_BODY','grading_json']as$secret)rosterExpect(!str_contains($payload,$secret),'no private field '.$secret);
rosterDenied(fn()=>$service->query(new \Joomla\CMS\User\User(),$course),401,'anonymous roster denied');
rosterDenied(fn()=>$service->query(new \Joomla\CMS\User\User($f['owner']),$course),403,'learner roster denied');
$g=$app->bootComponent('com_users')->getMVCFactory()->createModel('Group','Administrator',['ignore_request'=>true]);
if(!$g->save(['id'=>0,'parent_id'=>2,'title'=>'CI scoped enrollment reader']))throw new \RuntimeException($g->getError());$group=(int)$g->getState('group.id');
// Keep backend entry separate from the course permission so revoking the latter exercises our model, not Joomla's login wall.
if(!$g->save(['id'=>0,'parent_id'=>2,'title'=>'CI roster backend entry']))throw new \RuntimeException($g->getError());$backendGroup=(int)$g->getState('group.id');
$asset=new \Joomla\CMS\Table\Asset($db);$asset->loadByName('root.1');$permissions=json_decode($asset->rules,true);$permissions['core.login.admin'][(string)$backendGroup]=true;$asset->rules=json_encode($permissions,JSON_THROW_ON_ERROR);$asset->store();
$asset->loadByName('com_jlms');$permissions=json_decode($asset->rules,true);$permissions['core.manage'][(string)$backendGroup]=true;$asset->rules=json_encode($permissions,JSON_THROW_ON_ERROR);$asset->store();
$asset->loadByName('com_jlms.course.'.$course);$oldRules=$asset->rules;$permissions=json_decode($oldRules,true);$permissions['jlms.enrollments.manage'][(string)$group]=true;$asset->rules=json_encode($permissions,JSON_THROW_ON_ERROR);$asset->store();
$user=new \Joomla\CMS\User\User();$data=['name'=>'CI Roster Reader','username'=>'cirosterreader','email'=>'cirosterreader@example.invalid','password'=>'Ci-only-Roster-Password-2026','password2'=>'Ci-only-Roster-Password-2026','groups'=>[2,$group,$backendGroup],'block'=>0,'sendEmail'=>0];
if(!$user->bind($data)||!$user->save())throw new \RuntimeException($user->getError());$user=new \Joomla\CMS\User\User((int)$user->id);
rosterExpect(count($read($user))===count($rows),'dedicated course ACL grants roster without author permission');
rosterDenied(fn()=>$service->query($user,$otherCourse),403,'direct course ID cannot bypass scope');
$asset->loadByName('com_jlms.course.'.$course);$grantedRules=$asset->rules;$permissions=json_decode($grantedRules,true);$permissions['jlms.enrollments.manage'][(string)$group]=false;$asset->rules=json_encode($permissions,JSON_THROW_ON_ERROR);$asset->store();
try{rosterDenied(fn()=>$read($user),403,'explicit course deny refreshes stale identity');}finally{$asset->rules=$grantedRules;$asset->store();}
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$user->id,$group);
try{rosterDenied(fn()=>$read($user),403,'group revocation refreshes stale identity');}finally{\Joomla\CMS\User\UserHelper::addUserToGroup((int)$user->id,$group);}
$app->loadIdentity($user);$m=$factory->createModel('Enrollments','Administrator',['ignore_request'=>true]);$m->setState('filter.course',$course);$m->setState('filter.roster',['user'=>$f['owner']]);$m->setState('list.start',20);$m->setState('list.limit',20);
rosterExpect($m->getTotal()===25&&count($m->getItems())===5,'native list total and pagination');
rosterExpect($m->getRosterForm()->getField('version')!==false&&$m->getRosterForm()->getField('search')!==false,'native filter form includes learner search');
$app->loadIdentity(new \Joomla\CMS\User\User($f['owner']));
rosterDenied(fn()=>$m->getItems(),403,'cached native list rechecks authorization');rosterDenied(fn()=>$m->getTotal(),403,'cached total rechecks authorization');
$app->loadIdentity($actor);
// Escape hostile native account labels in the actual HTTP view, then restore via fixture helper.
$originalName=$db->setQuery('SELECT name FROM #__users WHERE id='.(int)$f['owner'])->loadResult();
$name='<img src=x onerror=ROSTER_XSS>'; $id=(int)$f['owner'];$q=$db->createQuery()->update('#__users')->set('name = :name')->where('id = :id')->bind(':name',$name)->bind(':id',$id,\Joomla\Database\ParameterType::INTEGER);$db->setQuery($q)->execute();
rosterExpect($before===$db->setQuery('SELECT id,revision,completed_total,completed_at,last_activity_at FROM #__jlms_enrollments ORDER BY id')->loadAssocList(),'roster reads do not mutate learning history');
file_put_contents($path,json_encode(['manager'=>(int)$user->id,'group'=>$group,'course'=>$course,'other_course'=>$otherCourse,'version'=>$version,'total'=>count($rows),'owner'=>$f['owner'],'original_name'=>$originalName,'completed'=>$f['completed'],'suspended'=>$f['suspended'],'active'=>$f['active'],'foreign_enrollment'=>$f['italian']],JSON_THROW_ON_ERROR));
echo json_encode(['roster_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;
