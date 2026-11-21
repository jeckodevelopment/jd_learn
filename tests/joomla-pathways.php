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
    $check(count($detail['courses'])===2 && $detail['courses'][0]['id']===(int)$data['course']
        && $detail['courses'][1]['id']===null && $detail['courses'][1]['title']==='',
        'public pathway service preserves visible course and anonymous hidden placeholder after update');
    echo json_encode(['pathway_retention_checks'=>4,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;
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
$check($detail['progress']===0 && $detail['required_courses']===1, 'enrolled course with defined rules permits a zero percent pathway aggregate');
$hiddenCourseId = (int)$db->setQuery("SELECT id FROM #__jlms_courses WHERE alias='test-course'")->loadResult();
if (!$hiddenCourseId) { throw new RuntimeException('Draft course fixture missing.'); }
$check((bool)$pathModel->save(['id'=>$pathwayId,'revision'=>1,'title'=>'CI learning pathway','alias'=>'ci-learning-pathway',
    'description'=>'<p>Pathway summary</p>','catid'=>$categoryId,'access'=>1,'language'=>'*','state'=>1,
    'courses'=>[['course_id'=>$courseId,'ordering'=>10],['course_id'=>$hiddenCourseId,'ordering'=>20]]]),'second course association saves');
$detail = $service->detail($operator,'en-GB',$pathwayId);
$check(count($detail['courses'])===2 && $detail['courses'][0]['id']===$courseId,'visible course is first in its authored order');
$check($detail['courses'][1]['id']===null && $detail['courses'][1]['title']==='','draft course becomes an unidentified placeholder');

// LCM-05: a published course with disabled delivery remains catalog-visible but
// must not be presented as a usable classroom inside a learning pathway.
$initialDelivery=(int)$db->setQuery('SELECT delivery_enabled FROM #__jlms_courses WHERE id='.(int)$courseId)->loadResult();
$initialEnrollments=(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id='.(int)$courseId)->loadResult();
try {
    $db->setQuery('UPDATE #__jlms_courses SET delivery_enabled=0 WHERE id='.(int)$courseId)->execute();
    $suspendedPathway=$service->detail($operator,'en-GB',$pathwayId);
    $check((int)$suspendedPathway['courses'][0]['id']===$courseId
        && $suspendedPathway['courses'][0]['access']==='unavailable'
        && $suspendedPathway['courses'][0]['delivery_suspended']===true
        && $suspendedPathway['courses'][0]['learning_state']!=='unavailable',
        'published but delivery-suspended course retains catalog and learning status without promising access');
    $check((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id='.(int)$courseId)->loadResult()===$initialEnrollments,
        'delivery suspension is read-only for pathway enrollment history');
} finally {
    $db->setQuery('UPDATE #__jlms_courses SET delivery_enabled='.(int)$initialDelivery.' WHERE id='.(int)$courseId)->execute();
}
$deliveryRestored=$service->detail($operator,'en-GB',$pathwayId);
$check($deliveryRestored['courses'][0]['access']==='available','restored delivery returns pathway course to its previous availability');
$check($deliveryRestored['courses'][0]['delivery_suspended']===false,'re-enabled delivery clears suspension display state');
$check(empty($service->detail(new Joomla\CMS\User\User(),'en-GB',$pathwayId)['courses'][0]['delivery_suspended']),'guest without content rights cannot inspect course delivery suspension');

$check($detail['progress']===null && $detail['required_courses']===2,'unavailable course without evidence suppresses ungrounded aggregate');
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
// LCM-03: archive hides the public pathway without mutating its course links,
// student enrollments or historical progress; restoring requires explicit publication.
$pathwayCourses = [['course_id'=>$courseId,'ordering'=>10],['course_id'=>$hiddenCourseId,'ordering'=>20]];
$pathwayBase = ['id'=>$pathwayId,'title'=>'CI learning pathway','alias'=>'ci-learning-pathway',
    'description'=>'<p>Pathway summary</p>','catid'=>$categoryId,'access'=>1,'language'=>'*',
    'courses'=>$pathwayCourses];
$beforeRelations = $db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering')->loadColumn();
$enrollmentsBeforeArchive = (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . $courseId)->loadResult();
// LCM-05: a pathway lifecycle change must not mutate its constituent courses.
$courseStatesBefore = $db->setQuery('SELECT id,state FROM #__jlms_courses WHERE id IN (' . $courseId . ',' . $hiddenCourseId . ') ORDER BY id')->loadAssocList();
$check((bool)$pathModel->save($pathwayBase + ['revision'=>2,'state'=>2]),
    'archiving a published pathway succeeds: ' . $pathModel->getError());
$check((int)$db->setQuery('SELECT state FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult() === 2,
    'archived pathway state is stored');
$archiveDenied = false;
try { $service->detail($operator,'en-GB',$pathwayId); }
catch (\RuntimeException $error) { $archiveDenied = $error->getCode() === 404; }
$check($archiveDenied,'archived pathway cannot be read through the public detail endpoint');
$archiveGuestDenied = false;
try { $service->detail($guest, 'en-GB', $pathwayId); }
catch (\RuntimeException $error) { $archiveGuestDenied = $error->getCode() === 404; }
$check($archiveGuestDenied, 'archived pathway direct detail also denies anonymous visitors');
$check($db->setQuery('SELECT id,state FROM #__jlms_courses WHERE id IN (' . $courseId . ',' . $hiddenCourseId . ') ORDER BY id')->loadAssocList() === $courseStatesBefore,
    'archiving a pathway never changes the states of its constituent courses');
$check($db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering')->loadColumn() === $beforeRelations,
    'archiving preserves pathway course associations');
$check((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . $courseId)->loadResult() === $enrollmentsBeforeArchive,
    'archiving a pathway does not change course enrollments');
$check((bool)$pathModel->save($pathwayBase + ['revision'=>3,'state'=>0]),
    'restoring archived pathway to unpublished succeeds: ' . $pathModel->getError());
$draftDenied = false;
try { $service->detail($operator,'en-GB',$pathwayId); }
catch (\RuntimeException $error) { $draftDenied = $error->getCode() === 404; }
$check($draftDenied,'restoration does not automatically republish a pathway');
$check((bool)$pathModel->save($pathwayBase + ['revision'=>4,'state'=>1]),
    'explicit republication of restored pathway succeeds: ' . $pathModel->getError());
$restored = $service->detail($operator,'en-GB',$pathwayId);
$check(count($restored['courses']) === 2 && $restored['courses'][0]['id'] === $courseId,
    'explicit republication restores the same public pathway course ordering');
// LCM-03: reversible trash is isolated from course enrolments and the public catalogue.
$check((bool)$pathModel->save($pathwayBase + ['revision'=>5,'state'=>-2]),
    'moving a pathway to trash succeeds: ' . $pathModel->getError());
$check((int)$db->setQuery('SELECT state FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult() === -2,
    'trash is represented by the native state');
$trashDenied = false;
try { $service->detail($operator, 'en-GB', $pathwayId); }
catch (\RuntimeException $error) { $trashDenied = $error->getCode() === 404; }
$check($trashDenied, 'trashed pathway is inaccessible via direct public URL');
$trashGuestDenied = false;
try { $service->detail($guest, 'en-GB', $pathwayId); }
catch (\RuntimeException $error) { $trashGuestDenied = $error->getCode() === 404; }
$check($trashGuestDenied, 'trashed pathway direct detail also denies anonymous visitors');
$check($db->setQuery('SELECT id,state FROM #__jlms_courses WHERE id IN (' . $courseId . ',' . $hiddenCourseId . ') ORDER BY id')->loadAssocList() === $courseStatesBefore,
    'trashing a pathway never changes the states of its constituent courses');
$check($db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering')->loadColumn() === $beforeRelations,
    'trash retains every course association in order');
$check((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . $courseId)->loadResult() === $enrollmentsBeforeArchive,
    'trash does not alter course enrolments');
$check(!$pathModel->save($pathwayBase + ['revision'=>6,'state'=>1]),
    'trashed pathway cannot be published directly without draft restoration');
$check((int)$db->setQuery('SELECT state FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult() === -2,
    'rejected direct publishing leaves trashed record intact');
$check((bool)$pathModel->save($pathwayBase + ['revision'=>6,'state'=>0]),
    'restoring from trash to unpublished succeeds');
$check((int)$db->setQuery('SELECT state FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult() === 0,
    'restoration does not automatically publish');
$check((bool)$pathModel->save($pathwayBase + ['revision'=>7,'state'=>1]),
    'explicit republication after trash restoration succeeds');
$check(count($service->detail($operator, 'en-GB', $pathwayId)['courses']) === 2,
    'republished pathway preserves all associations');
// LCM-03: multi-row operations must commit together and preserve relationships.
$beforeBulkCourses = $db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering')->loadColumn();
$beforeBulkEnrollments = (int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . $courseId)->loadResult();
$check($pathModel->changeStates([$pathwayId, $emptyId], -2), 'bulk trash of two pathways succeeds: ' . $pathModel->getError());
$check((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_pathways WHERE id IN (' . $pathwayId . ',' . $emptyId . ') AND state=-2')->loadResult() === 2,
    'both selected pathways are trashed');
$check(!$pathModel->changeStates([$pathwayId, 999999999], 0),
    'bulk restore including missing record must roll back every transition');
$check((int) $db->setQuery('SELECT state FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult() === -2,
    'failed bulk selection did not restore first pathway');
$check(!$pathModel->changeStates([$pathwayId, $pathwayId], 0), 'duplicate identifiers denied');
$check(!$pathModel->changeStates([$pathwayId], 1), 'bulk action cannot publish directly from trash');
$check($pathModel->changeStates([$pathwayId, $emptyId], 0), 'bulk restore moves both pathways to unpublished');
$check((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_pathways WHERE id IN (' . $pathwayId . ',' . $emptyId . ') AND state=0')->loadResult() === 2,
    'restoration leaves both pathways unpublished');
$check($db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering')->loadColumn() === $beforeBulkCourses,
    'bulk actions preserve pathway course links');
$check((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . $courseId)->loadResult() === $beforeBulkEnrollments,
    'bulk actions preserve historical enrollments');
$check($pathModel->changeStates([$pathwayId, $emptyId], 1), 'explicit mass publication after restore succeeds');
// Single-record protected permanent delete: populated pathways always denied.
$check($pathModel->changeStates([$pathwayId, $emptyId], -2), 'prepare both pathways for protected purge');
$check(!$pathModel->purgeEmpty($pathwayId), 'pathway with associated courses cannot be deleted');
$check((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId)->loadResult() === 2,
    'blocked permanent deletion leaves pathway courses intact');
// LCM-05: direct model mutations must enforce server-side permissions even
// when a caller bypasses the administration toolbar and submits valid IDs.
$pathModel->setCurrentUser(new Joomla\CMS\User\User());
$check(!$pathModel->changeStates([$pathwayId, $emptyId], 0),
    'anonymous actor cannot restore trashed pathways through the model');
$check(!$pathModel->purgeEmpty($emptyId),
    'anonymous actor cannot permanently delete a trashed empty pathway');
$check((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_pathways WHERE id IN (' . $pathwayId . ',' . $emptyId . ') AND state=-2')->loadResult() === 2,
    'denied mutation leaves both trashed pathway records unchanged');
$check($db->setQuery('SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering')->loadColumn() === $beforeBulkCourses,
    'unauthorised lifecycle requests preserve course associations');
$pathModel->setCurrentUser($operator);
$check(!$pathModel->purgeEmpty(0), 'invalid permanent deletion identifier rejected');
$check(!$pathModel->purgeEmpty(999999999), 'nonexistent pathway deletion rejected');
$check($pathModel->purgeEmpty($emptyId), 'only an empty trashed pathway can be deleted');
$check((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_pathways WHERE id=' . $emptyId)->loadResult() === 0,
    'empty trashed pathway is removed');
$check((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_enrollments WHERE course_id=' . $courseId)->loadResult() === $beforeBulkEnrollments,
    'protected purge leaves course enrollments untouched');
$check($db->setQuery('SELECT id,state FROM #__jlms_courses WHERE id IN (' . $courseId . ',' . $hiddenCourseId . ') ORDER BY id')->loadAssocList() === $courseStatesBefore,
    'permanent deletion of an empty pathway leaves constituent course states untouched');
$check($pathModel->changeStates([$pathwayId], 0) && $pathModel->changeStates([$pathwayId], 1),
    'the populated pathway is restored for retention testing');
// LCM-03: an editorial patch without the courses key must not clear the
// programme. Supplying courses=[] explicitly must still clear it.
$currentRevision = (int)$db->setQuery('SELECT revision FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult();
$partial = $pathwayBase + ['revision'=>$currentRevision,'state'=>1];
unset($partial['courses']);
$check($pathModel->save($partial), 'partial editorial save without courses succeeds: ' . $pathModel->getError());
$readLinks = static fn (): array => array_map('intval', $db->setQuery(
    'SELECT course_id FROM #__jlms_pathway_courses WHERE pathway_id=' . $pathwayId . ' ORDER BY ordering'
)->loadColumn());
$check($readLinks() === array_map('intval', $beforeBulkCourses),
    'partial editorial save preserves all existing pathway course links');
$currentRevision = (int)$db->setQuery('SELECT revision FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult();
$check($pathModel->save(array_replace($pathwayBase, ['revision'=>$currentRevision,'state'=>1,'courses'=>[]])),
    'explicitly empty courses list clears pathway programme');
$check($readLinks() === [], 'explicit empty courses list removes course links');
$currentRevision = (int)$db->setQuery('SELECT revision FROM #__jlms_pathways WHERE id=' . $pathwayId)->loadResult();
$check($pathModel->save($pathwayBase + ['revision'=>$currentRevision,'state'=>1]),
    'restore original programme for retention testing');
$check($readLinks() === array_map('intval', $beforeBulkCourses),
    'restored links retain original course order');
file_put_contents($fixture,json_encode(['pathway'=>$pathwayId,'course'=>$courseId,'hidden_course'=>$hiddenCourseId,'student'=>(int)$operator->id],JSON_THROW_ON_ERROR));
echo json_encode(['pathway_checks'=>14,'status'=>'passed','pathway_id'=>$pathwayId,'student_enrollment'=>$student],JSON_THROW_ON_ERROR).PHP_EOL;
