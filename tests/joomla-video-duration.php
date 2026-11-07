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
$operator = $container->get(UserFactoryInterface::class)->loadUserById($userId);
$app->loadIdentity($operator);
// ComponentHelper::filterText reads the native session user via Factory::getUser().
// A CLI fixture must establish both parts of a web login, otherwise the HTML
// policy is evaluated for Public while MVC authorisation uses the operator.
$app->getSession()->set('user', $operator);
if ((int) Factory::getUser()->id !== (int) $app->getIdentity()->id) {
    throw new \RuntimeException('Fixture session and application identities must match.');
}
$component = $app->bootComponent('com_jlms');
$factory = $component->getMVCFactory();



require __DIR__.'/cloudflare-fixture.php';
[$config,$secrets,$transport,$remote]=cloudflareFixture();
$registry=new VideoProviderRegistry();foreach([new \Jlms\Plugin\Jlmsvideo\Youtube\Provider\Provider(true),new \Jlms\Plugin\Jlmsvideo\Vimeo\Provider\Provider(true),new \Jlms\Plugin\Jlmsvideo\Cloudflarestream\Provider\Provider($config,$secrets,$transport)] as $provider){$registry->register($provider->getDescriptor(),static fn()=>$provider);}
$authoring=new \Jlms\Component\Jlms\Administrator\Application\CurriculumService($db,static fn(string $body):string=>\Joomla\CMS\Component\ComponentHelper::filterText($body),static fn()=>$registry);$component->setCurriculumService($authoring);
$actor=$app->getIdentity();$publication=$component->getPublicationService();$learning=$component->getLearningService();$checks=0;
function durationExpect(bool $ok,string $message):void{global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
function durationDenied(callable $work,string $key):void{try{$work();}catch(\Throwable $e){durationExpect($e->getMessage()===$key,'Expected '.$key.', got '.$e->getMessage());return;}throw new RuntimeException('Expected '.$key);}
function durationRow(string $table,int $id):object{global $db;return $db->setQuery('SELECT * FROM #__jlms_'.$table.' WHERE id='.$id)->loadObject();}
function durationState(int $course):array{global $db;$versions=$db->setQuery('SELECT * FROM #__jlms_course_versions WHERE course_id='.$course.' ORDER BY id')->loadAssocList();$ids=implode(',',array_column($versions,'id'));return ['course'=>(array)durationRow('courses',$course),'versions'=>$versions,'lessons'=>$db->setQuery('SELECT * FROM #__jlms_lessons WHERE course_version_id IN ('.$ids.') ORDER BY id')->loadAssocList(),'enrollments'=>$db->setQuery('SELECT * FROM #__jlms_enrollments WHERE course_id='.$course.' ORDER BY id')->loadAssocList(),'progress'=>$db->setQuery('SELECT p.* FROM #__jlms_lesson_progress p JOIN #__jlms_enrollments e ON e.id=p.enrollment_id WHERE e.course_id='.$course.' ORDER BY p.enrollment_id,p.lesson_id')->loadAssocList()];}
$fixture=dirname($root).'/video-duration-fixture.json';$mode=$argv[2]??'';
if(in_array($mode,['state','checkpoint','retained'],true)){$f=json_decode(file_get_contents($fixture),true,32,JSON_THROW_ON_ERROR);$state=durationState($f['course']);if($mode==='checkpoint'){file_put_contents($fixture.'.snapshot',json_encode($state,JSON_THROW_ON_ERROR));return;}if($mode==='retained'){durationExpect(json_decode(file_get_contents($fixture.'.snapshot'),true,64,JSON_THROW_ON_ERROR)===$state,'Measured provenance, frozen lessons and observed-duration progress survive upgrade');echo json_encode(['video_duration_retained_checks'=>$checks,'status'=>'passed']).PHP_EOL;return;}echo json_encode($state,JSON_THROW_ON_ERROR);return;}
$settings=\Joomla\CMS\Component\ComponentHelper::getParams('com_jlms');$settings->set('video_external_policy','embed');
$source=(int)$db->setQuery("SELECT id FROM #__jlms_courses WHERE alias='video-fixture'")->loadResult();$sourceCourse=durationRow('courses',$source);$sourceVersion=durationRow('course_versions',(int)$sourceCourse->current_version_id);
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);$course=$model->duplicate(['course_id'=>$source,'version_id'=>$sourceVersion->id,'revision'=>$sourceCourse->revision,'expected_version_revision'=>$sourceVersion->revision,'title'=>'Automatic duration fixture']);
$item=$model->getItem($course);durationExpect($model->save(['id'=>$course,'revision'=>$item->revision,'catalog_access'=>1,'content_access'=>1,'enrollment_mode'=>'auto']),'Independent duration fixture has learner access');
$version=(int)$authoring->outline($actor,$course)['version']->id;$module=(int)$authoring->outline($actor,$course,$version)['modules'][0]->id;$lesson=(int)$authoring->outline($actor,$course,$version)['lessons'][0]->id;
$scope=static fn():array=>['course_id'=>$course,'version_id'=>$version,'module_id'=>$module,'expected_version_revision'=>durationRow('course_versions',$version)->revision];
$input=static fn():array=>$scope()+['id'=>$lesson,'revision'=>durationRow('lessons',$lesson)->revision,'title'=>'Automatically measured lesson','kind'=>'video','required'=>'1','body'=>'<p>Metadata authoring fixture</p>','provider_key'=>'youtube','source_reference'=>'dQw4w9WgXcQ','privacy_hash'=>'','duration_mode'=>'auto'];
$before=durationState($course);$data=$authoring->authoringMetadata($actor,$input());durationExpect($data['duration_ms']===null&&$data['origin']==='author_sdk'&&$data['options']['reference']==='dQw4w9WgXcQ','YouTube inspection returns normalized SDK options without fake enrollment');
durationExpect(durationState($course)===$before,'Metadata acquisition performs no programme or learning write');
durationDenied(fn()=>$authoring->authoringMetadata(new \Joomla\CMS\User\User(0),$input()),'JERROR_ALERTNOAUTHOR');
durationDenied(fn()=>$authoring->authoringMetadata($actor,array_replace($input(),['version_id'=>$sourceVersion->id])),'COM_JLMS_RESOURCE_NOT_FOUND');
durationDenied(fn()=>$authoring->authoringMetadata($actor,array_replace($input(),['expected_version_revision'=>999999])),'COM_JLMS_REVISION_CONFLICT');
$settings->set('video_external_policy','blocked');durationDenied(fn()=>$authoring->authoringMetadata($actor,$input()),'COM_JLMS_VIDEO_EDITOR_POLICY_BLOCKED');$settings->set('video_external_policy','embed');
$vimeo=$authoring->authoringMetadata($actor,array_replace($input(),['provider_key'=>'vimeo','source_reference'=>'https://vimeo.com/123456789/abcdef1234']));durationExpect($vimeo['duration_ms']===null&&$vimeo['options']['privacy_hash']==='abcdef1234','Vimeo normalization retains unlisted privacy hash for SDK preview');
$proof=['fingerprint'=>$data['fingerprint'],'duration_ms'=>10000,'origin'=>'author_sdk'];
durationDenied(fn()=>$authoring->saveLesson($actor,array_replace($input(),['source_reference'=>'aaaaaaaaaaa']),$proof),'COM_JLMS_VIDEO_METADATA_STALE');
$authoring->saveLesson($actor,$input(),$proof);$measured=durationRow('lessons',$lesson);
durationExpect((int)$measured->duration_ms===10000&&json_decode($measured->params_json,true)['video_duration_origin']==='author_sdk','SDK duration is stored without author typing or confirmation');
$authoring->saveLesson($actor,$input());durationExpect(durationRow('lessons',$lesson)->duration_ms===$measured->duration_ms&&durationRow('lessons',$lesson)->source_revision===$measured->source_revision,'Temporary absence of new metadata preserves measured duration on unchanged source');
$stream=$scope()+['id'=>0,'revision'=>1,'title'=>'API measured Stream','kind'=>'video','required'=>'0','body'=>'','provider_key'=>'cloudflarestream','source_reference'=>str_repeat('b',32),'duration_mode'=>'auto'];
$calls=count($remote->calls);durationDenied(fn()=>$authoring->authoringMetadata(new \Joomla\CMS\User\User(0),$stream),'JERROR_ALERTNOAUTHOR');durationExpect(count($remote->calls)===$calls,'Remote API is not contacted before author authorization');
$previousRevision=(int)durationRow('course_versions',$version)->revision;
$remote->callback=static function()use($db,$version,$previousRevision,$remote):void{$remote->callback=null;$changed=(object)['id'=>$version,'revision'=>$previousRevision+1];$db->updateObject('#__jlms_course_versions',$changed,'id');};
durationDenied(fn()=>$authoring->authoringMetadata($actor,$stream),'COM_JLMS_REVISION_CONFLICT');
$reset=(object)['id'=>$version,'revision'=>$previousRevision];$db->updateObject('#__jlms_course_versions',$reset,'id');
$api=$authoring->authoringMetadata($actor,$stream);durationExpect($api['duration_ms']===10000&&$api['origin']==='provider_api','Stream duration comes from controlled official API metadata');
$streamId=$authoring->saveLesson($actor,$stream,['fingerprint'=>$api['fingerprint'],'duration_ms'=>$api['duration_ms'],'origin'=>$api['origin']]);durationExpect(json_decode(durationRow('lessons',$streamId)->params_json,true)['video_duration_origin']==='provider_api','Server API provenance retained');
$publish=static function(int $id)use($publication,$actor,$course):void{$t=(int)$publication->transitions($actor,$course,$id)[0]->id;$impact=$publication->updateImpact($actor,$course,$id,$t);$publication->transition($actor,$course,$id,(int)durationRow('courses',$course)->revision,(int)durationRow('course_versions',$id)->revision,$t,$impact['fingerprint']??null);};
$publish($version);$frozen=durationRow('lessons',$lesson);durationDenied(fn()=>$authoring->authoringMetadata($actor,$input()),'COM_JLMS_VERSION_IMMUTABLE');
$enrollment=$learning->start($actor,'en-GB',$course,bin2hex(random_bytes(20)));$now=(int)floor(microtime(true)*1000);$video=new \Jlms\Component\Jlms\Administrator\Application\VideoProgressService($db,$learning,$authoring,static function()use(&$now):int{return $now;});$grant=$video->playback($actor,'en-GB',$enrollment,$lesson,true);
$event=static fn(int $id,string $type,int $p,int $time,bool $playing=true):array=>['id'=>$id,'type'=>$type,'position_ms'=>$p,'client_ms'=>$time,'rate_milli'=>1000,'playing'=>$playing];
$now+=1000;$save=static fn(int $seq,array $events,int $duration)=>$video->save($actor,'en-GB',$enrollment,$lesson,$grant['session_id'],$grant['source_revision'],$seq,$duration,json_encode($events,JSON_THROW_ON_ERROR));
$r=$save(1,[$event(1,'play',0,0),$event(2,'seeking',8000,1,false),$event(3,'ended',8000,2,false)],8000);durationExpect(!$r['lesson_completed']&&$r['covered_ms']===0,'Minor duration difference and ended event never count skipped content');
$now+=7200;$r=$save(2,[$event(4,'seeked',0,3,false),$event(5,'play',0,4),$event(6,'position',7200,7204)],8000);durationExpect($r['lesson_completed']&&$r['course_completed']&&$r['covered_ms']===7200,'Shorter observed duration completes exactly at assigned90percent without rewriting frozen lesson');
durationExpect(durationRow('lessons',$lesson)==$frozen,'Tolerated metadata never rewrites historical source');
durationDenied(fn()=>$save(3,[$event(7,'position',7500,7504)],20000),'COM_JLMS_VIDEO_DURATION_MISMATCH');
durationExpect($save(2,[$event(4,'seeked',0,3,false),$event(5,'play',0,4),$event(6,'position',7200,7204)],8000)['replay'],'Duration-tolerant completion replay is idempotent');
$draft=$authoring->createDraft($actor,$course,(int)durationRow('courses',$course)->revision);$outline=$authoring->outline($actor,$course,$draft);$newLesson=(int)array_values(array_filter($outline['lessons'],static fn($l)=>$l->provider_key==='youtube'))[0]->id;
file_put_contents($fixture,json_encode(['course'=>$course,'version'=>$draft,'lesson'=>$newLesson,'original'=>$version,'foreign_version'=>(int)$sourceVersion->id],JSON_THROW_ON_ERROR));
echo json_encode(['video_duration_native_checks'=>$checks,'status'=>'passed']).PHP_EOL;
