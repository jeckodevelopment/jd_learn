<?php

declare(strict_types=1);

/** Native pathway authoring, visibility and aggregate-progress integration test. */
$root = realpath($argv[1] ?? '');
$retained = ($argv[2] ?? '') === 'retained';
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new RuntimeException('Disposable Joomla site required.');
}
$_SERVER += ['HTTP_HOST'=>'localhost','SERVER_NAME'=>'localhost','SERVER_PORT'=>'80','REQUEST_METHOD'=>'GET',
    'REQUEST_URI'=>'/administrator/index.php?option=com_jlms','SCRIPT_NAME'=>'/administrator/index.php',
    'PHP_SELF'=>'/administrator/index.php','SCRIPT_FILENAME'=>$root.'/administrator/index.php'];
define('_JEXEC', 1);
define('JPATH_BASE', $root.'/administrator');
require $root.'/administrator/includes/defines.php';
require $root.'/administrator/includes/framework.php';
define('JPATH_COMPONENT', JPATH_ADMINISTRATOR.'/components/com_jlms');
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_COMPONENT);
define('JPATH_COMPONENT_SITE', JPATH_SITE.'/components/com_jlms');

use Joomla\CMS\Factory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\UserFactoryInterface;

$container = Factory::getContainer();
$container->alias('session.web','session.web.administrator')->alias('session','session.web.administrator')
    ->alias(Session::class,'session.web.administrator')->alias(SessionInterface::class,'session.web.administrator');
$app = $container->get(AdministratorApplication::class);
Factory::$application = $app;
$app->createExtensionNamespaceMap();
$db = $container->get(DatabaseInterface::class);
$operatorId = (int)$db->setQuery("SELECT id FROM #__users WHERE username='cioperator'")->loadResult();
if (!$operatorId) { throw new RuntimeException('CI operator missing.'); }
$app->loadIdentity($container->get(UserFactoryInterface::class)->loadUserById($operatorId));
$app->getSession()->set('user',$app->getIdentity());
$component = $app->bootComponent('com_jlms');
$service = $component->getPathwayService();
$fixture = dirname($root).'/pathways-fixture.json';
$check = static function(bool $ok,string $message): void { if (!$ok) throw new RuntimeException($message); };

if ($retained) {
    $data = json_decode((string)file_get_contents($fixture),true,16,JSON_THROW_ON_ERROR);
    $path = $db->setQuery('SELECT id FROM #__jlms_pathways WHERE id='.(int)$data['pathway'])->loadResult();
    $check((int)$path === (int)$data['pathway'],'pathway record retained through package update');
    $rows = $db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id='.(int)$path.' ORDER BY ordering')->loadColumn();
    $check(array_map('intval',$rows) === [(int)$data['course'],(int)$data['hidden_course']],'ordered course associations retained through package update');
    $student = new Joomla\CMS\User\User((int)$data['student']);
    $detail = $service->detail($student,'en-GB',(int)$path);
    $check(count($detail['courses'])===1 && $detail['courses'][0]['id']===(int)$data['course'],
        'public pathway service remains usable after update: '.json_encode($detail,JSON_THROW_ON_ERROR));
    echo json_encode(['pathway_retention_checks'=>3,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;
    return;
}

$categoryModel = $app->bootComponent('com_categories')->getMVCFactory()->createModel('Category','Administrator',['ignore_request'=>true]);
$categoryModel->setState('category.extension','com_jlms.pathway');
$categoryModel->setState('category.component','com_jlms');
$categoryModel->setState('category.section','pathway');
$category = ['id'=>0,'parent_id'=>1,'extension'=>'com_jlms.pathway','title'=>'CI pathway category','alias'=>'ci-pathway-category',
    'published'=>1,'language'=>'*','access'=>1,'description'=>''];
if (!$categoryModel->save($category)) { throw new RuntimeException('Native pathway category creation failed: '.$categoryModel->getError()); }
$categoryId = (int)$categoryModel->getState('category.id');
$courseId = (int)$db->setQuery("SELECT id FROM #__jlms_courses WHERE alias='publication-test'")->loadResult();
if (!$courseId) { throw new RuntimeException('Published catalog course fixture missing.'); }
$pathModel = $component->getMVCFactory()->createModel('Pathway','Administrator',['ignore_request'=>true]);
$payload = ['id'=>0,'revision'=>1,'title'=>'CI learning pathway','alias'=>'ci-learning-pathway','description'=>'<p>Pathway summary</p>',
    'catid'=>$categoryId,'access'=>1,'language'=>'*','state'=>1,'courses'=>[['course_id'=>$courseId,'ordering'=>10]]];
$check((bool)$pathModel->save($payload),'native pathway model saves pathway and course order: '.$pathModel->getError());
$pathwayId = (int)$db->setQuery("SELECT id FROM #__jlms_pathways WHERE alias='ci-learning-pathway'")->loadResult();
$stored = $db->setQuery('SELECT course_id,ordering FROM #__jlms_pathway_courses WHERE pathway_id='.(int)$pathwayId)->loadObject();
$check($pathwayId>0 && (int)$stored->course_id===$courseId && (int)$stored->ordering===0,'native authoring normalizes and persists ordering');
$operator = $app->getIdentity();
$student = $component->getLearningService()->start($operator,'en-GB',$courseId,str_repeat('p',40));
$detail = $service->detail($operator,'en-GB',$pathwayId);
$hiddenCourseId = (int)$db->setQuery("SELECT id FROM #__jlms_courses WHERE alias='test-course'")->loadResult();
if (!$hiddenCourseId) { throw new RuntimeException('Draft course fixture missing.'); }
$check((bool)$pathModel->save(['id'=>$pathwayId,'revision'=>1,'title'=>'CI learning pathway','alias'=>'ci-learning-pathway',
    'description'=>'<p>Pathway summary</p>','catid'=>$categoryId,'access'=>1,'language'=>'*','state'=>1,
    'courses'=>[['course_id'=>$courseId,'ordering'=>10],['course_id'=>$hiddenCourseId,'ordering'=>20]]]),'second course association saves');
$detail = $service->detail($operator,'en-GB',$pathwayId);
$check(count($detail['courses'])===2 && $detail['courses'][0]['id']===$courseId,'visible course is first in its authored order');
$check($detail['courses'][1]['id']===null && $detail['courses'][1]['title']==='','draft course becomes an unidentified placeholder');
$check($detail['progress']===0 && $detail['required_courses']===2,'not-started and inaccessible courses contribute zero to aggregate');
$guest = new Joomla\CMS\User\User();
$guestDetail = $service->detail($guest,'en-GB',$pathwayId);
$check(count($guestDetail['courses'])===2 && $guestDetail['courses'][0]['access']==='restricted','catalog-visible but content-restricted course is labeled restricted');
$check($guestDetail['courses'][1]['id']===null && $guestDetail['courses'][1]['title']==='','guest cannot identify the draft course');
$check($guestDetail['progress']===null && $guestDetail['has_unavailable_courses']===true,'guest has no personal percentage while unavailable course contributes zero internally');
$before = (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE user_id='.(int)$operator->id.' AND course_id='.(int)$courseId)->loadResult();
$again = $service->detail($guest,'en-GB',$pathwayId);
$after = (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE user_id='.(int)$operator->id.' AND course_id='.(int)$courseId)->loadResult();
$check($before===$after,'reading a pathway never changes enrollment or access');
$rejected = ['id'=>0,'revision'=>1,'title'=>'Rejected pathway','alias'=>'rejected-ci-pathway','description'=>'','catid'=>$categoryId,
    'access'=>1,'language'=>'*','state'=>1,'courses'=>[['course_id'=>$courseId,'ordering'=>0],['course_id'=>$courseId,'ordering'=>1]]];
$check(!$pathModel->save($rejected),'duplicate course association is rejected');
$check(!(int)$db->setQuery("SELECT COUNT(*) FROM #__assets WHERE name LIKE 'com_jlms.pathway.tmp.%'")->loadResult(),
    'failed pathway save removes its prepared Joomla asset');
$emptyPayload = ['id'=>0,'revision'=>1,'title'=>'Empty CI pathway','alias'=>'empty-ci-pathway','description'=>'','catid'=>$categoryId,
    'access'=>1,'language'=>'*','state'=>1,'courses'=>[]];
$check((bool)$pathModel->save($emptyPayload),'empty pathway can be authored: '.$pathModel->getError());
$emptyId=(int)$db->setQuery("SELECT id FROM #__jlms_pathways WHERE alias='empty-ci-pathway'")->loadResult();
$emptyDetail=$service->detail($operator,'en-GB',$emptyId);
$check($emptyDetail['progress']===null && $emptyDetail['required_courses']===0,'empty pathway has no completion percentage');
file_put_contents($fixture,json_encode(['pathway'=>$pathwayId,'course'=>$courseId,'hidden_course'=>$hiddenCourseId,'student'=>(int)$operator->id],JSON_THROW_ON_ERROR));
echo json_encode(['pathway_checks'=>14,'status'=>'passed','pathway_id'=>$pathwayId,'student_enrollment'=>$student],JSON_THROW_ON_ERROR).PHP_EOL;
