<?php

declare(strict_types=1);


/** Run after Joomla's CLI installer and extension:install in an isolated CI site. */
$root = realpath($argv[1] ?? '');
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new \RuntimeException('Disposable Joomla site required.');
}
if (($argv[2] ?? '') !== 'worker') {
    $fixtureDb = new \PDO(getenv('JLMS_TEST_DSN'), getenv('JLMS_TEST_USER'), getenv('JLMS_TEST_PASSWORD'), [\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION]);
    $oldLanguageFilter = $fixtureDb->query("SELECT enabled FROM joomla_extensions WHERE type='plugin' AND folder='system' AND element='languagefilter'")->fetchColumn();
    $fixtureDb->exec("UPDATE joomla_extensions SET enabled=1 WHERE type='plugin' AND folder='system' AND element='languagefilter'");
    register_shutdown_function(static function () use ($fixtureDb,$oldLanguageFilter): void { $q=$fixtureDb->prepare("UPDATE joomla_extensions SET enabled=? WHERE type='plugin' AND folder='system' AND element='languagefilter'");$q->execute([$oldLanguageFilter]); });
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

$actor=$app->getIdentity();$service=$component->getManualEnrollmentService();$learning=$component->getLearningService();$checks=0;
function preExpect(bool $ok,string $message):void{global$checks;if(!$ok)throw new \RuntimeException($message);$checks++;}
function preDenied(callable $fn,int $code,string $message):void{try{$fn();}catch(\RuntimeException $e){preExpect($e->getCode()===$code,$message.': '.$e->getMessage());return;}throw new \RuntimeException($message);}
function preKey():string{$k=bin2hex(random_bytes(16));return substr($k,0,8).'-'.substr($k,8,4).'-4'.substr($k,13,3).'-a'.substr($k,17,3).'-'.substr($k,20);}
function preCommand(array $i):array{$p=$i['impact'];return [$p['course_id'],$p['user_id'],$p['language'],'PRIVATE_PREASSIGN_REASON',preKey(),$p['hash'],true,true];}
$path=dirname($root).'/preassignment-fixture.json';
if(($argv[2]??'')==='worker'){
 echo "ready\n";fflush(STDOUT);fgets(STDIN);$command=json_decode(file_get_contents(dirname($root).'/preassignment-race.json'),true,16,JSON_THROW_ON_ERROR);echo 'ok:'.$service->apply($actor,...$command)."\n";exit;
}
$f=json_decode(file_get_contents(dirname($root).'/manual-enrollment-fixture.json'),true,16,JSON_THROW_ON_ERROR);$course=(int)$f['course'];$manager=new \Joomla\CMS\User\User((int)$f['manager']);$group=(int)$f['group'];
$update=static function(string $table,int $id,array $fields)use($db):void{$o=(object)(['id'=>$id]+$fields);$db->updateObject('#__'.$table,$o,'id',true);};
if(($argv[2]??'')==='retained'){
 $saved=json_decode(file_get_contents($path),true,16,JSON_THROW_ON_ERROR);$owner=new \Joomla\CMS\User\User($saved['learner']);
 preExpect($service->apply($manager,...$saved['command'])===$saved['enrollment'],'preassignment receipt replay retained');
 preDenied(fn()=>$learning->resumeTarget($owner,'en-GB',$saved['enrollment']),403,'retained assignment still grants no viewing right');
 preExpect((int)$db->setQuery('SELECT completed_total FROM #__jlms_enrollments WHERE id='.(int)$saved['enrollment'])->loadResult()===1,'earned progress retained after later entitlement revocation');
 $log=json_decode($db->setQuery("SELECT message FROM #__action_logs WHERE extension='com_jlms.manualenrollment' AND item_id=".(int)$saved['receipt'])->loadResult(),true,16,JSON_THROW_ON_ERROR);
 preExpect($log['preassignment_requested']===true&&$log['access_at_assignment']===false,'native admission flags retained');
 echo json_encode(['preassignment_retention_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;exit;
}
$users=[];foreach(['audit','race','stale','http','blocked']as$name){$u=new \Joomla\CMS\User\User();$data=['name'=>'CI Preassign '.$name,'username'=>'cipreassign'.$name,'email'=>'cipreassign'.$name.'@example.invalid','password'=>'Ci-only-Preassign-Password-2026','password2'=>'Ci-only-Preassign-Password-2026','groups'=>[2],'block'=>0,'activation'=>''];if(!$u->bind($data)||!$u->save())throw new \RuntimeException($u->getError());$users[$name]=new \Joomla\CMS\User\User((int)$u->id);}
$owner=new \Joomla\CMS\User\User((int)$f['denied_user']);$id=(int)$owner->id;
$groups=$db->setQuery('SELECT user_id,group_id FROM #__user_usergroup_map ORDER BY user_id,group_id')->loadAssocList();
$counts=static fn(int $user)=>[(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_subjects WHERE user_id='.$user)->loadResult(),(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE user_id='.$user.' AND course_id='.$course)->loadResult(),(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_manual_enrollments WHERE enrollment_id IN (SELECT id FROM #__jlms_enrollments WHERE user_id='.$user.' AND course_id='.$course.')')->loadResult()];
$before=$counts($id);preDenied(fn()=>$service->editor($manager,$course,$id,'en-GB'),403,'default admission still requires access');
$i=$service->editor($manager,$course,$id,'en-GB',true);preExpect($i['impact']['preassignment']===true&&!$i['impact']['has_current_access']&&$i['impact']['blocked']===null,'explicit preassignment preview reports missing entitlement');
preExpect($counts($id)===$before,'preassignment preview has no writes');$command=preCommand($i);
$missing=$command;$missing[7]=false;preDenied(fn()=>$service->apply($manager,...$missing),403,'old/default command cannot silently preassign');
preDenied(fn()=>$service->editor($owner,$course,$id,'en-GB',true),403,'learner cannot opt into administrative preassignment');
preDenied(fn()=>$service->editor($manager,(int)$f['other_course'],$id,'en-GB',true),403,'preassignment retains course scope');
$eid=$service->apply($manager,...$command);preExpect($service->apply($manager,...$command)===$eid&&$counts($id)===[1,1,1],'explicit retry yields one assignment and receipt');
$e=$db->setQuery('SELECT status,origin,completed_total,completed_at FROM #__jlms_enrollments WHERE id='.$eid)->loadObject();preExpect($e->status==='active'&&$e->origin==='manual'&&(int)$e->completed_total===0&&$e->completed_at===null,'preassignment records no invented progress or alternate entitlement state');
preDenied(fn()=>$learning->resumeTarget($owner,'en-GB',$eid),403,'preassigned learner cannot resume without level');
preDenied(fn()=>$learning->complete($owner,'en-GB',$eid,(int)$f['lesson'],bin2hex(random_bytes(16))),403,'preassignment does not authorize completion');
$cards=$component->getDashboardService()->cards($owner,'en-GB',$db->setQuery($component->getDashboardService()->query($owner))->loadObjectList());$card=array_values(array_filter($cards,static fn($x)=>$x['id']===$eid))[0];preExpect($card['access']==='access'&&$card['percent']===0,'assigned title/history is visible with blocked classroom');
\Joomla\CMS\User\UserHelper::addUserToGroup($id,$group);
try{
 preExpect($learning->resumeTarget($owner,'en-GB',$eid)['lesson_id']===(int)$f['lesson'],'external core group assignment enables same enrollment');
 preExpect($service->apply($manager,...$command)===$eid,'original explicit replay works after entitlement grant');
 $changed=$command;$changed[7]=false;preDenied(fn()=>$service->apply($manager,...$changed),409,'changing opt-in cannot reuse UUID');
 $learning->complete($owner,'en-GB',$eid,(int)$f['lesson'],bin2hex(random_bytes(16)));
}finally{\Joomla\CMS\User\UserHelper::removeUserFromGroup($id,$group);}
preDenied(fn()=>$learning->resumeTarget($owner,'en-GB',$eid),403,'later external revocation blocks access again');
preExpect((int)$db->setQuery('SELECT completed_total FROM #__jlms_enrollments WHERE id='.$eid)->loadResult()===1,'external revocation preserves earned completion');
$receipt=(int)$db->setQuery('SELECT id FROM #__jlms_manual_enrollments WHERE enrollment_id='.$eid)->loadResult();$log=json_decode($db->setQuery("SELECT message FROM #__action_logs WHERE extension='com_jlms.manualenrollment' AND item_id=".$receipt)->loadResult(),true,16,JSON_THROW_ON_ERROR);
preExpect($log['preassignment_requested']===true&&$log['access_at_assignment']===false&&!str_contains(json_encode($log),'PRIVATE_PREASSIGN_REASON'),'audit records opt-in/eligibility snapshot without internal reason');
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$manager->id,(int)$f['manager_group']);try{preDenied(fn()=>$service->apply($manager,...$command),403,'operator revocation denies explicit replay');}finally{\Joomla\CMS\User\UserHelper::addUserToGroup((int)$manager->id,(int)$f['manager_group']);}
$update('users',$id,['block'=>1]);try{preDenied(fn()=>$service->apply($manager,...$command),403,'blocked learner denies even opt-in replay');}finally{$update('users',$id,['block'=>0]);}
// A normal command keeps the exact0.19 hash formula and is still replayable without the added argument.
$normal=$service->editor($manager,$course,(int)$f['learner'],'en-GB');$oldPlan=$normal['impact'];unset($oldPlan['hash'],$oldPlan['preassignment'],$oldPlan['has_current_access']);preExpect(hash('sha256',\Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition::canonical($oldPlan))===$normal['impact']['hash'],'default preview hash retains0.19 wire format');
$old=$db->setQuery('SELECT * FROM #__jlms_manual_enrollments WHERE id='.(int)$f['receipt'])->loadObject();$oldPayload=['actor'=>(int)$f['manager'],'course'=>$course,'user'=>(int)$f['learner'],'language'=>'en-GB','reason'=>$old->reason,'impact'=>$old->impact_hash];preExpect(hash('sha256',\Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition::canonical($oldPayload))===$old->request_hash,'default receipt retains legacy request hash');preExpect($service->apply($manager,$course,(int)$f['learner'],'en-GB',$old->reason,$old->mutation_id,$old->impact_hash,true)===(int)$f['enrollment'],'default replay remains compatible');
// Native access-level restrictions do not become publication exceptions.
$original=$db->setQuery('SELECT catid,catalog_access,content_access,state FROM #__jlms_courses WHERE id='.$course)->loadObject();$privateLevel=(int)$original->content_access;
$update('jlms_courses',$course,['catalog_access'=>$privateLevel]);
try{preDenied(fn()=>$service->editor($manager,$course,(int)$users['audit']->id,'en-GB'),404,'default still respects private catalog');$private=$service->editor($manager,$course,(int)$users['audit']->id,'en-GB',true);preExpect(!$private['impact']['has_current_access'],'explicit preassignment permits private catalog metadata only for authorized manager');}
finally{$update('jlms_courses',$course,['catalog_access'=>(int)$original->catalog_access]);}
$update('jlms_courses',$course,['state'=>0]);try{preDenied(fn()=>$service->editor($manager,$course,(int)$users['audit']->id,'en-GB',true),404,'unpublished course is not assignable');}finally{$update('jlms_courses',$course,['state'=>1]);}
$app->getInput()->set('extension','com_jlms.course');$cat=$app->bootComponent('com_categories')->getMVCFactory()->createModel('Category','Administrator',['ignore_request'=>true]);if(!$cat->save(['id'=>0,'parent_id'=>1,'extension'=>'com_jlms.course','title'=>'CI Preassignment private category','alias'=>'ci-preassignment-private','published'=>1,'access'=>$privateLevel,'language'=>'*']))throw new \RuntimeException($cat->getError());$catid=(int)$cat->getState('category.id');$update('jlms_courses',$course,['catid'=>$catid]);
try{
 preExpect(!$service->editor($manager,$course,(int)$users['audit']->id,'en-GB',true)['impact']['has_current_access'],'private category can be preassigned without granting its view level');
 $update('categories',$catid,['published'=>0]);preDenied(fn()=>$service->editor($manager,$course,(int)$users['audit']->id,'en-GB',true),404,'unpublished category blocks preassignment');$update('categories',$catid,['published'=>1]);
}finally{$update('jlms_courses',$course,['catid'=>(int)$original->catid]);}
// Published content-language access may be missing; unpublished content languages remain unavailable.
$lang=$db->setQuery("SELECT lang_id,access,published FROM #__languages WHERE lang_code='en-GB'")->loadObject();
$setLang=static function(int $access,int $published)use($db,$lang):void{$q=$db->createQuery()->update('#__languages')->set('access = :access')->set('published = :published')->where('lang_id = :id');$id=(int)$lang->lang_id;$q->bind(':access',$access,\Joomla\Database\ParameterType::INTEGER)->bind(':published',$published,\Joomla\Database\ParameterType::INTEGER)->bind(':id',$id,\Joomla\Database\ParameterType::INTEGER);$db->setQuery($q)->execute();};
try{$setLang($privateLevel,1);preExpect(!$service->editor($manager,$course,(int)$users['audit']->id,'en-GB',true)['impact']['has_current_access'],'language viewing restriction may be preassigned');$setLang($privateLevel,0);preDenied(fn()=>$service->editor($manager,$course,(int)$users['audit']->id,'en-GB',true),403,'unpublished content language is not bypassed');}
finally{$setLang((int)$lang->access,(int)$lang->published);}
$edition=(int)$f['edition'];$update('jlms_editions',$edition,['ends_at'=>'2000-01-01 00:00:00']);try{preDenied(fn()=>$service->editor($manager,$course,(int)$users['audit']->id,'en-GB',true),403,'closed window not bypassed');}finally{$update('jlms_editions',$edition,['ends_at'=>null]);}
$broken=new \Jlms\Component\Jlms\Administrator\Application\ManualEnrollmentService($db,$learning,$component->getCatalogService(),$component->getEnrollmentRosterService(),static function():void{throw new \RuntimeException('COM_JLMS_MANUAL_AUDIT_FAILED',503);});$beforeAudit=$counts((int)$users['audit']->id);$bad=preCommand($service->editor($manager,$course,(int)$users['audit']->id,'en-GB',true));preDenied(fn()=>$broken->apply($manager,...$bad),503,'preassignment audit outage rejects command');preExpect($counts((int)$users['audit']->id)===$beforeAudit,'audit rollback includes subject receipt and assignment');
$stale=preCommand($service->editor($manager,$course,(int)$users['stale']->id,'en-GB',true));\Joomla\CMS\User\UserHelper::addUserToGroup((int)$users['stale']->id,$group);try{preDenied(fn()=>$service->apply($manager,...$stale),409,'eligibility change invalidates explicit preview');}finally{\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$users['stale']->id,$group);}
$race=preCommand($service->editor($actor,$course,(int)$users['race']->id,'en-GB',true));file_put_contents(dirname($root).'/preassignment-race.json',json_encode($race,JSON_THROW_ON_ERROR));$workers=[];
for($x=0;$x<2;$x++){$pipes=[];$proc=proc_open([PHP_BINARY,__FILE__,$root,'worker'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($proc))throw new \RuntimeException('Worker failed');$workers[]=[$proc,$pipes];}
foreach($workers as[$proc,$pipes]){stream_set_timeout($pipes[1],30);if(trim((string)fgets($pipes[1]))!=='ready')throw new \RuntimeException('Worker bootstrap failed');}
$db->transactionStart();$db->setQuery('SELECT id FROM #__jlms_editions WHERE id='.$edition.' FOR UPDATE')->loadResult();foreach($workers as[$proc,$pipes]){fwrite($pipes[0],"go\n");fclose($pipes[0]);}usleep(200000);$db->transactionCommit();$results=[];foreach($workers as[$proc,$pipes]){$out=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($proc)!==0)throw new \RuntimeException($err.$out);$results[]=$out;}
preExpect($results[0]===$results[1]&&str_starts_with($results[0],'ok:')&&$counts((int)$users['race']->id)===[1,1,1],'parallel explicit retries preserve uniqueness');
$used=(int)$db->setQuery("SELECT COUNT(*) FROM #__jlms_enrollments WHERE edition_id=$edition AND status <> 'cancelled'")->loadResult();$update('jlms_editions',$edition,['capacity'=>$used]);try{$full=$service->editor($manager,$course,(int)$users['audit']->id,'en-GB',true);preExpect($full['impact']['blocked']==='COM_JLMS_EDITION_FULL','preassignments consume capacity');preDenied(fn()=>$service->apply($manager,...preCommand($full)),409,'full capacity cannot be bypassed by opt-in');}finally{$update('jlms_editions',$edition,['capacity'=>null]);}
preExpect($groups===$db->setQuery('SELECT user_id,group_id FROM #__user_usergroup_map ORDER BY user_id,group_id')->loadAssocList(),'preassignment never changes native group memberships');
file_put_contents($path,json_encode(['course'=>$course,'learner'=>$id,'enrollment'=>$eid,'receipt'=>$receipt,'command'=>$command,'http_user'=>(int)$users['http']->id,'group'=>$group,'manager'=>(int)$manager->id,'manager_group'=>(int)$f['manager_group'],'lesson'=>(int)$f['lesson']],JSON_THROW_ON_ERROR));
echo json_encode(['preassignment_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;
