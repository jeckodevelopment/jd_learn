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


$authoring = $component->getCurriculumService();
$publication = $component->getPublicationService();
$catalog = $component->getCatalogService();
$actor = $app->getIdentity();
$catid = (int) $db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
use Jlms\Component\Jlms\Administrator\Application\CoursePresentation;


use Jlms\Component\Jlms\Administrator\Application\CourseSchedule as Schedule;
$checks=0;
function scheduleExpect(bool $ok,string $message):void { global $checks;if(!$ok)throw new RuntimeException($message);$checks++; }
function scheduleDenied(callable $call,int $code,string $message):void { try {$call();}catch(RuntimeException $e){scheduleExpect($e->getCode()===$code,$message);return;}throw new RuntimeException($message); }
$fixture=dirname($root).'/calendar-fixture.json';
if (($argv[2]??'')==='retained') {
 $f=json_decode(file_get_contents($fixture),true,32,JSON_THROW_ON_ERROR);
 scheduleExpect($db->setQuery('SELECT presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id='.(int)$f['version'])->loadAssoc()===$f['snapshot'],'Frozen calendar survives reinstall');
 scheduleExpect($db->setQuery('SELECT starts_at,ends_at FROM #__jlms_editions WHERE id='.(int)$f['edition'])->loadAssoc()===$f['window'],'Edition window survives reinstall');
 scheduleExpect($catalog->outline(new \Joomla\CMS\User\User(),'en-GB',(int)$f['course'])['calendar']['timezone']==='Europe/Rome','Calendar zone survives reinstall');
 echo json_encode(['calendar_retention_checks'=>$checks,'status'=>'passed']).PHP_EOL;exit;
}
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
$siteConfig = $app->getConfig();
$previousOffset = $siteConfig->get('offset', 'UTC');
$siteConfig->set('offset', 'Europe/Rome');
$newCourse = $model->getItem(0);
$newCourseForm = $model->getForm([], true);
scheduleExpect($newCourse->calendar_timezone === '', 'New course editor selects the Joomla global timezone option');
scheduleExpect($newCourseForm->getField('calendar_timezone')->value === '', 'New course form keeps the global timezone option selected');
scheduleExpect(in_array('', array_map(static fn($option) => (string) $option->value, $newCourseForm->getField('calendar_timezone')->getOptions()), true), 'Global timezone option is rendered by the native Joomla field');
$siteConfig->set('offset', $previousOffset);
$base=['id'=>0,'revision'=>1,'title'=>'Calendar course fixture','alias'=>'calendar-course-fixture','catid'=>$catid,'language'=>'*','catalog_access'=>1,'content_access'=>2,'enrollment_mode'=>'auto','presentation_description'=>'<p>Course presentation for the publication fixture.</p>', 'summary'=>'Calendar fixture'];
$values=['calendar_timezone'=>'','calendar_publish_up'=>'2000-01-01 01:00:00','calendar_publish_down'=>'2099-01-01 01:00:00','calendar_starts_at'=>'2000-01-01 01:00:00','calendar_ends_at'=>'2099-01-01 01:00:00'];
scheduleExpect($model->save($base+$values+['publish_up'=>'2200-01-01 00:00:00','params_json'=>'{"injected":true}']),'Typed calendar saved with mass assignment ignored');
$course=(int)$model->getState('course.id');$item=$model->getItem($course);
scheduleExpect(str_starts_with($item->publish_up,'2000-01-01 00:00:00')&&$item->calendar_publish_up===$values['calendar_publish_up']&&$item->calendar_timezone==='Europe/Rome','UTC persisted and editor round-trips local values');
scheduleExpect(!str_contains($item->params_json,'injected'),'Raw calendar JSON not accepted');
$edit=array_replace($base,['id'=>$course]);
scheduleExpect($model->save($edit),'Legacy client can save without calendar fields');
$before=$model->getItem($course);scheduleExpect($before->calendar_starts_at===$values['calendar_starts_at'],'Old client retains calendar');
foreach ([['calendar_timezone'=>['UTC']],['calendar_timezone'=>'bad/zone'],['calendar_starts_at'=>'2027-03-28 02:30:00'],['calendar_starts_at'=>'2027-10-31 02:30:00'],['calendar_ends_at'=>'1999-01-01 00:00:00'],['calendar_publish_down'=>'1999-01-01 00:00:00'],['calendar_starts_at'=>'2001-02-30 12:00:00'],['calendar_ends_at'=>['bad']]] as $invalid) {
 scheduleExpect(!$model->save(array_replace($edit,$values,['revision'=>2],$invalid)),'Invalid calendar rejected');
 $after=$model->getItem($course);scheduleExpect((int)$after->revision===2&&$after->params_json===$before->params_json&&$after->publish_up===$before->publish_up,'Invalid calendar preserves revision and stored values');
}
$version=$authoring->createDraft($actor,$course,2);
$rev=static fn():int=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$version)->loadResult();
$module=$authoring->saveModule($actor,['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>$rev(),'title'=>'Calendar chapter']);
$lesson=$authoring->saveLesson($actor,['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>$rev(),'module_id'=>$module,'title'=>'Calendar lesson','kind'=>'text','required'=>'1','body'=>'<p>CALENDAR_PRIVATE_BODY</p>']);
$edit['revision']=(int)$model->getItem($course)->revision;
scheduleExpect($model->save($edit+['calendar_ends_at'=>'2098-01-01 01:00:00']),'Calendar can be edited after teaching draft creation');
$publication->transition($actor,$course,$version,(int)$model->getItem($course)->revision,$rev(),(int)$publication->transitions($actor,$course)[0]->id);
$edition=(int)$db->setQuery('SELECT id FROM #__jlms_editions WHERE course_id='.$course)->loadResult();
$window=$db->setQuery('SELECT starts_at,ends_at FROM #__jlms_editions WHERE id='.$edition)->loadAssoc();
scheduleExpect(str_starts_with($window['starts_at'],'2000-01-01 00:00:00')&&str_starts_with($window['ends_at'],'2098-01-01 00:00:00'),'First publication copies latest saved dates into edition');
$snapshot=$db->setQuery('SELECT presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id='.$version)->loadAssoc();
$outline=$catalog->outline(new \Joomla\CMS\User\User(),'en-GB',$course);
scheduleExpect($outline['calendar']['timezone']==='Europe/Rome'&&str_starts_with($outline['calendar']['ends_at'],'2098-01-01 00:00:00')&&$outline['start_window_open'],'Public calendar comes from frozen publication');
$learner=new \Joomla\CMS\User\User();$userData=['name'=>'CI calendar viewer','username'=>'cicalendarviewer','email'=>'cicalendarviewer@example.invalid','password'=>'Ci-only-Calendar-Viewer-2026','password2'=>'Ci-only-Calendar-Viewer-2026','groups'=>[2],'block'=>0,'activation'=>''];
if (!$learner->bind($userData)||!$learner->save())throw new RuntimeException('Calendar viewer fixture failed');
$learner=new \Joomla\CMS\User\User((int)$learner->id);$learning=$component->getLearningService();$key=bin2hex(random_bytes(16));
$enrollment=$learning->start($learner,'en-GB',$course,$key);
$history=$db->setQuery('SELECT revision,completed_total,completed_at FROM #__jlms_enrollments WHERE id='.$enrollment)->loadAssoc();
foreach ([['2099-01-01 00:00:00',null],[null,'2000-01-01 00:00:00']] as [$start,$end]) {
 $change=(object)['id'=>$edition,'starts_at'=>$start,'ends_at'=>$end];$db->updateObject('#__jlms_editions',$change,'id',true);
 scheduleExpect(!$catalog->outline($learner,'en-GB',$course)['start_window_open'],'Current edition window disables start action');
 scheduleDenied(fn()=>$learning->start($learner,'en-GB',$course,$key),403,'Automatic replay rechecks the window');
 scheduleDenied(fn()=>$learning->classroom($learner,'en-GB',$enrollment,$lesson),403,'Classroom denies future/closed window');
 scheduleDenied(fn()=>$learning->complete($learner,'en-GB',$enrollment,$lesson,bin2hex(random_bytes(16))),403,'Completion denies a stale form outside the window');
 scheduleDenied(fn()=>$component->getManualEnrollmentService()->editor($actor,$course,(int)$learner->id,'en-GB'),403,'Manual admission rechecks the window');
 scheduleExpect($history===$db->setQuery('SELECT revision,completed_total,completed_at FROM #__jlms_enrollments WHERE id='.$enrollment)->loadAssoc(),'Window rejection preserves learning history');
}
$restore=(object)(['id'=>$edition]+$window);$db->updateObject('#__jlms_editions',$restore,'id',true);
foreach ([['publish_up','2099-01-01 00:00:00'],['publish_down','1999-01-01 00:00:00']] as [$field,$value]) {
 $current=$db->setQuery('SELECT '.$field.' FROM #__jlms_courses WHERE id='.$course)->loadResult();$change=(object)['id'=>$course,$field=>$value];$db->updateObject('#__jlms_courses',$change,'id',true);
 scheduleDenied(fn()=>$catalog->outline($learner,'en-GB',$course),404,'Publication window gates direct course access');
 $change->$field=$current;$db->updateObject('#__jlms_courses',$change,'id',true);
}
scheduleExpect($model->save(array_replace($edit,['revision'=>(int)$model->getItem($course)->revision,'calendar_ends_at'=>'2097-01-01 00:00:00'])),'Current course calendar can be updated with a native history revision');
foreach ([0,1] as $state) {
 $publication->transition($actor,$course,$version,(int)$model->getItem($course)->revision,$rev(),(int)$publication->transitions($actor,$course)[0]->id);
 scheduleExpect($snapshot===$db->setQuery('SELECT presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id='.$version)->loadAssoc()&&$window===$db->setQuery('SELECT starts_at,ends_at FROM #__jlms_editions WHERE id='.$edition)->loadAssoc(),'Withdrawal/republication preserves snapshot and edition window');
}
$legacy=json_decode(file_get_contents(dirname($root).'/legacy-time-fixture.json'),true,32,JSON_THROW_ON_ERROR);
scheduleExpect(Schedule::fromSnapshot($legacy['presentation_before_upgrade']['presentation_json'])===null,'Legacy snapshots need no calendar backfill');
file_put_contents($fixture,json_encode(['course'=>$course,'version'=>$version,'edition'=>$edition,'window'=>$window,'snapshot'=>$snapshot],JSON_THROW_ON_ERROR));
echo json_encode(['calendar_integration_checks'=>$checks,'status'=>'passed']).PHP_EOL;
