<?php

declare(strict_types=1);


/** Run after Joomla's CLI installer and extension:install in an isolated CI site. */
$root = realpath($argv[1] ?? '');
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new \RuntimeException('Disposable Joomla site required.');
}
$fixtureDb=new \PDO(getenv('JLMS_TEST_DSN'),getenv('JLMS_TEST_USER'),getenv('JLMS_TEST_PASSWORD'),[\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION]);
$languagePlugin=$fixtureDb->query("SELECT enabled FROM joomla_extensions WHERE type='plugin' AND folder='system' AND element='languagefilter'")->fetchColumn();
$fixtureDb->exec("UPDATE joomla_extensions SET enabled=1 WHERE type='plugin' AND folder='system' AND element='languagefilter'");
register_shutdown_function(static function()use($fixtureDb,$languagePlugin):void{$q=$fixtureDb->prepare("UPDATE joomla_extensions SET enabled=? WHERE type='plugin' AND folder='system' AND element='languagefilter'");$q->execute([$languagePlugin]);});

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

$operator=$app->getIdentity();
$checks=0;
function dashExpect(bool $ok,string $message):void{global$checks;if(!$ok)throw new \RuntimeException($message);$checks++;}
function dashDenied(callable $call,int $code,string $message):void{try{$call();}catch(\RuntimeException $e){dashExpect($e->getCode()===$code,$message.': '.$e->getMessage());return;}throw new \RuntimeException($message);}
$service=$component->getDashboardService();$learning=$component->getLearningService();
$path=dirname($root).'/dashboard-fixture.json';
$f=json_decode(file_get_contents(dirname($root).'/learning-fixture.json'),true,16,JSON_THROW_ON_ERROR);
$read=static function(\Joomla\CMS\User\User $user,string $filter='all')use($db,$service):array{return $service->cards($user,'en-GB',$db->setQuery($service->query($user,$filter))->loadObjectList());};
if(($argv[2]??'')==='retained'){
    $saved=json_decode(file_get_contents($path),true,16,JSON_THROW_ON_ERROR);$user=new \Joomla\CMS\User\User($saved['owner']);
    $cards=$read($user);dashExpect(count($cards)===26,'owner history count retained');
    $card=array_values(array_filter($cards,static fn($c)=>$c['id']===$saved['completed']))[0];
    dashExpect($card['learning_state']==='completed'&&$card['access']==='expired'&&$card['percent']===100,'completion and expired access retained independently');
    dashExpect($learning->resumeTarget($user,'en-GB',$saved['active'])['lesson_id']===$saved['lesson'],'resume history retained');
    echo json_encode(['dashboard_retention_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;exit;
}
$newUser=static function(string $name)use($f):\Joomla\CMS\User\User{
    $u=new \Joomla\CMS\User\User();$d=['name'=>'CI '.$name,'username'=>$name,'email'=>$name.'@example.invalid','password'=>'Ci-only-Dashboard-Password-2026','password2'=>'Ci-only-Dashboard-Password-2026','groups'=>[2,$f['group_id']],'block'=>0,'activation'=>''];
    if(!$u->bind($d)||!$u->save())throw new \RuntimeException('Dashboard fixture user failed');return new \Joomla\CMS\User\User((int)$u->id);
};
$owner=$newUser('cidashboard');$other=$newUser('cidashboardother');$empty=$newUser('cidashboardempty');
$active=$learning->start($owner,'en-GB',$f['course_id'],bin2hex(random_bytes(16)));
$otherEnrollment=$learning->start($other,'en-GB',$f['course_id'],bin2hex(random_bytes(16)));
$base=$db->setQuery('SELECT * FROM #__jlms_enrollments WHERE id='.$active)->loadObject();
$editionBase=$db->setQuery('SELECT * FROM #__jlms_editions WHERE id='.(int)$base->edition_id)->loadObject();
$seed=static function(string $code,array $fields=[])use($db,$base,$editionBase):int{
    // Disposable history fixture only; not a manual-enrollment product API.
    $edition=clone$editionBase;unset($edition->id);$edition->code='dashboard-'.$code;$db->insertObject('#__jlms_editions',$edition,'id');
    $row=clone$base;unset($row->id);$row->edition_id=(int)$edition->id;foreach($fields as$key=>$value)$row->$key=$value;
    $db->insertObject('#__jlms_enrollments',$row,'id');return(int)$row->id;
};
$now=gmdate('Y-m-d H:i:s');
$completed=$seed('completed',['completed_total'=>3,'completed_at'=>$now,'ends_at'=>'2000-01-01 00:00:00']);
$suspended=$seed('suspended',['status'=>'suspended']);$cancelled=$seed('cancelled',['status'=>'cancelled']);
$future=$seed('future',['starts_at'=>'2099-01-01 00:00:00']);
for($i=0;$i<20;$i++)$seed('page-'.$i);
$update=static function(string $table,int $id,array $fields)use($db):void{$r=(object)(['id'=>$id]+$fields);$db->updateObject('#__'.$table,$r,'id',true);};
$snapshot=static fn()=>$db->setQuery('SELECT revision,started_at,completed_total,last_activity_at,last_lesson_id FROM #__jlms_enrollments WHERE id='.$active)->loadAssoc();
$before=$snapshot();
dashExpect($read($empty)===[],'empty owner has no accidental enrollments');
dashDenied(fn()=>$service->query(new \Joomla\CMS\User\User()),401,'guest history denied');
$app->loadIdentity(new \Joomla\CMS\User\User());
$guestModel=$factory->createModel('Dashboard','Site',['ignore_request'=>true]);
dashDenied(fn()=>$guestModel->getItems(),401,'native list wrapper preserves guest rejection');
dashDenied(fn()=>$guestModel->getTotal(),401,'native totals require authentication too');
$app->loadIdentity($operator);
dashDenied(fn()=>$learning->resumeTarget($other,'en-GB',$active),404,'other owner cannot resolve resume target');
$rows=$db->setQuery($service->query($owner))->loadObjectList();
dashExpect(count($rows)===25,'query selects only owner history');
dashDenied(fn()=>$service->cards($other,'en-GB',$rows),404,'projection rejects another owner rows');
$cards=$read($owner);$byId=array_column($cards,null,'id');
dashExpect($byId[$active]['learning_state']==='not_started'&&$byId[$active]['access']==='available'&&$byId[$active]['percent']===0,'new enrollment status independent from access');
dashExpect($byId[$completed]['learning_state']==='completed'&&$byId[$completed]['access']==='expired'&&$byId[$completed]['percent']===100,'completed history remains visible after window expiry');
dashExpect($byId[$suspended]['access']==='suspended'&&$byId[$cancelled]['access']==='cancelled'&&$byId[$future]['access']==='not_open','distinct classroom block reasons');
dashExpect(count($read($owner,'completed'))===1&&count($read($owner,'not_started'))===24&&$read($owner,'in_progress')===[],'progress filter does not conflate access status');
$target=$learning->resumeTarget($owner,'en-GB',$active);
dashExpect($target['lesson_id']===$f['lessons'][0],'first available activity chosen when no last activity');
dashExpect($snapshot()===$before,'dashboard and resume reads do not write educational history');
$learning->complete($owner,'en-GB',$active,$f['lessons'][1],bin2hex(random_bytes(16)));
dashExpect($learning->resumeTarget($owner,'en-GB',$active)['lesson_id']===$f['lessons'][1],'resume returns last valid activity');
dashExpect(count($read($owner,'in_progress'))===1,'learning progress filter reflects persisted activity');
$foreignLesson=(int)$db->setQuery('SELECT id FROM #__jlms_lessons WHERE course_version_id <> '.(int)$base->course_version_id.' ORDER BY id LIMIT 1')->loadResult();
$update('jlms_enrollments',$active,['last_lesson_id'=>$foreignLesson]);
dashExpect($learning->resumeTarget($owner,'en-GB',$active)['lesson_id']===$f['lessons'][0],'cross-version last activity falls back without disclosing its body');
$update('jlms_enrollments',$active,['last_lesson_id'=>$f['lessons'][1]]);
dashDenied(fn()=>$learning->resumeTarget($owner,'en-GB',$suspended),403,'suspension cannot be bypassed by resume');
dashDenied(fn()=>$learning->resumeTarget($owner,'en-GB',$completed),403,'expired completed classroom cannot be reopened by resume');
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$owner->id,$f['group_id']);
try{
    $c=array_column($read($owner),null,'id');dashExpect($c[$active]['access']==='access'&&$c[$completed]['completed'],'fresh group revocation keeps owner history but removes access');
    dashDenied(fn()=>$learning->resumeTarget($owner,'en-GB',$active),403,'old actor object cannot bypass current revocation');
}finally{\Joomla\CMS\User\UserHelper::addUserToGroup((int)$owner->id,$f['group_id']);}
dashExpect(array_column($read($owner),null,'id')[$active]['access']==='available','restored current membership restores resume availability');
$update('users',(int)$owner->id,['block'=>1]);
try{dashDenied(fn()=>$read($owner),403,'blocked account cannot read personal history');dashDenied(fn()=>$learning->resumeTarget($owner,'en-GB',$active),403,'blocked account cannot resume');}
finally{$update('users',(int)$owner->id,['block'=>0]);}
$update('jlms_courses',$f['course_id'],['state'=>0]);
try{$c=array_column($read($owner),null,'id');dashExpect(count($c)===25&&$c[$active]['access']==='unavailable'&&$c[$active]['title']!=='','withdrawn catalog preserves historical title and counters');}
finally{$update('jlms_courses',$f['course_id'],['state'=>1]);}
$serialized=json_encode($read($owner),JSON_THROW_ON_ERROR);
dashExpect(!str_contains($serialized,'LEARNING_PRIVATE_BODY')&&!str_contains($serialized,'rules_json')&&!str_contains($serialized,'subject_key')&&!str_contains($serialized,'user_id'),'projection contains no private teaching, identity keys or internal rules');
$app->loadIdentity($owner);$model=$factory->createModel('Dashboard','Site',['ignore_request'=>true]);$model->setState('filter.progress','all');$model->setState('list.limit',20);$model->setState('list.start',20);
dashExpect(count($model->getItems())===5&&(int)$model->getTotal()===25,'native pagination bounded to own history');
dashExpect((bool)$model->getProgressForm()->getField('progress'),'native progress filter form available');
$categoryFixture=json_decode(file_get_contents(dirname($root).'/categories-fixture.json'),true,16,JSON_THROW_ON_ERROR);
$italian=$learning->start($owner,'it-IT',$categoryFixture['italianCourse'],bin2hex(random_bytes(16)));
$c=array_column($read($owner),null,'id');
dashExpect(count($c)===26&&$c[$italian]['access']==='available','history spans installed course languages independently of UI');
dashExpect($learning->resumeTarget($owner,'en-GB',$italian)['language']==='it-IT','resume preserves original course language without mapping enrollment');
$db->setQuery("UPDATE #__languages SET published=0 WHERE lang_code='it-IT'")->execute();
try{$c=array_column($read($owner),null,'id');dashExpect($c[$italian]['access']==='language','unpublished course language removes current availability');dashDenied(fn()=>$learning->resumeTarget($owner,'en-GB',$italian),403,'resume rechecks current content language');}
finally{$db->setQuery("UPDATE #__languages SET published=1 WHERE lang_code='it-IT'")->execute();}
$app->loadIdentity($operator);$menus=[];
foreach(['en-GB','it-IT']as$tag){$alias='dashboard-menu-'.substr($tag,0,2);$table=new \Joomla\CMS\Table\Menu($db);$table->setLocation(1,'last-child');$table->bind(['title'=>$alias,'alias'=>$alias,'menutype'=>'mainmenu','type'=>'component','link'=>'index.php?option=com_jlms&view=dashboard','component_id'=>(int)$db->setQuery("SELECT extension_id FROM #__extensions WHERE type='component' AND element='com_jlms'")->loadResult(),'published'=>1,'access'=>2,'language'=>$tag,'home'=>0,'params'=>'{}','browserNav'=>0]);if(!$table->check()||!$table->store())throw new \RuntimeException('Native dashboard menu failed');$menus[$tag]=(int)$table->id;}
file_put_contents($path,json_encode(['owner'=>(int)$owner->id,'other'=>(int)$other->id,'empty'=>(int)$empty->id,'active'=>$active,'other_enrollment'=>$otherEnrollment,'completed'=>$completed,'suspended'=>$suspended,'cancelled'=>$cancelled,'future'=>$future,'lesson'=>$f['lessons'][1],'group'=>$f['group_id'],'course'=>$f['course_id'],'italian'=>$italian,'menus'=>$menus],JSON_THROW_ON_ERROR));
echo json_encode(['dashboard_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;
