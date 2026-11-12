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



$actor=$app->getIdentity();$authoring=$component->getCurriculumService();$publication=$component->getPublicationService();$learning=$component->getLearningService();$checks=0;
function ruleExpect(bool $ok,string $message):void{global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
function ruleDenied(callable $work,string $key):void{try{$work();}catch(DomainException $error){ruleExpect($error->getMessage()===$key,'Expected '.$key.', got '.$error->getMessage());return;}throw new RuntimeException('Expected '.$key);}
function ruleRow(string $table,int $id):object{global $db;return $db->setQuery('SELECT * FROM #__jlms_'.$table.' WHERE id='.$id)->loadObject();}
function ruleLearning(?int $course=null):array{global $db;if($course!==null){return [$db->setQuery('SELECT * FROM #__jlms_enrollments WHERE course_id='.$course.' ORDER BY id')->loadAssocList(),$db->setQuery('SELECT a.* FROM #__jlms_attempts a JOIN #__jlms_enrollments e ON e.id=a.enrollment_id WHERE e.course_id='.$course.' ORDER BY a.id')->loadAssocList(),$db->setQuery('SELECT p.* FROM #__jlms_lesson_progress p JOIN #__jlms_enrollments e ON e.id=p.enrollment_id WHERE e.course_id='.$course.' ORDER BY p.enrollment_id,p.lesson_id')->loadAssocList()];}return [$db->setQuery('SELECT * FROM #__jlms_enrollments ORDER BY id')->loadAssocList(),$db->setQuery('SELECT * FROM #__jlms_attempts ORDER BY id')->loadAssocList(),$db->setQuery('SELECT * FROM #__jlms_lesson_progress ORDER BY enrollment_id,lesson_id')->loadAssocList()];}
$fixture=dirname($root).'/teaching-rules-fixture.json';$mode=$argv[2]??'';
if(in_array($mode,['state','checkpoint','retained'],true)){
 $f=json_decode(file_get_contents($fixture),true,32,JSON_THROW_ON_ERROR);
 $state=['course'=>(array)ruleRow('courses',$f['course']),'version'=>(array)ruleRow('course_versions',$f['version']),'original'=>(array)ruleRow('course_versions',$f['original']),'learning'=>ruleLearning($f['course'])];
 if($mode==='checkpoint'){file_put_contents($fixture.'.snapshot',json_encode($state,JSON_THROW_ON_ERROR));return;}
 if($mode==='retained'){ruleExpect(json_decode(file_get_contents($fixture.'.snapshot'),true,64,JSON_THROW_ON_ERROR)===$state,'Teaching rules and assigned history survive upgrade');echo json_encode(['teaching_rules_retained_checks'=>$checks,'status'=>'passed']).PHP_EOL;return;}
 echo json_encode($state,JSON_THROW_ON_ERROR);return;
}
\Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->set('video_external_policy','embed');
$source=(int)$db->setQuery("SELECT id FROM #__jlms_courses WHERE alias='video-fixture'")->loadResult();$sourceCourse=ruleRow('courses',$source);$sourceVersion=ruleRow('course_versions',(int)$sourceCourse->current_version_id);
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);$course=$model->duplicate(['course_id'=>$source,'version_id'=>$sourceVersion->id,'revision'=>$sourceCourse->revision,'expected_version_revision'=>$sourceVersion->revision,'title'=>'Teaching rules fixture']);
$item=$model->getItem($course);ruleExpect($model->save(['id'=>$course,'revision'=>$item->revision,'catalog_access'=>1,'content_access'=>1,'enrollment_mode'=>'auto']),'Independent rules fixture has explicit learner access');
$version=(int)$authoring->outline($actor,$course)['version']->id;
$publish=static function(int $id)use($publication,$actor,$course):void{$transition=(int)$publication->transitions($actor,$course,$id)[0]->id;$impact=$publication->updateImpact($actor,$course,$id,$transition);$publication->transition($actor,$course,$id,(int)ruleRow('courses',$course)->revision,(int)ruleRow('course_versions',$id)->revision,$transition,$impact['fingerprint']??null);};
ruleExpect($authoring->teachingRules($actor,$course,$version)['video_threshold_basis_points']===9000,'Default is90 percent');$publish($version);$original=$version;
$oldLesson=(int)$db->setQuery('SELECT id FROM #__jlms_lessons WHERE course_version_id='.$original." AND kind='video' ORDER BY id")->loadResult();
$oldEnrollment=$learning->start($actor,'en-GB',$course,bin2hex(random_bytes(20)));$old=ruleRow('course_versions',$original);
// Explicit consultation mode permits an optional-only programme without inventing completion credit.
$consultationCourse=$model->duplicate(['course_id'=>$source,'version_id'=>$sourceVersion->id,'revision'=>$sourceCourse->revision,'expected_version_revision'=>$sourceVersion->revision,'title'=>'Consultation rules fixture']);
$consultationItem=$model->getItem($consultationCourse);
ruleExpect($model->save(['id'=>$consultationCourse,'revision'=>$consultationItem->revision,'catalog_access'=>1,'content_access'=>1,'enrollment_mode'=>'auto']),'Consultation fixture has valid learner access');
$consultationVersion=(int)$authoring->outline($actor,$consultationCourse)['version']->id;
$db->setQuery('UPDATE #__jlms_lessons SET required=0 WHERE course_version_id='.(int)$consultationVersion)->execute();
$authoring->saveTeachingRules($actor,['course_id'=>$consultationCourse,'version_id'=>$consultationVersion,'expected_version_revision'=>ruleRow('course_versions',$consultationVersion)->revision,'mode'=>'consultation','sequence_mode'=>'free','video_threshold'=>'90']);
$consultationTransition=(int)$publication->transitions($actor,$consultationCourse,$consultationVersion)[0]->id;
$publication->transition($actor,$consultationCourse,$consultationVersion,(int)ruleRow('courses',$consultationCourse)->revision,(int)ruleRow('course_versions',$consultationVersion)->revision,$consultationTransition);
$consultationEnrollment=$learning->start($actor,'en-GB',$consultationCourse,bin2hex(random_bytes(20)));
$consultationLesson=(int)$db->setQuery('SELECT id FROM #__jlms_lessons WHERE course_version_id='.(int)$consultationVersion.' ORDER BY ordering,id')->loadResult();
$consultationClassroom=$learning->classroom($actor,'en-GB',$consultationEnrollment,$consultationLesson);
ruleExpect($consultationClassroom['progress_applicable']===false&&$consultationClassroom['percent']===null&&$consultationClassroom['completed_at']===null,'Consultation enrollment exposes no progress or course completion');
try{$learning->complete($actor,'en-GB',$consultationEnrollment,$consultationLesson,bin2hex(random_bytes(20)));throw new RuntimeException('Consultation must not grant lesson completion');}catch(RuntimeException $error){ruleExpect($error->getMessage()==='COM_JLMS_CONSULTATION_COMPLETION_UNAVAILABLE','Consultation lesson completion is explicitly denied');}
ruleExpect((int)ruleRow('enrollments',$consultationEnrollment)->required_total===0&&(int)ruleRow('enrollments',$consultationEnrollment)->completed_total===0,'Consultation enrollment stores zero completion requirements and credit');
$version=$authoring->createDraft($actor,$course,(int)ruleRow('courses',$course)->revision);$courseBefore=ruleRow('courses',$course);$before=ruleLearning();
$input=['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>ruleRow('course_versions',$version)->revision,'video_threshold'=>'50.25'];
ruleDenied(fn()=>$authoring->saveTeachingRules(new \Joomla\CMS\User\User(0),$input),'JERROR_ALERTNOAUTHOR');
ruleDenied(fn()=>$authoring->saveTeachingRules($actor,array_replace($input,['version_id'=>$sourceVersion->id,'expected_version_revision'=>$sourceVersion->revision])),'COM_JLMS_RESOURCE_NOT_FOUND');
ruleDenied(fn()=>$authoring->saveTeachingRules($actor,array_replace($input,['version_id'=>$original,'expected_version_revision'=>$old->revision])),'COM_JLMS_VERSION_IMMUTABLE');
ruleDenied(fn()=>$authoring->saveTeachingRules($actor,array_replace($input,['video_threshold'=>'0'])),'COM_JLMS_TEACHING_THRESHOLD_INVALID');
$other=(int)$db->setQuery("SELECT id FROM #__users WHERE username='cilearner'")->loadResult();
foreach([['courses',$course],['course_versions',$version]] as [$table,$id]){$oldCheckout=ruleRow($table,$id)->checked_out;$locked=(object)['id'=>$id,'checked_out'=>$other];$db->updateObject('#__jlms_'.$table,$locked,'id',true);try{ruleDenied(fn()=>$authoring->saveTeachingRules($actor,$input),'JERROR_ALERTNOAUTHOR');}finally{$locked->checked_out=$oldCheckout;$db->updateObject('#__jlms_'.$table,$locked,'id',true);}}
$authoring->saveTeachingRules($actor,$input+['mode'=>'consultation','certificate_enabled'=>true,'rules_json'=>'{}']);
$rules=$authoring->teachingRules($actor,$course,$version);ruleExpect($rules['video_threshold_basis_points']===5025&&$rules['mode']==='learning'&&$rules['certificate_enabled']===false,'Only supported threshold field changes');
ruleExpect((int)ruleRow('course_versions',$version)->revision===(int)$input['expected_version_revision']+1,'Rule save increments version CAS');
ruleExpect(ruleRow('courses',$course)==$courseBefore&&ruleRow('course_versions',$original)==$old&&ruleLearning()===$before,'Draft rule change preserves metadata, frozen source and all learning');
ruleDenied(fn()=>$authoring->saveTeachingRules($actor,$input),'COM_JLMS_REVISION_CONFLICT');
$t=(int)$publication->transitions($actor,$course,$version)[0]->id;$review=$publication->updateImpact($actor,$course,$version,$t);ruleExpect($review['rules_changed'],'Publication review recognises teaching rule changes');
ruleExpect($review['video_threshold_change']===['before'=>9000,'after'=>5025],'Review exposes exact before/after threshold');
$authoring->saveTeachingRules($actor,array_replace($input,['expected_version_revision'=>ruleRow('course_versions',$version)->revision,'video_threshold'=>'50.26']));
ruleDenied(fn()=>$publication->transition($actor,$course,$version,(int)ruleRow('courses',$course)->revision,(int)ruleRow('course_versions',$version)->revision,$t,$review['fingerprint']),'COM_JLMS_PUBLICATION_REVIEW_STALE');
$authoring->saveTeachingRules($actor,array_replace($input,['expected_version_revision'=>ruleRow('course_versions',$version)->revision]));
$valid=ruleRow('course_versions',$version);$invalid=(object)['id'=>$version,'rules_json'=>json_encode(array_replace($rules,['video_threshold_basis_points'=>10001]),JSON_THROW_ON_ERROR)];$db->updateObject('#__jlms_course_versions',$invalid,'id');
ruleDenied(fn()=>$publish($version),'COM_JLMS_PUBLICATION_UNSUPPORTED');ruleExpect((int)ruleRow('courses',$course)->current_version_id===$original&&ruleLearning()===$before,'Invalid frozen threshold cannot be published');
$invalid->rules_json=$valid->rules_json;$db->updateObject('#__jlms_course_versions',$invalid,'id');$publish($version);
$newEnrollment=$learning->start($actor,'en-GB',$course,bin2hex(random_bytes(20)));$newLesson=(int)$db->setQuery('SELECT id FROM #__jlms_lessons WHERE course_version_id='.$version." AND kind='video' ORDER BY id")->loadResult();
ruleExpect((int)ruleRow('enrollments',$oldEnrollment)->course_version_id===$original&&(int)ruleRow('enrollments',$newEnrollment)->course_version_id===$version,'New admissions use updated rules while historical assignments remain');
$now=(int)floor(microtime(true)*1000);$video=new \Jlms\Component\Jlms\Administrator\Application\VideoProgressService($db,$learning,$authoring,static function()use(&$now):int{return $now;});
$event=static fn(int $id,int $p,int $time,string $type='position'):array=>['id'=>$id,'type'=>$type,'position_ms'=>$p,'client_ms'=>$time,'rate_milli'=>1000,'playing'=>true];
$oldGrant=$video->playback($actor,'en-GB',$oldEnrollment,$oldLesson,true);$newGrant=$video->playback($actor,'en-GB',$newEnrollment,$newLesson,true);$now+=5000;
$batch=json_encode([$event(1,0,0,'play'),$event(2,5000,5000)],JSON_THROW_ON_ERROR);
$oldResult=$video->save($actor,'en-GB',$oldEnrollment,$oldLesson,$oldGrant['session_id'],$oldGrant['source_revision'],1,10000,$batch);
$newResult=$video->save($actor,'en-GB',$newEnrollment,$newLesson,$newGrant['session_id'],$newGrant['source_revision'],1,10000,$batch);
ruleExpect(!$oldResult['lesson_completed']&&!$newResult['lesson_completed'],'5000ms cannot round up to50.25 percent');$now+=25;$batch=json_encode([$event(3,5025,5025)],JSON_THROW_ON_ERROR);
$newResult=$video->save($actor,'en-GB',$newEnrollment,$newLesson,$newGrant['session_id'],$newGrant['source_revision'],2,10000,$batch);
$oldResult=$video->save($actor,'en-GB',$oldEnrollment,$oldLesson,$oldGrant['session_id'],$oldGrant['source_revision'],2,10000,$batch);
ruleExpect($newResult['lesson_completed']&&$newResult['course_completed']&&!$oldResult['lesson_completed'],'Exact configured threshold applies only to its assigned version');
$now+=3975;$oldResult=$video->save($actor,'en-GB',$oldEnrollment,$oldLesson,$oldGrant['session_id'],$oldGrant['source_revision'],3,10000,json_encode([$event(4,9000,9000)],JSON_THROW_ON_ERROR));
ruleExpect($oldResult['lesson_completed']&&$oldResult['course_completed'],'Existing student still completes at original90percent');
$consultationVideo=(int)$db->setQuery('SELECT id FROM #__jlms_lessons WHERE course_version_id='.(int)$consultationVersion." AND kind='video' ORDER BY id")->loadResult();
$consultationGrant=$video->playback($actor,'en-GB',$consultationEnrollment,$consultationVideo,true);$now+=10000;
$consultationResult=$video->save($actor,'en-GB',$consultationEnrollment,$consultationVideo,$consultationGrant['session_id'],$consultationGrant['source_revision'],1,10000,json_encode([$event(10,0,0,'play'),$event(11,10000,10000)],JSON_THROW_ON_ERROR));
ruleExpect(!$consultationResult['lesson_completed']&&!$consultationResult['course_completed'],'Consultation video telemetry never grants lesson or course completion');
$version=$authoring->createDraft($actor,$course,(int)ruleRow('courses',$course)->revision);
file_put_contents($fixture,json_encode(['course'=>$course,'version'=>$version,'original'=>$original],JSON_THROW_ON_ERROR));
echo json_encode(['teaching_rules_native_checks'=>$checks,'status'=>'passed']).PHP_EOL;
