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

$actor=$app->getIdentity();$service=$component->getManualEnrollmentService();$learning=$component->getLearningService();$checks=0;
function manualExpect(bool $ok,string $message):void{global$checks;if(!$ok)throw new \RuntimeException($message);$checks++;}
function manualDenied(callable $fn,int $code,string $message):void{try{$fn();}catch(\RuntimeException $e){manualExpect($e->getCode()===$code,$message.': '.$e->getMessage());return;}throw new \RuntimeException($message);}
function manualKey():string{$k=bin2hex(random_bytes(16));return substr($k,0,8).'-'.substr($k,8,4).'-4'.substr($k,13,3).'-a'.substr($k,17,3).'-'.substr($k,20);}
function manualCommand(array $i):array{$p=$i['impact'];return [$p['course_id'],$p['user_id'],$p['language'],'PRIVATE_MANUAL_REASON',manualKey(),$p['hash'],true];}
$path=dirname($root).'/manual-enrollment-fixture.json';
if(($argv[2]??'')==='worker'){
 echo "ready\n";fflush(STDOUT);fgets(STDIN);$race=json_decode(file_get_contents(dirname($root).'/manual-enrollment-race.json'),true,16,JSON_THROW_ON_ERROR);$command=$race['commands'][(int)($argv[4]??0)];
 try{$id=($argv[3]??'')==='auto'?$learning->start(new \Joomla\CMS\User\User($command[1]),'en-GB',$command[0],bin2hex(random_bytes(16))):$service->apply($actor,...$command);echo 'ok:'.$id."\n";}catch(\RuntimeException $e){if($e->getCode()!==409)throw $e;echo "conflict\n";}exit;
}
if(($argv[2]??'')==='retained'){
 $f=json_decode(file_get_contents($path),true,16,JSON_THROW_ON_ERROR);$e=$db->setQuery('SELECT * FROM #__jlms_enrollments WHERE id='.(int)$f['enrollment'])->loadObject();
 manualExpect($e&&$e->origin==='manual'&&(int)$e->user_id===$f['learner']&&(int)$e->created_by===$f['manager'],'manual assignment provenance retained');
 manualExpect($learning->resumeTarget(new \Joomla\CMS\User\User($f['learner']),'en-GB',(int)$e->id)['lesson_id']===$f['lesson'],'manual learner remains able to resume');
 manualExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_manual_enrollments WHERE enrollment_id='.(int)$e->id)->loadResult()===1,'single manual receipt retained');
 manualExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__action_logs WHERE extension='com_jlms.manualenrollment' AND item_id=".(int)$f['receipt'])->loadResult()===1,'native manual audit retained');
 echo json_encode(['manual_enrollment_retention_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;exit;
}
$f=json_decode(file_get_contents(dirname($root).'/dashboard-fixture.json'),true,16,JSON_THROW_ON_ERROR);$rf=json_decode(file_get_contents(dirname($root).'/roster-fixture.json'),true,16,JSON_THROW_ON_ERROR);$group=(int)$f['group'];
$level=(int)$db->setQuery("SELECT id FROM #__viewlevels WHERE title='CI learning access'")->loadResult();$catid=(int)$db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);if(!$model->save(['id'=>0,'revision'=>1,'title'=>'Manual admission fixture','alias'=>'manual-admission-fixture','catid'=>$catid,'language'=>'*','catalog_access'=>1,'content_access'=>$level,'enrollment_mode'=>'explicit','summary'=>'Manual admission']))throw new \RuntimeException($model->getError());
$course=(int)$model->getState('course.id');$authoring=$component->getCurriculumService();$version=$authoring->createDraft($actor,$course,1);
$scope=static fn():array=>['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$version)->loadResult()];
$module=$authoring->saveModule($actor,$scope()+['id'=>0,'title'=>'Manual admission chapter']);$lesson=$authoring->saveLesson($actor,$scope()+['id'=>0,'module_id'=>$module,'title'=>'Manual admission lesson','kind'=>'text','required'=>'1','body'=>'<p>MANUAL_PRIVATE_BODY</p>']);
$pub=$component->getPublicationService();$pub->transition($actor,$course,$version,(int)$db->setQuery('SELECT revision FROM #__jlms_courses WHERE id='.$course)->loadResult(),$scope()['expected_version_revision'],(int)$pub->transitions($actor,$course)[0]->id);
$users=[];foreach(['one','failure','race','capacity1','capacity2','automatic','snapshot','http','denied']as$name){$u=new \Joomla\CMS\User\User();$data=['name'=>'CI Manual '.$name,'username'=>'cimanual'.$name,'email'=>'cimanual'.$name.'@example.invalid','password'=>'Ci-only-Manual-Password-2026','password2'=>'Ci-only-Manual-Password-2026','groups'=>$name==='denied'?[2]:[2,$group],'block'=>0,'activation'=>''];if(!$u->bind($data)||!$u->save())throw new \RuntimeException($u->getError());$users[$name]=new \Joomla\CMS\User\User((int)$u->id);}
$manager=new \Joomla\CMS\User\User((int)$rf['manager']);$asset=new \Joomla\CMS\Table\Asset($db);$asset->loadByName('com_jlms.course.'.$course);$rules=json_decode($asset->rules,true);$rules['jlms.enrollments.manage'][(string)$rf['group']]=true;$asset->rules=json_encode($rules,JSON_THROW_ON_ERROR);$asset->store();
$groups=$db->setQuery('SELECT user_id,group_id FROM #__user_usergroup_map ORDER BY user_id,group_id')->loadAssocList();
$counts=static fn()=>[(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id='.$course)->loadResult(),(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_manual_enrollments')->loadResult(),(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_subjects')->loadResult()];
$before=$counts();$i=$service->editor($manager,$course,(int)$users['one']->id,'en-GB');$edition=$i['impact']['edition_id'];
manualExpect($counts()===$before&&$i['impact']['blocked']===null,'preview creates no subject enrollment or receipt');
manualDenied(fn()=>$service->editor(new \Joomla\CMS\User\User(),$course,(int)$users['one']->id,'en-GB'),401,'anonymous manual admission denied');
manualDenied(fn()=>$service->editor($users['one'],$course,(int)$users['one']->id,'en-GB'),403,'learner cannot admit themselves manually');
manualDenied(fn()=>$service->editor($manager,(int)$rf['other_course'],(int)$users['one']->id,'en-GB'),403,'management is course scoped');
manualDenied(fn()=>$service->editor($manager,$course,(int)$users['denied']->id,'en-GB'),403,'manual admission cannot grant missing level');
manualDenied(fn()=>$service->editor($manager,$course,2147483647,'en-GB'),404,'unknown core account rejected');
manualDenied(fn()=>$service->editor($manager,$course,(int)$users['one']->id,'zz-ZZ'),400,'uninstalled language rejected');
$command=manualCommand($i);foreach([[6,false,400],[3,'',422],[3,'<script>',422],[5,str_repeat('0',64),409]]as[$index,$value,$code]){$bad=$command;$bad[$index]=$value;manualDenied(fn()=>$service->apply($manager,...$bad),$code,'invalid manual command rejected');}
manualExpect($counts()===$before,'invalid confirmations and stale preview have no writes');
$id=$service->apply($manager,...$command);$record=$db->setQuery('SELECT * FROM #__jlms_enrollments WHERE id='.$id)->loadObject();
manualExpect($record->origin==='manual'&&(int)$record->created_by===(int)$manager->id&&(int)$record->user_id===(int)$users['one']->id,'manual creation has operator and learner provenance');
manualExpect((int)$record->required_total===1&&(int)$record->completed_total===0&&$record->completed_at===null,'new assignment carries no invented progress');
manualExpect($learning->resumeTarget($users['one'],'en-GB',$id)['lesson_id']===$lesson,'manual learner can enter published classroom');
$after=$counts();manualExpect($service->apply($manager,...$command)===$id&&$counts()===$after,'identical retry returns one enrollment and receipt');
$changed=$command;$changed[3]='Changed reason';manualDenied(fn()=>$service->apply($manager,...$changed),409,'same UUID different payload rejected');
manualExpect($service->editor($manager,$course,(int)$users['one']->id,'en-GB')['impact']['blocked']==='COM_JLMS_MANUAL_EXISTS','existing assignment blocks distinct creation');
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$manager->id,(int)$rf['group']);try{manualDenied(fn()=>$service->apply($manager,...$command),403,'revoked operator cannot replay');}finally{\Joomla\CMS\User\UserHelper::addUserToGroup((int)$manager->id,(int)$rf['group']);}
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$users['one']->id,$group);try{manualDenied(fn()=>$service->apply($manager,...$command),403,'revoked learner cannot replay admission');}finally{\Joomla\CMS\User\UserHelper::addUserToGroup((int)$users['one']->id,$group);}
$update=static function(string $table,int $id,array $fields)use($db):void{$o=(object)(['id'=>$id]+$fields);$db->updateObject('#__'.$table,$o,'id',true);};
$update('users',(int)$users['failure']->id,['block'=>1]);try{manualDenied(fn()=>$service->editor($actor,$course,(int)$users['failure']->id,'en-GB'),403,'blocked account rejected');}finally{$update('users',(int)$users['failure']->id,['block'=>0]);}
$broken=new \Jlms\Component\Jlms\Administrator\Application\ManualEnrollmentService($db,$learning,$component->getCatalogService(),$component->getEnrollmentRosterService(),static function():void{throw new \RuntimeException('COM_JLMS_MANUAL_AUDIT_FAILED',503);});
$beforeFailure=$counts();$fail=manualCommand($service->editor($actor,$course,(int)$users['failure']->id,'en-GB'));manualDenied(fn()=>$broken->apply($actor,...$fail),503,'audit failure is not a successful admission');manualExpect($counts()===$beforeFailure,'audit failure rolls back subject enrollment and receipt');
$update('jlms_courses',$course,['enrollment_mode'=>'approval']);try{manualDenied(fn()=>$service->editor($actor,$course,(int)$users['failure']->id,'en-GB'),409,'approval flow cannot be bypassed');}finally{$update('jlms_courses',$course,['enrollment_mode'=>'explicit']);}
$update('jlms_courses',$course,['state'=>0]);try{manualDenied(fn()=>$service->editor($actor,$course,(int)$users['failure']->id,'en-GB'),404,'unpublished course rejected');}finally{$update('jlms_courses',$course,['state'=>1]);}
$update('jlms_editions',$edition,['ends_at'=>'2000-01-01 00:00:00']);try{manualDenied(fn()=>$service->editor($actor,$course,(int)$users['failure']->id,'en-GB'),403,'expired default edition rejected');}finally{$update('jlms_editions',$edition,['ends_at'=>null]);}
$state=$component->getEnrollmentStateService();$si=$state->editor($actor,$course,$id,'suspended');$state->apply($actor,$course,$id,$si['impact']['enrollment_revision'],'suspended','Fixture suspension',manualKey(),$si['impact']['hash'],true);
manualExpect($service->apply($manager,...$command)===$id&&$service->editor($manager,$course,(int)$users['one']->id,'en-GB')['impact']['existing_status']==='suspended','creation replay never reactivates an existing enrollment');
$si=$state->editor($actor,$course,$id,'active');$state->apply($actor,$course,$id,$si['impact']['enrollment_revision'],'active','Fixture reactivation',manualKey(),$si['impact']['hash'],true);
$runRace=static function(array $commands,array $modes)use($db,$root,$edition,$course):array{file_put_contents(dirname($root).'/manual-enrollment-race.json',json_encode(['commands'=>$commands],JSON_THROW_ON_ERROR));$workers=[];
 foreach($modes as$index=>$mode){$pipes=[];$proc=proc_open([PHP_BINARY,__FILE__,$root,'worker',$mode,(string)$index],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($proc))throw new \RuntimeException('Worker failed');$workers[]=[$proc,$pipes];}
 foreach($workers as[$proc,$pipes]){stream_set_timeout($pipes[1],30);if(trim((string)fgets($pipes[1]))!=='ready')throw new \RuntimeException('Worker bootstrap failed');}
 $db->transactionStart();$db->setQuery('SELECT id FROM #__jlms_editions WHERE id='.$edition.' FOR UPDATE')->loadResult();foreach($workers as[$proc,$pipes]){fwrite($pipes[0],"go\n");fclose($pipes[0]);}usleep(200000);$db->transactionCommit();$result=[];
 foreach($workers as[$proc,$pipes]){$out=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($proc)!==0)throw new \RuntimeException($err.$out);$result[]=$out;}return$result;};
$race=manualCommand($service->editor($actor,$course,(int)$users['race']->id,'en-GB'));$result=$runRace([$race,$race],['manual','manual']);manualExpect($result[0]===$result[1]&&str_starts_with($result[0],'ok:'),'simultaneous identical retries return one result');
$used=(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE edition_id='.$edition)->loadResult();$update('jlms_editions',$edition,['capacity'=>$used+1]);
$c1=manualCommand($service->editor($actor,$course,(int)$users['capacity1']->id,'en-GB'));$c2=manualCommand($service->editor($actor,$course,(int)$users['capacity2']->id,'en-GB'));$result=$runRace([$c1,$c2],['manual','manual']);
manualExpect(count(array_filter($result,static fn($v)=>$v==='conflict'))===1&&(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE edition_id='.$edition)->loadResult()===$used+1,'simultaneous capacity boundary cannot overbook');
manualExpect($service->editor($actor,$course,(int)$users['failure']->id,'en-GB')['impact']['blocked']==='COM_JLMS_EDITION_FULL','full edition has no admission action');$update('jlms_editions',$edition,['capacity'=>null]);
$update('jlms_courses',$course,['enrollment_mode'=>'auto']);$auto=manualCommand($service->editor($actor,$course,(int)$users['automatic']->id,'en-GB'));$result=$runRace([$auto,$auto],['manual','auto']);
manualExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id='.$course.' AND user_id='.(int)$users['automatic']->id)->loadResult()===1,'automatic start racing manual admission yields one enrollment');
// Deterministic reproduction: the automatic worker must wait for this edition.
// Commit a competing admission while it waits, then require it to reuse that row.
// SHOW FULL PROCESSLIST exposes only this disposable database user's connections;
// no timing-only assertion or privileged server instrumentation is required.
$snapshotUser = (int) $users['snapshot']->id;
$subject = manualKey();
$subjectRecord = (object) ['subject_key' => $subject, 'user_id' => $snapshotUser, 'created_at' => gmdate('Y-m-d H:i:s')];
$db->insertObject('#__jlms_subjects', $subjectRecord);
file_put_contents(dirname($root) . '/manual-enrollment-race.json', json_encode(['commands' => [[$course, $snapshotUser]]], JSON_THROW_ON_ERROR));
$pipes = [];
$worker = proc_open([PHP_BINARY, __FILE__, $root, 'worker', 'auto', '0'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
if (!is_resource($worker)) { throw new RuntimeException('Snapshot worker failed'); }
stream_set_timeout($pipes[1], 30);
if (trim((string) fgets($pipes[1])) !== 'ready') { throw new RuntimeException('Snapshot worker bootstrap failed'); }
$db->transactionStart();
try {
    $db->setQuery('SELECT id FROM #__jlms_editions WHERE id=' . $edition . ' FOR UPDATE')->loadResult();
    fwrite($pipes[0], "go\n"); fclose($pipes[0]);
    $waiting = false;
    for ($poll = 0; $poll < 100; $poll++) {
        foreach ($db->setQuery('SHOW FULL PROCESSLIST')->loadAssocList() as $process) {
            if (str_contains((string) $process['Info'], 'jlms_editions') && str_contains((string) $process['Info'], 'FOR UPDATE')) { $waiting = true; break; }
        }
        if ($waiting) { break; }
        usleep(50000);
    }
    manualExpect($waiting, 'Automatic worker reached the blocked edition lock');
    $winner = (object) ['user_id' => $snapshotUser, 'subject_key' => $subject, 'course_id' => $course,
        'course_version_id' => $version, 'edition_id' => $edition, 'status' => 'active', 'origin' => 'start',
        'required_total' => 1, 'completed_total' => 0, 'revision' => 1, 'created_by' => $snapshotUser,
        'modified_by' => $snapshotUser, 'created_at' => gmdate('Y-m-d H:i:s'), 'modified_at' => gmdate('Y-m-d H:i:s')];
    $db->insertObject('#__jlms_enrollments', $winner, 'id');
    $db->transactionCommit();
} catch (Throwable $error) {
    $db->transactionRollback(); proc_terminate($worker);
    throw $error;
}
$result = trim(stream_get_contents($pipes[1])); $error = stream_get_contents($pipes[2]);
fclose($pipes[1]); fclose($pipes[2]);
if (proc_close($worker) !== 0) { throw new RuntimeException($error . $result); }
manualExpect($result === 'ok:' . $winner->id, 'Automatic start sees the admission committed while its edition lock was blocked');
manualExpect((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE user_id=' . $snapshotUser . ' AND edition_id=' . $edition)->loadResult() === 1, 'Blocked automatic retry retains one enrollment');

manualExpect($learning->start($users['one'],'en-GB',$course,bin2hex(random_bytes(16)))===$id,'automatic start reuses manual assignment');$update('jlms_courses',$course,['enrollment_mode'=>'explicit']);
manualExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_manual_enrollments WHERE enrollment_id IS NULL OR course_version_id IS NULL')->loadResult()===0,'no incomplete receipt committed');
manualExpect($groups===$db->setQuery('SELECT user_id,group_id FROM #__user_usergroup_map ORDER BY user_id,group_id')->loadAssocList(),'admission never changes native group assignments');
$receipt=(int)$db->setQuery('SELECT id FROM #__jlms_manual_enrollments WHERE enrollment_id='.$id)->loadResult();$log=$db->setQuery("SELECT message FROM #__action_logs WHERE extension='com_jlms.manualenrollment' AND item_id=".$receipt)->loadResult();$message=json_decode($log,true,16,JSON_THROW_ON_ERROR);manualExpect($message['enrollment_id']===$id&&!str_contains($log,'PRIVATE_MANUAL_REASON'),'native audit references internal reason without copying it');
$projection=json_encode($service->editor($actor,$course,(int)$users['one']->id,'en-GB'),JSON_THROW_ON_ERROR);foreach(['subject_key','claim_token','request_hash','rules_json','MANUAL_PRIVATE_BODY','email']as$secret)manualExpect(!str_contains($projection,$secret),'preview excludes '.$secret);
file_put_contents($path,json_encode(['course'=>$course,'version'=>$version,'edition'=>$edition,'lesson'=>$lesson,'enrollment'=>$id,'learner'=>(int)$users['one']->id,'http_user'=>(int)$users['http']->id,'denied_user'=>(int)$users['denied']->id,'group'=>$group,'manager'=>(int)$manager->id,'manager_group'=>(int)$rf['group'],'other_course'=>(int)$rf['other_course'],'receipt'=>$receipt],JSON_THROW_ON_ERROR));
echo json_encode(['manual_enrollment_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;
