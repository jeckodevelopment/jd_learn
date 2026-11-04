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


$authoring = $component->getCurriculumService();
$publication = $component->getPublicationService();
$catalog = $component->getCatalogService();
$actor = $app->getIdentity();
$catid = (int) $db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
use Jlms\Component\Jlms\Administrator\Application\CoursePresentation;



$checks=0;
function listExpect(bool $ok,string $message):void { global $checks;if(!$ok)throw new RuntimeException($message);$checks++; }
$fixturePath=dirname($root).'/courses-ui-fixture.json';
if (($argv[2]??'')==='retained') {
 $f=json_decode(file_get_contents($fixturePath),true,32,JSON_THROW_ON_ERROR);
 $rows=$db->setQuery('SELECT id,state,current_version_id,revision FROM #__jlms_courses WHERE id IN ('.implode(',',array_map('intval',$f['courses'])).') ORDER BY id')->loadAssocList();
 listExpect($rows===$f['rows'],'List and editor navigation preserve course state, version and revision after upgrade');
 echo json_encode(['courses_ui_retention_checks'=>$checks,'status'=>'passed']).PHP_EOL;exit;
}
$categoryModel=$app->bootComponent('com_categories')->getMVCFactory()->createModel('Category','Administrator',['ignore_request'=>true]);
$parent=1;$categories=[];
foreach(['parent','child'] as $name){
 listExpect($categoryModel->save(['id'=>0,'parent_id'=>$parent,'extension'=>'com_jlms.course','title'=>'List UI '.$name,'alias'=>'list-ui-'.$name,'published'=>1,'language'=>'*','access'=>1]),'Native list category fixture');
 $categories[$name]=(int)$categoryModel->getState('category.id');$parent=$categories[$name];
}
$editor=(int)$db->setQuery("SELECT id FROM #__users WHERE username='cieditor'")->loadResult();
$ids=[];$versions=[];
foreach(['Zebra','Alpha','Bravo'] as $name){
 $model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
 listExpect($model->save(['id'=>0,'revision'=>1,'title'=>'List UI '.$name,'alias'=>'list-ui-'.strtolower($name),'catid'=>$categories[$name==='Alpha'?'child':'parent'],'language'=>$name==='Alpha'?'en-GB':'*','catalog_access'=>1,'content_access'=>$name==='Bravo'?1:2,'enrollment_mode'=>'auto','summary'=>'List fixture','presentation_description'=>'<p>List UI public presentation</p>']),'List course fixture');
 $id=(int)$model->getState('course.id');$ids[$name]=$id;
 $draft=$authoring->createDraft($actor,$id,(int)$model->getItem($id)->revision);$versions[$name]=$draft;
 $module=$authoring->saveModule($actor,['course_id'=>$id,'version_id'=>$draft,'expected_version_revision'=>1,'title'=>'List UI module']);
 $revision=(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$draft)->loadResult();
 $authoring->saveLesson($actor,['course_id'=>$id,'version_id'=>$draft,'expected_version_revision'=>$revision,'module_id'=>$module,'title'=>'List UI lesson','kind'=>'text','body'=>'<p>LIST_UI_PRIVATE_LESSON_BODY</p>','required'=>'1']);
 if($name==='Alpha'){
  $choices=$publication->transitions($actor,$id,$draft);
  $publication->transition($actor,$id,$draft,(int)$model->getItem($id)->revision,(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$draft)->loadResult(),(int)$choices[0]->id);
 }
}
$change=(object)['id'=>$ids['Bravo'],'created_by'=>$editor];$db->updateObject('#__jlms_courses',$change,'id');
$change=(object)['id'=>$ids['Zebra'],'checked_out'=>$editor,'checked_out_time'=>gmdate('Y-m-d H:i:s')];$db->updateObject('#__jlms_courses',$change,'id');
$list=$factory->createModel('Courses','Administrator',['ignore_request'=>true]);$list->setState('list.limit',100);$list->setState('list.start',0);$list->setState('filter.search','List UI');$list->setState('list.ordering','a.title');$list->setState('list.direction','ASC');
$get=static fn():array=>array_map(static fn($row):int=>(int)$row->id,$list->getItems());
listExpect($get()===[$ids['Alpha'],$ids['Bravo'],$ids['Zebra']]&&(int)$list->getTotal()===3,'Native list sorting, search and total agree');
$list->setState('filter.published','1');listExpect($get()===[$ids['Alpha']]&&(int)$list->getTotal()===1,'Published filter shares the total query');
$list->setState('filter.published','0');listExpect($get()===[$ids['Bravo'],$ids['Zebra']]&&(int)$list->getTotal()===2,'Filter changes invalidate cached items and totals');
$list->setState('filter.published','');$list->setState('filter.category_id',(string)$categories['parent']);listExpect(count($get())===3,'Category filter includes native descendants');
$list->setState('filter.category_id',(string)$categories['child']);listExpect($get()===[$ids['Alpha']],'Child category filter is precise');
$list->setState('filter.category_id','');$list->setState('filter.author_id',(string)$editor);listExpect($get()===[$ids['Bravo']],'Native author filter');
$list->setState('filter.author_id','');$list->setState('filter.access','1');listExpect($get()===[$ids['Bravo']],'Content access level filter');
$list->setState('filter.access','');$list->setState('filter.language','en-GB');listExpect($get()===[$ids['Alpha']],'Language filter');
$list->setState('filter.language','');$list->setState('filter.checked_out','1');listExpect($get()===[$ids['Zebra']],'Checkout filter');
$list->setState('filter.checked_out','0');listExpect($get()===[$ids['Alpha'],$ids['Bravo']],'Unlocked filter');
$list->setState('filter.checked_out','');$list->setState('filter.search','id:'.$ids['Bravo']);listExpect($get()===[$ids['Bravo']],'Native id search');
$list->setState('filter.search',"' OR 1=1 --");listExpect($get()===[],'Search is bound SQL input');
$list->setState('filter.search','List UI');$list->setState('filter.published',['1']);listExpect($get()===[],'Malformed filters fail closed');
$list->setState('filter.published','');$list->setState('list.ordering','a.title; DROP TABLE #__jlms_courses');$list->setState('list.direction','DESC; DELETE');listExpect(count($get())===3,'SQL ordering is allowlisted');
$restricted=$factory->createModel('Courses','Administrator',['ignore_request'=>true]);$restricted->setCurrentUser(new \Joomla\CMS\User\User($editor));$restricted->setState('list.limit',100);$restricted->setState('list.start',0);$restricted->setState('filter.search','id:'.$ids['Alpha']);
listExpect(count($restricted->getItems())===0&&(int)$restricted->getTotal()===0,'Filters and totals cannot expose an uneditable course');
$rows=$db->setQuery('SELECT id,state,current_version_id,revision FROM #__jlms_courses WHERE id IN ('.implode(',',$ids).') ORDER BY id')->loadAssocList();
file_put_contents(dirname($root).'/courses-ui-fixture.json',json_encode(['courses'=>$ids,'versions'=>$versions,'categories'=>$categories,'editor'=>$editor,'rows'=>$rows],JSON_THROW_ON_ERROR));
echo json_encode(['courses_ui_checks'=>$checks,'status'=>'passed']).PHP_EOL;
