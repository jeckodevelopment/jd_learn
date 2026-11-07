<?php

declare(strict_types=1);


/** Run after Joomla's CLI installer and extension:install in an isolated CI site. */
$root = realpath($argv[1] ?? '');
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new \RuntimeException('Disposable Joomla site required.');
}
// Set fixture plugin state before Joomla caches its enabled-plugin list.
// Retained checks must not alter state. Workers inherit the parent's committed setup;
// rewriting extension settings concurrently can deadlock before the test barrier.
if (!in_array(($argv[2] ?? ''), ['retained', 'worker'], true)) {
    $fixtureDb = new \PDO(getenv('JLMS_TEST_DSN'), getenv('JLMS_TEST_USER'), getenv('JLMS_TEST_PASSWORD'), [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    $fixtureDb->exec("UPDATE joomla_extensions SET enabled=1 WHERE type='plugin' AND folder='jlmsvideo'");
    $fixtureDb->exec("UPDATE joomla_extensions SET params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.mail_to_user',0) WHERE type='plugin' AND folder='user' AND element='joomla'");
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



$learning=$component->getLearningService();
if (($argv[2] ?? '') === 'retained') {
    $owner=(int)$db->setQuery("SELECT id FROM #__users WHERE username='cilearner'")->loadResult();
    $saved=$db->setQuery('SELECT id, required_total, completed_total, completed_at FROM #__jlms_enrollments WHERE user_id='.$owner)->loadObject();
    if (!$saved || (int)$saved->required_total!==3 || (int)$saved->completed_total!==3 || !$saved->completed_at
        || $learning->classroom(new \Joomla\CMS\User\User($owner),'en-GB',(int)$saved->id)['percent']!==100) {
        throw new \RuntimeException('Package update lost learning history');
    }
    echo json_encode(['learning_upgrade'=>'passed','required_completed'=>3],JSON_THROW_ON_ERROR).PHP_EOL;exit(0);
}
if (($argv[2] ?? '') === 'worker') {
    echo "ready\n";fflush(STDOUT);fgets(STDIN);
    $learning->complete(new \Joomla\CMS\User\User((int)$argv[3]),'en-GB',(int)$argv[4],(int)$argv[5],$argv[6]);
    echo "worker passed\n";exit(0);
}
if (in_array($argv[2] ?? '',['revoke','grant'],true)) {
    $id=(int)$argv[3];
    $name=$db->setQuery('SELECT username FROM #__users WHERE id='.$id)->loadResult();
    if ($name!=='cilearnerhttp') { throw new \RuntimeException('Not an HTTP test account'); }
    $groupId=(int)$argv[4];
    if ($db->setQuery('SELECT title FROM #__usergroups WHERE id='.$groupId)->loadResult()!=='CI learning entitlement') {throw new \RuntimeException('Not a fixture group');}
    ($argv[2]==='revoke') ? \Joomla\CMS\User\UserHelper::removeUserFromGroup($id,$groupId) : \Joomla\CMS\User\UserHelper::addUserToGroup($id,$groupId);
    echo "fixture access changed\n";exit(0);
}
$checks=0;
function learningExpect(bool $ok,string $message): void {
    global $checks;
    if (!$ok) {throw new \RuntimeException($message);}
    $checks++;
}
function learningDenied(callable $call,int $code,string $message): void {
    try {$call();} catch (\RuntimeException $error) {learningExpect($error->getCode()===$code,$message.'; received '.$error->getMessage().' / '.$error->getCode());return;}
    throw new \RuntimeException($message);
}
function fixtureUpdate(string $table,object $record,bool $nulls=false): void {
    global $db;$db->updateObject('#__'.$table,$record,'id',$nulls);
}
function fixtureStudent(string $username): \Joomla\CMS\User\User {
    global $learningGroup;
    $user=new \Joomla\CMS\User\User();
    $data=['name'=>'CI Learner','username'=>$username,'email'=>$username.'@example.invalid',
        'password'=>'Ci-only-Learner-Password-2026','password2'=>'Ci-only-Learner-Password-2026','groups'=>[2,$learningGroup],'block'=>0,'activation'=>''];
    if (!$user->bind($data) || !$user->save()) {throw new \RuntimeException('Learner fixture failed: '.$user->getError());}
    return new \Joomla\CMS\User\User((int)$user->id);
}
$groupModel=$app->bootComponent('com_users')->getMVCFactory()->createModel('Group','Administrator',['ignore_request'=>true]);
if (!$groupModel->save(['id'=>0,'parent_id'=>2,'title'=>'CI learning entitlement'])) {throw new \RuntimeException('Core group fixture failed: '.$groupModel->getError());}
$learningGroup=(int)$groupModel->getState('group.id');
$level=(object)['title'=>'CI learning access','ordering'=>0,'rules'=>json_encode([$learningGroup],JSON_THROW_ON_ERROR)];
$db->insertObject('#__viewlevels',$level,'id');$learningLevel=(int)$level->id;
$student=fixtureStudent('cilearner');$other=fixtureStudent('cilearnerother');$httpStudent=fixtureStudent('cilearnerhttp');
$actor=$app->getIdentity();$authoring=$component->getCurriculumService();$publication=$component->getPublicationService();
$catid=(int)$db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
if (!$model->save(['id'=>0,'revision'=>1,'title'=>'Learning fixture','alias'=>'learning-fixture','catid'=>$catid,
    'language'=>'*','catalog_access'=>1,'content_access'=>$learningLevel,'enrollment_mode'=>'auto','presentation_description'=>'<p>Course presentation for the publication fixture.</p>', 'summary'=>'Text learning fixture'])) {throw new \RuntimeException($model->getError());}
$course=(int)$model->getState('course.id');$version=$authoring->createDraft($actor,$course,1);
$scope=static function()use($db,$course,$version):array{return ['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$version)->loadResult()];};
$module=$authoring->saveModule($actor,$scope()+['id'=>0,'title'=>'Learning chapter']);
$lessons=[];
for($i=1;$i<=4;$i++) {
 $lessons[]=$authoring->saveLesson($actor,$scope()+['id'=>0,'module_id'=>$module,'title'=>'Learning lesson '.$i,'kind'=>'text',
  'required'=>$i===4?'0':'1','body'=>'<p>LEARNING_PRIVATE_BODY_'.$i.'</p><img src="x" onerror="alert(1)"><script>alert(2)</script>']);
}
$cr=(int)$db->setQuery('SELECT revision FROM #__jlms_courses WHERE id='.$course)->loadResult();
$publication->transition($actor,$course,$version,$cr,$scope()['expected_version_revision'],(int)$publication->transitions($actor,$course)[0]->id);
$key=bin2hex(random_bytes(16));$enrollment=$learning->start($student,'en-GB',$course,$key);
learningExpect($learning->start($student,'en-GB',$course,$key)===$enrollment,'same start key reuses enrollment');
learningExpect($learning->start($student,'en-GB',$course,bin2hex(random_bytes(16)))===$enrollment,'natural enrollment uniqueness across keys');
learningExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE user_id='.(int)$student->id)->loadResult()===1,'one enrollment persisted');
learningExpect($db->setQuery('SELECT started_at FROM #__jlms_enrollments WHERE id='.$enrollment)->loadResult()===null,'enrollment alone grants no learning credit');
$editionForCapacity=(int)$db->setQuery('SELECT edition_id FROM #__jlms_enrollments WHERE id='.$enrollment)->loadResult();
fixtureUpdate('jlms_editions',(object)['id'=>$editionForCapacity,'capacity'=>1]);
learningDenied(fn()=>$learning->start($other,'en-GB',$course,bin2hex(random_bytes(16))),409,'edition capacity excludes additional enrollment');
fixtureUpdate('jlms_editions',(object)['id'=>$editionForCapacity,'capacity'=>null],true);
$otherCourse=(int)$db->setQuery('SELECT id FROM #__jlms_courses WHERE state=1 AND id<>'.$course.' LIMIT 1')->loadResult();
learningDenied(fn()=>$learning->start($student,'en-GB',$otherCourse,$key),409,'start key cannot be reused for another course');
learningExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE user_id='.(int)$student->id)->loadResult()===1,'conflicting start rolls back new enrollment');
$view=$learning->classroom($student,'en-GB',$enrollment);
learningExpect($view['percent']===0 && str_contains($view['lesson']->body,'LEARNING_PRIVATE_BODY_1'),'authorised student reads first text');
learningExpect(!str_contains($view['lesson']->body,'<script') && !str_contains($view['lesson']->body,'onerror'),'delivery strips active markup');
learningDenied(fn()=>$learning->classroom($other,'en-GB',$enrollment),404,'foreign enrollment hidden');
learningDenied(fn()=>$learning->classroom(new \Joomla\CMS\User\User(),'en-GB',$enrollment),401,'guest classroom rejected');
learningDenied(fn()=>$learning->complete($other,'en-GB',$enrollment,$lessons[0],bin2hex(random_bytes(16))),404,'foreign completion rejected');
$foreign=(int)$db->setQuery('SELECT id FROM #__jlms_lessons WHERE course_version_id<>'.$version.' LIMIT 1')->loadResult();
learningDenied(fn()=>$learning->classroom($student,'en-GB',$enrollment,$foreign),404,'foreign lesson hidden');
learningDenied(fn()=>$learning->complete($student,'en-GB',$enrollment,$foreign,bin2hex(random_bytes(16))),404,'foreign lesson completion rejected');
// Independent PHP processes use the actual database locks, without a pcntl dependency.
function learningWorkers(array $lessonIds): void {
 global $student,$enrollment,$root;
 $workers=[];
 foreach($lessonIds as $lessonId) {
  $pipes=[];$process=proc_open([PHP_BINARY,__FILE__,$root,'worker',(string)$student->id,(string)$enrollment,(string)$lessonId,bin2hex(random_bytes(16))],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
  if (!is_resource($process)) {throw new \RuntimeException('Worker could not start');}
  if (trim((string)fgets($pipes[1]))!=='ready') {throw new \RuntimeException('Worker barrier failed');}
  $workers[]=[$process,$pipes];
 }
 foreach($workers as [$process,$pipes]) {fwrite($pipes[0],"go\n");fclose($pipes[0]);}
 foreach($workers as [$process,$pipes]) {
  $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
  if (proc_close($process)!==0) {throw new \RuntimeException('Concurrent completion failed: '.$out.' '.$err);}
 }
}
learningWorkers([$lessons[0],$lessons[0]]);
learningExpect($learning->classroom($student,'en-GB',$enrollment)['percent']===33,'concurrent duplicate completion counts once and rounds down');
$completeKey=bin2hex(random_bytes(16));$learning->complete($student,'en-GB',$enrollment,$lessons[0],$completeKey);
$learning->complete($student,'en-GB',$enrollment,$lessons[0],$completeKey);
learningDenied(fn()=>$learning->complete($student,'en-GB',$enrollment,$lessons[1],$completeKey),409,'same key with different payload rejected');
$learning->complete($student,'en-GB',$enrollment,$lessons[3],bin2hex(random_bytes(16)));
learningExpect($learning->classroom($student,'en-GB',$enrollment)['percent']===33,'optional lesson gives no required credit');
// Revoke a core group while keeping the same User object with its old cached access levels.
$student->getAuthorisedViewLevels();\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$student->id,$learningGroup);
learningDenied(fn()=>$learning->classroom($student,'en-GB',$enrollment),403,'current group revocation overrides cached identity');
learningDenied(fn()=>$learning->complete($student,'en-GB',$enrollment,$lessons[0],$completeKey),403,'idempotent replay still checks current entitlement');
learningDenied(fn()=>$learning->start($student,'en-GB',$course,$key),403,'start replay cannot restore revoked access');
\Joomla\CMS\User\UserHelper::addUserToGroup((int)$student->id,$learningGroup);
learningExpect($learning->classroom($student,'en-GB',$enrollment)['percent']===33,'restoring group retains history');
fixtureUpdate('users',(object)['id'=>(int)$student->id,'block'=>1]);
learningDenied(fn()=>$learning->classroom($student,'en-GB',$enrollment),403,'fresh account block overrides session state');
fixtureUpdate('users',(object)['id'=>(int)$student->id,'block'=>0]);
fixtureUpdate('jlms_enrollments',(object)['id'=>$enrollment,'status'=>'suspended']);
learningDenied(fn()=>$learning->start($student,'en-GB',$course,$key),403,'start cannot reactivate a suspension');
fixtureUpdate('jlms_enrollments',(object)['id'=>$enrollment,'status'=>'active','ends_at'=>'2000-01-01 00:00:00']);
learningDenied(fn()=>$learning->classroom($student,'en-GB',$enrollment),403,'expired educational window denied');
fixtureUpdate('jlms_enrollments',(object)['id'=>$enrollment,'ends_at'=>null],true);
$edition=(int)$db->setQuery('SELECT edition_id FROM #__jlms_enrollments WHERE id='.$enrollment)->loadResult();
fixtureUpdate('jlms_editions',(object)['id'=>$edition,'status'=>'closed']);
learningDenied(fn()=>$learning->complete($student,'en-GB',$enrollment,$lessons[1],bin2hex(random_bytes(16))),403,'closed edition rejects progress');
fixtureUpdate('jlms_editions',(object)['id'=>$edition,'status'=>'open']);
fixtureUpdate('categories',(object)['id'=>$catid,'published'=>0]);
learningDenied(fn()=>$learning->classroom($student,'en-GB',$enrollment),404,'category withdrawal blocks direct classroom');
fixtureUpdate('categories',(object)['id'=>$catid,'published'=>1]);
// Removing a course from the promotional catalog does not suspend a valid enrollment.
$catalog=$component->getCatalogService();
fixtureUpdate('jlms_courses',(object)['id'=>$course,'state'=>0,'publish_down'=>'2000-01-01 00:00:00']);
learningDenied(fn()=>$catalog->course($student,'en-GB',$course),404,'withdrawn course is absent from the promotional catalog');
learningDenied(fn()=>$learning->start($student,'en-GB',$course,bin2hex(random_bytes(16))),404,'withdrawn course cannot accept a new self-service admission');
learningExpect($learning->classroom($student,'en-GB',$enrollment)['percent']===33,'catalog withdrawal and end date preserve the authorised existing classroom');
fixtureUpdate('jlms_courses',(object)['id'=>$course,'state'=>1,'publish_down'=>null],true);
learningWorkers([$lessons[1],$lessons[2]]);
$done=$learning->classroom($student,'en-GB',$enrollment);
learningExpect($done['percent']===100 && $done['completed_at']!==null,'concurrent distinct requirements complete course once');
$stamp=$done['completed_at'];$learning->complete($student,'en-GB',$enrollment,$lessons[1],bin2hex(random_bytes(16)));
learningExpect($learning->classroom($student,'en-GB',$enrollment)['completed_at']===$stamp,'completion date is immutable on replay');
learningExpect((int)$db->setQuery('SELECT completed_total FROM #__jlms_enrollments WHERE id='.$enrollment)->loadResult()===3,'required counter is exact');
learningExpect(array_map('intval',$db->setQuery('SELECT group_id FROM #__user_usergroup_map WHERE user_id='.(int)$student->id.' ORDER BY group_id')->loadColumn())===[2,$learningGroup],'LMS has assigned no additional groups');
fixtureUpdate('jlms_courses',(object)['id'=>$course,'enrollment_mode'=>'approval']);
learningDenied(fn()=>$learning->start($httpStudent,'en-GB',$course,bin2hex(random_bytes(16))),409,'approval mode cannot bypass request workflow');
fixtureUpdate('jlms_courses',(object)['id'=>$course,'enrollment_mode'=>'auto']);
file_put_contents(dirname($root).'/learning-fixture.json',json_encode(['course_id'=>$course,'lessons'=>$lessons,'http_user_id'=>(int)$httpStudent->id,'group_id'=>$learningGroup],JSON_THROW_ON_ERROR));
echo json_encode(['learning_checks'=>$checks,'status'=>'passed','concurrent_workers'=>4],JSON_THROW_ON_ERROR).PHP_EOL;
