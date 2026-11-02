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


use Jlms\Component\Jlms\Administrator\Application\TeacherProfiles;
$checks = 0;
function teacherExpect(bool $ok,string $message):void { global $checks; if (!$ok) { throw new RuntimeException($message); } $checks++; }
$fixture = dirname($root) . '/teachers-fixture.json';
if (($argv[2] ?? '') === 'retained') {
    $f=json_decode(file_get_contents($fixture),true,32,JSON_THROW_ON_ERROR);
    teacherExpect($db->setQuery('SELECT presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id='.(int)$f['version'])->loadAssoc()===$f['snapshot'],'Frozen teacher projection survives upgrade');
    $outline=$catalog->outline(new \Joomla\CMS\User\User(),'en-GB',(int)$f['course']);
    teacherExpect(count($outline['teachers'])===1&&$outline['teachers'][0]['title']==='Original public teacher','Historical teacher text remains available after upgrade');
    teacherExpect(TeacherProfiles::selected($db,(int)$f['course'])===$f['ids'],'Assigned teacher relations survive upgrade');
    echo json_encode(['teacher_retention_checks'=>$checks,'status'=>'passed']).PHP_EOL;exit;
}
$categoryModel=$app->bootComponent('com_categories')->getMVCFactory()->createModel('Category','Administrator',['ignore_request'=>true]);
teacherExpect($categoryModel->save(['id'=>0,'parent_id'=>1,'extension'=>'com_jlms.teacher','title'=>'CI Teachers','alias'=>'ci-teachers','published'=>1,'language'=>'*','access'=>1]),'Native teacher category created');
$teacherCategory=(int)$categoryModel->getState('category.id');
$teacherModel=$factory->createModel('Teacher','Administrator',['ignore_request'=>true]);
$teacherBase=['id'=>0,'revision'=>1,'title'=>'Original public teacher','alias'=>'original-public-teacher','catid'=>$teacherCategory,'bio'=>'Public biography <script>plain text</script>','image'=>'','image_alt'=>'','image_decorative'=>'0','website'=>'https://example.invalid/teacher','language'=>'*','access'=>1,'state'=>1,'user_id'=>(int)$actor->id];
$groups=$db->setQuery('SELECT user_id,group_id FROM #__user_usergroup_map ORDER BY user_id,group_id')->loadAssocList();
teacherExpect($teacherModel->save($teacherBase+['params_json'=>'{"injected":true}','created_by'=>999999]),'Teacher saved through native model');
$public=(int)$teacherModel->getState('teacher.id');$teacher=$teacherModel->getItem($public);
teacherExpect((int)$teacher->revision===1&&(int)$teacher->created_by===(int)$actor->id&&!str_contains($teacher->params_json,'injected'),'Revision ownership and allowlisted params persisted');
$asset=new \Joomla\CMS\Table\Asset($db);$asset->loadByName('com_jlms.teacher.'.$public);
$parent=(int)$db->setQuery('SELECT asset_id FROM #__categories WHERE id='.$teacherCategory)->loadResult();
teacherExpect((int)$asset->parent_id===$parent,'Teacher asset inherits native teacher category');
teacherExpect($groups===$db->setQuery('SELECT user_id,group_id FROM #__user_usergroup_map ORDER BY user_id,group_id')->loadAssocList(),'Account link grants no groups');
teacherExpect($teacherModel->save(array_replace($teacherBase,['title'=>'PRIVATE_TEACHER_NAME','alias'=>'private-teacher','access'=>2,'bio'=>'PRIVATE_TEACHER_BIO','user_id'=>''])),'Restricted teacher saved without native account');
$private=(int)$teacherModel->getState('teacher.id');
$courseModel=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
$courseBase=['id'=>0,'revision'=>1,'title'=>'Teacher course fixture','alias'=>'teacher-course-fixture','catid'=>$catid,'language'=>'*','catalog_access'=>1,'content_access'=>2,'enrollment_mode'=>'auto','summary'=>'Teacher course'];
teacherExpect(!$courseModel->save($courseBase+['teacher_ids'=>[$public]]),'Course must be saved before atomic assignment');
teacherExpect($courseModel->save($courseBase),'Draft course saved');$course=(int)$courseModel->getState('course.id');
$courseData=array_replace($courseBase,['id'=>$course,'teacher_ids'=>[$public,$private]]);
teacherExpect($courseModel->save($courseData),'Ordered teachers assigned to saved draft');
teacherExpect(TeacherProfiles::selected($db,$course)===[$public,$private],'Teacher order persisted in relation table');
$courseData['revision']=2;unset($courseData['teacher_ids']);
teacherExpect($courseModel->save($courseData),'Legacy client can save without teacher field');
teacherExpect(TeacherProfiles::selected($db,$course)===[$public,$private],'Omitted teacher field preserves assignment');
foreach ([[999999],[$public,$public],['1 OR 1=1'],[['bad']]] as $bad) {
 teacherExpect(!$courseModel->save(array_replace($courseData,['revision'=>3,'teacher_ids'=>$bad])),'Invalid teacher assignment denied');
 teacherExpect(TeacherProfiles::selected($db,$course)===[$public,$private]&&(int)$courseModel->getItem($course)->revision===3,'Denied assignment preserves course and relations');
}
$edit=array_replace($teacherBase,['id'=>$public]);
foreach ([['website'=>'javascript:alert(1)'],['website'=>'https://user:pass@example.invalid/'],['state'=>'-1'],['image_alt'=>['array']],['access'=>['array']],['user_id'=>99999999],['bio'=>str_repeat('a',65536)]] as $bad) {
 teacherExpect(!$teacherModel->save(array_replace($edit,$bad)),'Invalid teacher profile rejected');
 teacherExpect((int)$teacherModel->getItem($public)->revision===1,'Invalid profile retains revision');
}
$teacherModel->setCurrentUser(new \Joomla\CMS\User\User());
teacherExpect(!$teacherModel->save($edit),'Guest cannot manage teachers');$teacherModel->setCurrentUser($actor);
$version=$authoring->createDraft($actor,$course,3);
$rev=static fn():int=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$version)->loadResult();
$module=$authoring->saveModule($actor,['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>$rev(),'title'=>'Teacher chapter']);
$authoring->saveLesson($actor,['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>$rev(),'module_id'=>$module,'title'=>'Teacher lesson','kind'=>'text','required'=>'1','body'=>'<p>TEACHER_PRIVATE_LESSON</p>']);
$publication->transition($actor,$course,$version,(int)$courseModel->getItem($course)->revision,$rev(),(int)$publication->transitions($actor,$course)[0]->id);
$snapshot=$db->setQuery('SELECT presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id='.$version)->loadAssoc();
$guest=new \Joomla\CMS\User\User();$outline=$catalog->outline($guest,'en-GB',$course);
teacherExpect(count($outline['teachers'])===1&&$outline['teachers'][0]['id']===$public,'Guest only sees authorised public teacher');
teacherExpect(!str_contains(json_encode($outline),'PRIVATE_TEACHER_BIO')&&!str_contains(json_encode($outline),'PRIVATE_TEACHER_NAME')&&!str_contains(json_encode($outline),'user_id')&&!str_contains(json_encode($outline),'ci@example.invalid'),'Public projection excludes restricted profile and linked account data');
teacherExpect(count($catalog->outline($actor,'en-GB',$course)['teachers'])===2,'Authorised account can see both frozen teachers');
$edit['title']='Updated public teacher';$edit['bio']='Updated public biography';
teacherExpect($teacherModel->save($edit),'Public profile can be updated independently');
teacherExpect($catalog->outline($guest,'en-GB',$course)['teachers'][0]['title']==='Original public teacher','Profile changes do not rewrite historical teacher text');
teacherExpect(!$teacherModel->save($edit),'Stale teacher revision rejected');
$edit['revision']=2;$edit['state']=0;teacherExpect($teacherModel->save($edit),'Teacher can be withdrawn with state permission');
teacherExpect($catalog->outline($guest,'en-GB',$course)['teachers']===[],'Withdrawal hides historical profile without changing snapshot');
$edit['revision']=3;$edit['state']=1;$edit['access']=2;teacherExpect($teacherModel->save($edit),'Teacher republished with stricter level');
teacherExpect($catalog->outline($guest,'en-GB',$course)['teachers']===[],'Current teacher level rechecked for historical data');
$edit['revision']=4;$edit['access']=1;teacherExpect($teacherModel->save($edit),'Teacher public availability restored');
$privateEdit=array_replace($teacherBase,['id'=>$private,'title'=>'PRIVATE_TEACHER_NAME','alias'=>'private-teacher','access'=>1,'bio'=>'PRIVATE_TEACHER_BIO','user_id'=>'']);
teacherExpect($teacherModel->save($privateEdit),'Restricted profile can become public for future versions');
teacherExpect(count($catalog->outline($guest,'en-GB',$course)['teachers'])===1,'Looser current access never exposes a previously restricted snapshot');
$update=(object)['id'=>$teacherCategory,'published'=>0];$db->updateObject('#__categories',$update,'id');
teacherExpect($catalog->outline($guest,'en-GB',$course)['teachers']===[],'Native category withdrawal gates teacher history');
$update->published=1;$db->updateObject('#__categories',$update,'id');
teacherExpect(!$courseModel->save(array_replace($courseData,['revision'=>(int)$courseModel->getItem($course)->revision,'teacher_ids'=>[]])),'Published course assignments immutable');
teacherExpect($snapshot===$db->setQuery('SELECT presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id='.$version)->loadAssoc(),'All profile changes preserve course snapshot hash and timestamp');
file_put_contents($fixture,json_encode(['course'=>$course,'version'=>$version,'ids'=>[$public,$private],'public'=>$public,'private'=>$private,'teacher_category'=>$teacherCategory,'snapshot'=>$snapshot],JSON_THROW_ON_ERROR));
echo json_encode(['teacher_checks'=>$checks,'status'=>'passed']).PHP_EOL;
