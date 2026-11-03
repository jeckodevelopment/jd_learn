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
    $fixtureDb->exec("UPDATE joomla_extensions SET enabled=1 WHERE type='plugin' AND folder='jlmsvideo'");
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





require __DIR__ . '/cloudflare-fixture.php';
use Jlms\Plugin\Jlmsvideo\Cloudflarestream\Provider\Provider;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
use Jlms\Component\Jlms\Administrator\Application\VideoProgressService;
$checks=0;
function streamExpect(bool $value,string $message): void {global $checks;if(!$value)throw new \RuntimeException($message);$checks++;}
function streamDenied(callable $work,string $key): void {
 try{$work();}catch(\RuntimeException $e){streamExpect($e->getMessage()===$key,$key.': '.$e->getMessage());return;}throw new \RuntimeException('Expected '.$key);
}
[$config,$secrets,$transport,$remote]=cloudflareFixture();
$provider=new Provider($config,$secrets,$transport);$registry=new \Jlms\Contracts\Video\VideoProviderRegistry();
$registry->register($provider->getDescriptor(),static fn()=>$provider);
$authoring=new CurriculumService($db,static fn(string $body):string=>\Joomla\CMS\Component\ComponentHelper::filterText($body),static fn()=>$registry);
$component->setCurriculumService($authoring);$learning=$component->getLearningService();$publication=$component->getPublicationService();$actor=$app->getIdentity();
$student=new \Joomla\CMS\User\User((int)$db->setQuery("SELECT id FROM #__users WHERE username='cilearner'")->loadResult());
$other=new \Joomla\CMS\User\User((int)$db->setQuery("SELECT id FROM #__users WHERE username='cilearnerother'")->loadResult());
$group=(int)$db->setQuery("SELECT id FROM #__usergroups WHERE title='CI learning entitlement'")->loadResult();
$level=(int)$db->setQuery("SELECT id FROM #__viewlevels WHERE title='CI learning access'")->loadResult();
$catid=(int)$db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
if(!$model->save(['id'=>0,'revision'=>1,'title'=>'Stream fixture','alias'=>'stream-fixture','catid'=>$catid,'language'=>'*','catalog_access'=>1,'content_access'=>$level,'enrollment_mode'=>'auto','summary'=>'Stream fixture']))throw new \RuntimeException($model->getError());
$course=(int)$model->getState('course.id');$version=$authoring->createDraft($actor,$course,1);
$scope=static fn():array=>['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$version)->loadResult()];
$module=$authoring->saveModule($actor,$scope()+['id'=>0,'title'=>'Stream chapter']);
$lesson=$authoring->saveLesson($actor,$scope()+['id'=>0,'module_id'=>$module,'title'=>'Protected Stream lesson','kind'=>'video','required'=>'1',
 'body'=>'','provider_key'=>'cloudflarestream','source_reference'=>str_repeat('b',32),'duration_seconds'=>'10','duration_confirmed'=>'1']);
$transition=(int)$publication->transitions($actor,$course)[0]->id;
$publish=static fn()=>$publication->transition($actor,$course,$version,(int)$db->setQuery('SELECT revision FROM #__jlms_courses WHERE id='.$course)->loadResult(),$scope()['expected_version_revision'],$transition);
$remote->protected=false;streamDenied($publish,'COM_JLMS_VIDEO_REMOTE_UNPROTECTED');
streamExpect($db->setQuery('SELECT status FROM #__jlms_course_versions WHERE id='.$version)->loadResult()==='draft','unsafe remote video leaves draft untouched');
$remote->protected=true;
// Verify that provider HTTP work does not hold course/enrollment row locks: another connection can lock them.
$remote->callback=static function(string $method)use($course):void{
 $pdo=new \PDO(getenv('JLMS_TEST_DSN'),getenv('JLMS_TEST_USER'),getenv('JLMS_TEST_PASSWORD'),[\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION]);
 $pdo->exec('SET innodb_lock_wait_timeout=1');$pdo->beginTransaction();
 $pdo->query('SELECT id FROM joomla_jlms_courses WHERE id='.$course.' FOR UPDATE');
 $pdo->query('SELECT id FROM joomla_jlms_enrollments WHERE course_id='.$course.' FOR UPDATE');$pdo->rollBack();
};
$publish();streamExpect($db->setQuery('SELECT status FROM #__jlms_course_versions WHERE id='.$version)->loadResult()==='published','verified provider permits publication');
$enrollment=$learning->start($student,'en-GB',$course,bin2hex(random_bytes(16)));
$now=(int)floor(microtime(true)*1000);$service=new VideoProgressService($db,$learning,$authoring,static function()use(&$now):int{return $now;});
$count=count($remote->calls);streamDenied(fn()=>$service->playback($other,'en-GB',$enrollment,$lesson,true),'COM_JLMS_RESOURCE_NOT_FOUND');
streamExpect(count($remote->calls)===$count,'foreign enrollment makes no provider call');
$grant=$service->playback($student,'en-GB',$enrollment,$lesson,true);
streamExpect($grant['provider']==='cloudflarestream' && isset($grant['options']['token']) && !isset($grant['options']['reference']),'only temporary token reaches player');
$session=$grant['session_id'];$revision=$grant['source_revision'];
streamDenied(fn()=>$service->renew($student,'en-GB',$enrollment,$lesson,$session,$revision,true),'COM_JLMS_VIDEO_CONFLICT');
$event=static fn(int $id,string $type,int $p,int $t):array=>['id'=>$id,'type'=>$type,'position_ms'=>$p,'client_ms'=>$t,'rate_milli'=>1000,'playing'=>true];
$batch=json_encode([$event(1,'play',0,0),$event(2,'position',4000,4000)],JSON_THROW_ON_ERROR);$now+=4000;$count=count($remote->calls);
$progress=$service->save($student,'en-GB',$enrollment,$lesson,$session,$revision,1,10000,$batch);
streamExpect($progress['covered_ms']===4000 && count($remote->calls)===$count,'progress saves without provider HTTP');
$now+=37000;
$remote->status=503;streamDenied(fn()=>$service->renew($student,'en-GB',$enrollment,$lesson,$session,$revision,true),'COM_JLMS_VIDEO_PROVIDER_UNAVAILABLE');$remote->status=200;
streamExpect((int)$db->setQuery('SELECT covered_ms FROM #__jlms_lesson_progress WHERE enrollment_id='.$enrollment.' AND lesson_id='.$lesson)->loadResult()===4000,'provider failure preserves progress');
$renewed=$service->renew($student,'en-GB',$enrollment,$lesson,$session,$revision,true);
streamExpect($renewed['expires_at']>$grant['expires_at'] && $renewed['source_revision']===$revision,'renewal keeps session/source and extends temporary access');
streamDenied(fn()=>$service->renew($student,'en-GB',$enrollment,$lesson,$session,$revision,true),'COM_JLMS_VIDEO_CONFLICT');
$now+=41000;
$remote->callback=static function(string $method)use($student,$group):void{
 if($method==='POST')\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$student->id,$group);
};
streamDenied(fn()=>$service->renew($student,'en-GB',$enrollment,$lesson,$session,$revision,true),'COM_JLMS_LEARNING_DENIED');
\Joomla\CMS\User\UserHelper::addUserToGroup((int)$student->id,$group);$remote->callback=null;
// Access revoked while the remote API was running must not deliver an initial token either.
$remote->callback=static function(string $method)use($student,$group):void{
 if($method==='POST')\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$student->id,$group);
};
streamDenied(fn()=>$service->playback($student,'en-GB',$enrollment,$lesson,true),'COM_JLMS_LEARNING_DENIED');
\Joomla\CMS\User\UserHelper::addUserToGroup((int)$student->id,$group);$remote->callback=null;
$now+=61000;streamDenied(fn()=>$service->save($student,'en-GB',$enrollment,$lesson,$session,$revision,2,10000,json_encode([$event(3,'play',4000,82000),$event(4,'position',9000,87000)],JSON_THROW_ON_ERROR)),'COM_JLMS_VIDEO_CONFLICT');
streamExpect((int)$db->setQuery('SELECT covered_ms FROM #__jlms_lesson_progress WHERE enrollment_id='.$enrollment.' AND lesson_id='.$lesson)->loadResult()===4000,'expired media cannot award new coverage');
$enrollmentRow=(object)['id'=>$enrollment,'ends_at'=>gmdate('Y-m-d H:i:s',intdiv($now,1000)+25)];$db->updateObject('#__jlms_enrollments',$enrollmentRow,'id');
$grant=$service->playback($student,'en-GB',$enrollment,$lesson,true);
streamExpect($grant['expires_at']===intdiv($now,1000)+25,'enrollment end bounds issued token');
$enrollmentRow->ends_at=null;$db->updateObject('#__jlms_enrollments',$enrollmentRow,'id',true);
echo json_encode(['stream_integration_checks'=>$checks,'status'=>'passed','remote_transport'=>'fixture; no live Cloudflare call'],JSON_THROW_ON_ERROR).PHP_EOL;
