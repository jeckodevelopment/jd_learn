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
function videoExpect(bool $value,string $message): void { global $checks; if (!$value) throw new \RuntimeException($message); $checks++; }
function videoDenied(callable $work,int $code,string $message): void {
    try { $work(); } catch (\RuntimeException $error) { videoExpect($error->getCode()===$code,$message.': '.$error->getMessage().' / '.$error->getCode()); return; }
    throw new \RuntimeException($message);
}
$learning=$component->getLearningService(); $authoring=$component->getCurriculumService(); $publication=$component->getPublicationService();
$actor=$app->getIdentity();
$student=new \Joomla\CMS\User\User((int)$db->setQuery("SELECT id FROM #__users WHERE username='cilearner'")->loadResult());
$other=new \Joomla\CMS\User\User((int)$db->setQuery("SELECT id FROM #__users WHERE username='cilearnerother'")->loadResult());
if (($argv[2] ?? '')==='retained') {
    $fixture=json_decode(file_get_contents(dirname($root).'/video-fixture.json'),true,16,JSON_THROW_ON_ERROR);
    $view=$learning->classroom($student,'en-GB',$fixture['enrollment_id'],$fixture['lesson_id']);
    videoExpect($view['percent']===100 && $view['completed_at']!==null,'video completion survives upgrade');
    echo json_encode(['video_upgrade'=>'passed']).PHP_EOL;exit(0);
}
$level=(int)$db->setQuery("SELECT id FROM #__viewlevels WHERE title='CI learning access'")->loadResult();
$group=(int)$db->setQuery("SELECT id FROM #__usergroups WHERE title='CI learning entitlement'")->loadResult();
$catid=(int)$db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
if (!$model->save(['id'=>0,'revision'=>1,'title'=>'Video fixture','alias'=>'video-fixture','catid'=>$catid,
 'language'=>'*','catalog_access'=>1,'content_access'=>$level,'enrollment_mode'=>'auto','summary'=>'Video fixture'])) throw new \RuntimeException($model->getError());
$course=(int)$model->getState('course.id');$version=$authoring->createDraft($actor,$course,1);
$scope=static function()use($db,$course,$version):array{return ['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$version)->loadResult()];};
$module=$authoring->saveModule($actor,$scope()+['id'=>0,'title'=>'Video chapter']);
$lesson=$authoring->saveLesson($actor,$scope()+['id'=>0,'module_id'=>$module,'title'=>'Video lesson','kind'=>'video',
 'required'=>'1','provider_key'=>'youtube','source_reference'=>'dQw4w9WgXcQ','duration_seconds'=>'10','duration_confirmed'=>'1','body'=>'<p>Video description</p>']);
$cr=(int)$db->setQuery('SELECT revision FROM #__jlms_courses WHERE id='.$course)->loadResult();
$publication->transition($actor,$course,$version,$cr,$scope()['expected_version_revision'],(int)$publication->transitions($actor,$course)[0]->id);
$enrollment=$learning->start($student,'en-GB',$course,bin2hex(random_bytes(16)));
$now=(int)floor(microtime(true)*1000);
$service=new \Jlms\Component\Jlms\Administrator\Application\VideoProgressService($db,$learning,$authoring,static function()use(&$now):int{return $now;});
videoDenied(fn()=>$service->playback($student,'en-GB',$enrollment,$lesson,false),403,'no consent');
videoDenied(fn()=>$service->playback($other,'en-GB',$enrollment,$lesson,true),404,'foreign enrollment');
videoDenied(fn()=>$service->playback(new \Joomla\CMS\User\User(),'en-GB',$enrollment,$lesson,true),401,'guest');
videoDenied(fn()=>$learning->complete($student,'en-GB',$enrollment,$lesson,bin2hex(random_bytes(16))),403,'manual completion cannot bypass video');
$view=$learning->classroom($student,'en-GB',$enrollment,$lesson);
videoExpect($view['lesson']->kind==='video' && !isset($view['lesson']->source_json),'classroom never exposes reference');
$a=$service->playback($student,'en-GB',$enrollment,$lesson,true);$b=$service->playback($student,'en-GB',$enrollment,$lesson,true);
videoExpect($a['options']['reference']==='dQw4w9WgXcQ' && !isset($a['playback_token']),'safe descriptor');
$event=static fn(int $id,string $type,int $p,int $t,bool $playing=true):array=>['id'=>$id,'type'=>$type,'position_ms'=>$p,'client_ms'=>$t,'rate_milli'=>1000,'playing'=>$playing];
$batch=json_encode([$event(1,'play',0,0),$event(2,'position',5000,5000)],JSON_THROW_ON_ERROR);$now+=5000;
$save=static fn(array $grant,int $seq,string $events,int $duration=10000)=>$service->save($student,'en-GB',$enrollment,$lesson,$grant['session_id'],$grant['source_revision'],$seq,$duration,$events);
$r=$save($a,1,$batch);videoExpect($r['covered_ms']===5000 && !$r['lesson_completed'],'first valid interval');
videoExpect($save($a,1,$batch)['replay'],'identical batch replay');
videoDenied(fn()=>$save($a,1,'[]'),409,'different replay');
videoDenied(fn()=>$save($a,3,$batch),409,'sequence gap');
videoDenied(fn()=>$save($a,2,$batch,20000),422,'duration mismatch');
$student->getAuthorisedViewLevels();\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$student->id,$group);
videoDenied(fn()=>$save($a,1,$batch),403,'revoked access even on replay');
\Joomla\CMS\User\UserHelper::addUserToGroup((int)$student->id,$group);
$now+=4000;$otherBatch=json_encode([$event(1,'play',5000,0),$event(2,'position',9000,4000)],JSON_THROW_ON_ERROR);
$r=$save($b,1,$otherBatch);videoExpect($r['covered_ms']===9000 && $r['lesson_completed'] && $r['course_completed'],'cross-tab interval union completes exactly');
videoExpect($r['resume_conflict'] && $r['resume_ms']===5000,'stale tab cannot overwrite resume');
videoExpect((int)$db->setQuery('SELECT completed_total FROM #__jlms_enrollments WHERE id='.$enrollment)->loadResult()===1,'completion counted once');
videoExpect($save($b,1,$otherBatch)['replay'],'completion replay');
$service->close($student,'en-GB',$enrollment,$lesson,$a['session_id']);
videoDenied(fn()=>$save($a,2,json_encode([$event(3,'position',6000,6000)],JSON_THROW_ON_ERROR)),409,'closed session cannot write');
file_put_contents(dirname($root).'/video-fixture.json',json_encode(['course_id'=>$course,'enrollment_id'=>$enrollment,'lesson_id'=>$lesson,'user_id'=>(int)$student->id],JSON_THROW_ON_ERROR));
echo json_encode(['video_checks'=>$checks,'status'=>'passed']).PHP_EOL;
