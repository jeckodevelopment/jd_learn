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
    $videoRoot=dirname($root).'/private-videos'; if(!is_dir($videoRoot))mkdir($videoRoot,0750,true);
    $params=$fixtureDb->query("SELECT params FROM joomla_extensions WHERE type='component' AND element='com_jlms'")->fetchColumn();
    $params=json_decode($params?:'{}',true,32,JSON_THROW_ON_ERROR);
    $params+=['video_storage_root'=>$videoRoot,'video_ffprobe_path'=>'/usr/bin/ffprobe','video_delivery_mode'=>'php_bounded','video_max_mib'=>250];
    if (($argv[2]??'')==='') { $params['material_scan_required']=0; } $params['video_external_policy']='blocked';
    $statement=$fixtureDb->prepare("UPDATE joomla_extensions SET params=? WHERE type='component' AND element='com_jlms'");$statement->execute([json_encode($params,JSON_THROW_ON_ERROR)]);
    $fixtureDb->exec("UPDATE joomla_extensions SET params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.playback_enabled',1) WHERE type='plugin' AND folder='jlmsvideo' AND element='html5'");
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





$mode=$argv[2]??'';$fixturePath=dirname($root).'/html5-fixture.json';
$learningFixture=json_decode(file_get_contents(dirname($root).'/learning-fixture.json'),true,16,JSON_THROW_ON_ERROR);
if($mode!==''){
 $fixture=json_decode(file_get_contents($fixturePath),true,16,JSON_THROW_ON_ERROR);
 $course=(int)$fixture['course_id'];$version=(int)$fixture['version_id'];$lesson=(int)$fixture['lesson_id'];
 $row=$db->setQuery('SELECT * FROM #__jlms_lessons WHERE id='.$lesson)->loadObject();
 if(!$row || $row->provider_key!=='html5')throw new \RuntimeException('Fixture lesson missing');
 if($mode==='publish'){
  $publication=$component->getPublicationService();
  $publication->transition($app->getIdentity(),$course,$version,(int)$db->setQuery('SELECT revision FROM #__jlms_courses WHERE id='.$course)->loadResult(),
   (int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$version)->loadResult(),(int)$publication->transitions($app->getIdentity(),$course)[0]->id);
 }elseif($mode==='edit'){
  $component->getCurriculumService()->saveLesson($app->getIdentity(),['id'=>$lesson,'course_id'=>$course,'version_id'=>$version,
   'expected_version_revision'=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$version)->loadResult(),
   'revision'=>(int)$row->revision,'module_id'=>(int)$row->module_id,'title'=>'Local lesson edited','kind'=>'video','required'=>'1','body'=>'',
   'provider_key'=>'html5','source_reference'=>'untrusted-other-asset-id','duration_seconds'=>'1','duration_confirmed'=>'0']);
 }elseif($mode==='configure'){
  $changes=json_decode($argv[3]??'{}',true,16,JSON_THROW_ON_ERROR);
  if(array_diff(array_keys($changes),['material_scan_required','material_clamd_socket']))throw new \RuntimeException('Unapproved fixture option');
  $params=json_decode($db->setQuery("SELECT params FROM #__extensions WHERE type='component' AND element='com_jlms'")->loadResult(),true,32,JSON_THROW_ON_ERROR);
  $params=array_replace($params,$changes);
  $ext=(object)['extension_id'=>(int)$db->setQuery("SELECT extension_id FROM #__extensions WHERE type='component' AND element='com_jlms'")->loadResult(),'params'=>json_encode($params,JSON_THROW_ON_ERROR)];$db->updateObject('#__extensions',$ext,'extension_id');
 }elseif($mode==='delivery'){
  if(!in_array($argv[3]??'', ['disabled','php_bounded','x_accel'],true))throw new \RuntimeException('Bad fixture mode');
  $params=json_decode($db->setQuery("SELECT params FROM #__extensions WHERE type='component' AND element='com_jlms'")->loadResult(),true,32,JSON_THROW_ON_ERROR);
  $params['video_delivery_mode']=$argv[3];
  $ext=(object)['extension_id'=>(int)$db->setQuery("SELECT extension_id FROM #__extensions WHERE type='component' AND element='com_jlms'")->loadResult(),'params'=>json_encode($params,JSON_THROW_ON_ERROR)];
  $db->updateObject('#__extensions',$ext,'extension_id');
 }elseif($mode==='reject' || $mode==='restore'){
  $record=(object)['id'=>(int)$row->media_asset_id,'status'=>$mode==='reject'?'rejected':'ready'];$db->updateObject('#__jlms_assets',$record,'id');
 }elseif($mode==='deny-student-upload'){
  try{$component->getLocalVideoService()->upload(new \Joomla\CMS\User\User($learningFixture['http_user_id']),$course,$version,$lesson,1,[]);}
  catch(\RuntimeException $error){if($error->getCode()===403){echo '{"student_upload":"denied"}';exit(0);}throw $error;}
  throw new \RuntimeException('Student upload unexpectedly allowed');
 }elseif($mode==='retained'){
  if(!$row->media_asset_id || (int)$row->duration_ms!==10000)throw new \RuntimeException('Video metadata lost');
  $enrollment=(int)$db->setQuery('SELECT id FROM #__jlms_enrollments WHERE course_id='.$course.' AND user_id='.(int)$learningFixture['http_user_id'])->loadResult();
  if($component->getLearningService()->classroom(new \Joomla\CMS\User\User($learningFixture['http_user_id']),'en-GB',$enrollment,$lesson)['percent']!==100)throw new \RuntimeException('Video completion lost');
  echo '{"html5_upgrade":"passed"}';exit(0);
 }elseif($mode!=='state')throw new \RuntimeException('Unknown fixture action');
 $row=$db->setQuery('SELECT * FROM #__jlms_lessons WHERE id='.$lesson)->loadObject();
 $asset=$row->media_asset_id?$db->setQuery('SELECT * FROM #__jlms_assets WHERE id='.(int)$row->media_asset_id)->loadObject():null;
 echo json_encode(['lesson'=>$row,'asset'=>$asset,'version'=>$db->setQuery('SELECT * FROM #__jlms_course_versions WHERE id='.$version)->loadObject()],JSON_THROW_ON_ERROR);exit(0);
}
$catid=(int)$db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
$level=(int)$db->setQuery("SELECT id FROM #__viewlevels WHERE title='CI learning access'")->loadResult();
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
if(!$model->save(['id'=>0,'revision'=>1,'title'=>'HTML5 fixture','alias'=>'html5-fixture','catid'=>$catid,'language'=>'*','catalog_access'=>1,'content_access'=>$level,'enrollment_mode'=>'auto','presentation_description'=>'<p>Course presentation for the publication fixture.</p>', 'summary'=>'HTML5 fixture']))throw new \RuntimeException($model->getError());
$course=(int)$model->getState('course.id');$authoring=$component->getCurriculumService();$actor=$app->getIdentity();$version=$authoring->createDraft($actor,$course,1);
$scope=static fn():array=>['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$version)->loadResult()];
$module=$authoring->saveModule($actor,$scope()+['id'=>0,'title'=>'Local chapter']);
$lesson=$authoring->saveLesson($actor,$scope()+['id'=>0,'module_id'=>$module,'title'=>'Local lesson','kind'=>'video','required'=>'1','body'=>'','provider_key'=>'html5','source_reference'=>'']);
file_put_contents($fixturePath,json_encode(['course_id'=>$course,'version_id'=>$version,'lesson_id'=>$lesson,'http_user_id'=>$learningFixture['http_user_id'],'group_id'=>$learningFixture['group_id'],'video_root'=>dirname($root).'/private-videos'],JSON_THROW_ON_ERROR));
echo '{"html5_fixture":"created"}';
