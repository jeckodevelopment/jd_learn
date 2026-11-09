<?php

declare(strict_types=1);

/** Review workflow integration test; run only on the disposable CI Joomla site. */
$root = realpath($argv[1] ?? '');
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') { throw new RuntimeException('Disposable Joomla site required.'); }
$_SERVER += ['HTTP_HOST'=>'localhost','SERVER_NAME'=>'localhost','SERVER_PORT'=>'80','REQUEST_METHOD'=>'GET',
    'REQUEST_URI'=>'/administrator/index.php?option=com_jlms','SCRIPT_NAME'=>'/administrator/index.php',
    'PHP_SELF'=>'/administrator/index.php','SCRIPT_FILENAME'=>$root.'/administrator/index.php'];
define('_JEXEC',1); define('JPATH_BASE',$root.'/administrator');
require $root.'/administrator/includes/defines.php'; require $root.'/administrator/includes/framework.php';
define('JPATH_COMPONENT',JPATH_ADMINISTRATOR.'/components/com_jlms');
define('JPATH_COMPONENT_ADMINISTRATOR',JPATH_COMPONENT); define('JPATH_COMPONENT_SITE',JPATH_SITE.'/components/com_jlms');

use Joomla\CMS\Factory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Workflow\Workflow;
use Joomla\Registry\Registry;
use Jlms\Component\Jlms\Administrator\Infrastructure\WorkflowInstaller;

$container=Factory::getContainer();
$container->alias('session.web','session.web.administrator')->alias('session','session.web.administrator')
    ->alias(Session::class,'session.web.administrator')->alias(SessionInterface::class,'session.web.administrator');
$app=$container->get(AdministratorApplication::class); Factory::$application=$app; $app->createExtensionNamespaceMap();
$db=$container->get(DatabaseInterface::class);
$actorId=(int)$db->setQuery("SELECT id FROM #__users WHERE username='cioperator'")->loadResult();
if(!$actorId)throw new RuntimeException('CI administrator missing.');
$app->loadIdentity($container->get(UserFactoryInterface::class)->loadUserById($actorId));
$app->getSession()->set('user',$app->getIdentity()); $actor=$app->getIdentity();
$component=$app->bootComponent('com_jlms'); $publication=$component->getPublicationService(); $authoring=$component->getCurriculumService();
WorkflowInstaller::install($app,$db);
$workflow=new Workflow('com_jlms.course',$app,$db);
$reviewStageCount=(int)$db->setQuery("SELECT COUNT(*) FROM #__workflow_stages WHERE title='COM_JLMS_IN_REVIEW' AND workflow_id=(SELECT id FROM #__workflows WHERE extension='com_jlms.course' AND title='JD Learn' LIMIT 1)")->loadResult();
$reviewTransitionCount=(int)$db->setQuery("SELECT COUNT(*) FROM #__workflow_transitions WHERE title IN ('COM_JLMS_SUBMIT_REVIEW_DRAFT','COM_JLMS_SUBMIT_REVIEW_UPDATE','COM_JLMS_RETURN_INITIAL_DRAFT','COM_JLMS_RETURN_PUBLISHED_DRAFT','COM_JLMS_APPROVE_REVIEW') AND workflow_id=(SELECT id FROM #__workflows WHERE extension='com_jlms.course' AND title='JD Learn' LIMIT 1)")->loadResult();
WorkflowInstaller::install($app,$db);
if($reviewStageCount!==1||$reviewTransitionCount!==5
    ||$reviewStageCount!==(int)$db->setQuery("SELECT COUNT(*) FROM #__workflow_stages WHERE title='COM_JLMS_IN_REVIEW' AND workflow_id=(SELECT id FROM #__workflows WHERE extension='com_jlms.course' AND title='JD Learn' LIMIT 1)")->loadResult()
    ||$reviewTransitionCount!==(int)$db->setQuery("SELECT COUNT(*) FROM #__workflow_transitions WHERE title IN ('COM_JLMS_SUBMIT_REVIEW_DRAFT','COM_JLMS_SUBMIT_REVIEW_UPDATE','COM_JLMS_RETURN_INITIAL_DRAFT','COM_JLMS_RETURN_PUBLISHED_DRAFT','COM_JLMS_APPROVE_REVIEW') AND workflow_id=(SELECT id FROM #__workflows WHERE extension='com_jlms.course' AND title='JD Learn' LIMIT 1)")->loadResult()) {
    throw new RuntimeException('Review workflow install/upgrade must be idempotent.');
}
$cat=(int)$db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
$model=$component->getMVCFactory()->createModel('Course','Administrator',['ignore_request'=>true]);
$alias='review-'.bin2hex(random_bytes(6));
$data=['id'=>0,'title'=>'Review workflow test','alias'=>$alias,'catid'=>$cat,'language'=>'*','catalog_access'=>1,'content_access'=>2,
    'enrollment_mode'=>'auto','summary'=>'Review test summary','presentation_description'=>'<p>Review test course description.</p>','revision'=>1];
if(!$model->save($data))throw new RuntimeException('Review fixture failed: '.$model->getError());
$course=(int)$model->getState('course.id');
$rev=static fn(string $table,int $id):int=>(int)$db->setQuery('SELECT revision FROM #__jlms_'.$table.' WHERE id='.(int)$id)->loadResult();
$version=$authoring->createDraft($actor,$course,$rev('courses',$course));
$scope=static fn(int $version):array=>['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>$rev('course_versions',$version)];
$module=$authoring->saveModule($actor,$scope($version)+['id'=>0,'title'=>'Review module']);
$authoring->saveLesson($actor,$scope($version)+['id'=>0,'module_id'=>$module,'title'=>'Review lesson','kind'=>'text','required'=>'1','body'=>'<p>Review lesson body.</p>']);
$checks=0;
$expect=static function(bool $ok,string $message)use(&$checks):void{if(!$ok)throw new RuntimeException($message);$checks++;};
$activeVersion=$version;
$choice=static function(string $action,int $state)use($publication,$actor,$course,&$activeVersion):object {
    foreach($publication->transitions($actor,$course,$activeVersion) as $transition){$options=new Registry($transition->options);if($options->get('jlms_action')===$action&&(int)$options->get('publishing')===$state)return $transition;}
    throw new RuntimeException('Missing workflow choice '.$action.'/'.$state);
};
$transition=static function(object $choice,int $version,?string $impact=null)use($publication,$actor,$course,$rev):void {
    $publication->transition($actor,$course,$version,$rev('courses',$course),$rev('course_versions',$version),(int)$choice->id,$impact);
};
$stage=static function()use($db,$workflow,$course):string {$association=$workflow->getAssociation($course);if(!$association)return '';return (string)$db->setQuery('SELECT title FROM #__workflow_stages WHERE id='.(int)$association->stage_id)->loadResult();};
$review=$choice('review',0);
$transition($review,$version);
$row=$db->setQuery('SELECT state,current_version_id FROM #__jlms_courses WHERE id='.$course)->loadObject();
$expect((int)$row->state===0&&$row->current_version_id===null&&$stage()==='COM_JLMS_IN_REVIEW','new draft enters native review without becoming public');
$expect($db->setQuery('SELECT status FROM #__jlms_course_versions WHERE id='.$version)->loadResult()==='draft','review does not freeze the candidate');
try{$authoring->saveModule($actor,$scope($version)+['id'=>0,'title'=>'Should be locked']);throw new RuntimeException('Reviewer state allowed edits');}
catch(DomainException $e){$expect($e->getMessage()==='COM_JLMS_VERSION_IN_REVIEW','review state locks draft edits at the service boundary');}
$return=$choice('return',0);$transition($return,$version);
$expect($stage()==='COM_JLMS_DRAFT','reviewer can return an initial draft for changes');
$transition($choice('review',0),$version);
$transition($choice('publish',1),$version);
$current=(int)$db->setQuery('SELECT current_version_id FROM #__jlms_courses WHERE id='.$course)->loadResult();
$expect($current===$version&&$stage()==='JPUBLISHED','review approval publishes the validated initial version');

$draft=$authoring->createDraft($actor,$course,$rev('courses',$course));
$activeVersion=$draft;
$draftScope=$scope($draft);
$draftModule=$authoring->saveModule($actor,$draftScope+['id'=>0,'title'=>'Updated module']);
$authoring->saveLesson($actor,$scope($draft)+['id'=>0,'module_id'=>$draftModule,'title'=>'Updated lesson','kind'=>'text','required'=>'1','body'=>'<p>Updated content.</p>']);
$transition($choice('review',1),$draft);
$row=$db->setQuery('SELECT state,current_version_id FROM #__jlms_courses WHERE id='.$course)->loadObject();
$expect((int)$row->state===1&&(int)$row->current_version_id===$current&&$stage()==='COM_JLMS_IN_REVIEW','reviewing an update preserves the currently published course');
$guest=new \Joomla\CMS\User\User();
$expect((int)$component->getCatalogService()->course($guest,'en-GB',$course)->id===$course,'learners retain access to the live version during editorial review');
try{$authoring->saveModule($actor,$draftScope+['id'=>0,'title'=>'Should stay locked']);throw new RuntimeException('Update review allowed edits');}
catch(DomainException $e){$expect($e->getMessage()==='COM_JLMS_VERSION_IN_REVIEW','update draft remains locked while under review');}
$transition($choice('return',1),$draft);
$expect($stage()==='JPUBLISHED'&&(int)$db->setQuery('SELECT current_version_id FROM #__jlms_courses WHERE id='.$course)->loadResult()===$current,
    'returning an update preserves the live version and published course state');
$finalModule=$authoring->saveModule($actor,$scope($draft)+['id'=>0,'title'=>'Final update module']);
$authoring->saveLesson($actor,$scope($draft)+['id'=>0,'module_id'=>$finalModule,'title'=>'Final update lesson','kind'=>'text','required'=>'1','body'=>'<p>Final update content.</p>']);
$transition($choice('review',1),$draft);
$approval=$choice('publish',1);$impact=$publication->updateImpact($actor,$course,$draft,(int)$approval->id);
$expect(is_array($impact)&&isset($impact['fingerprint']),'approval of a revision requires an impact preview');
try{$transition($approval,$draft);throw new RuntimeException('Revision published without impact confirmation');}
catch(DomainException $e){$expect($e->getMessage()==='COM_JLMS_PUBLICATION_REVIEW_REQUIRED','stale or missing impact confirmation blocks approval');}
$transition($approval,$draft,$impact['fingerprint']);
$expect((int)$db->setQuery('SELECT current_version_id FROM #__jlms_courses WHERE id='.$course)->loadResult()===$draft
    &&$db->setQuery('SELECT status FROM #__jlms_course_versions WHERE id='.$current)->loadResult()==='published'
    &&$db->setQuery('SELECT status FROM #__jlms_course_versions WHERE id='.$draft)->loadResult()==='published',
    'approved update activates new version and preserves the assigned version history');
echo json_encode(['publication_review_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;
