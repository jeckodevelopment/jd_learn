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


$service=$component->getPublicationService(); $authoring=$component->getCurriculumService(); $actor=$app->getIdentity();
$checks=0;
function impactExpect(bool $ok,string $message): void { global $checks; if (!$ok) { throw new RuntimeException($message); } $checks++; }
function impactDenied(callable $work,string $message): void { try { $work(); } catch (\DomainException $e) { impactExpect($e->getMessage()===$message,'Expected '.$message.', got '.$e->getMessage()); return; } throw new RuntimeException('Expected rejection: '.$message); }
function impactRow(string $table,int $id): object { global $db; return $db->setQuery('SELECT * FROM #__jlms_'.$table.' WHERE id='.$id)->loadObject(); }
function impactLearning(): array { global $db; return [$db->setQuery('SELECT * FROM #__jlms_enrollments ORDER BY id')->loadAssocList(),$db->setQuery('SELECT * FROM #__jlms_attempts ORDER BY id')->loadAssocList(),$db->setQuery('SELECT * FROM #__jlms_lesson_progress ORDER BY enrollment_id,lesson_id')->loadAssocList()]; }
$mode=$argv[2]??'';$fixture=dirname($root).'/publication-impact-fixture.json';
if ($mode==='state') {
 $f=json_decode(file_get_contents($fixture),true,32,JSON_THROW_ON_ERROR);
 echo json_encode(['course'=>(array)impactRow('courses',$f['course']),'version'=>(array)impactRow('course_versions',$f['version']),'learning'=>impactLearning()],JSON_THROW_ON_ERROR);return;
}
$source=(int)$db->setQuery("SELECT id FROM #__jlms_courses WHERE alias='quiz-runtime-fixture'")->loadResult();$record=impactRow('courses',$source);$original=impactRow('course_versions',(int)$record->current_version_id);
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
$course=$model->duplicate(['course_id'=>$source,'version_id'=>$original->id,'revision'=>$record->revision,'expected_version_revision'=>$original->revision,'title'=>'Publication impact fixture']);
// Keep this independent fixture publicly accessible. The copied source deliberately uses
// a restricted test level which does not include the administrator as a learner.
$copyRecord=$model->getItem($course);
impactExpect($model->save(['id'=>$course,'revision'=>$copyRecord->revision,'catalog_access'=>1,'content_access'=>1,'enrollment_mode'=>'auto']),'Independent impact fixture has explicit learner access');
$version=(int)$authoring->outline($actor,$course)['version']->id;
$transition=(int)$service->transitions($actor,$course,$version)[0]->id;
impactExpect($service->updateImpact($actor,$course,$version,$transition)===null,'Initial publication has no update review');
$service->transition($actor,$course,$version,(int)impactRow('courses',$course)->revision,(int)impactRow('course_versions',$version)->revision,$transition);
$old=$version;
$enrollment=$component->getLearningService()->start($actor,'en-GB',$course,str_repeat('e',40));
impactExpect((int)impactRow('enrollments',$enrollment)->course_version_id===$old,'Real enrollment assigned to the original programme');
$before=impactLearning();
$draft=$authoring->createDraft($actor,$course,(int)impactRow('courses',$course)->revision);
$findTransition=static function(string $action)use($service,$actor,$course,$draft):object {
    foreach($service->transitions($actor,$course,$draft) as $candidate) {
        if((new \Joomla\Registry\Registry($candidate->options))->get('jlms_action')===$action)return $candidate;
    }
    throw new RuntimeException('Missing editorial transition '.$action);
};
$submit=$findTransition('review');
$service->transition($actor,$course,$draft,(int)impactRow('courses',$course)->revision,(int)impactRow('course_versions',$draft)->revision,(int)$submit->id);
$transition=(int)$findTransition('publish')->id;
$review=static fn(): array => $service->updateImpact($actor,$course,$draft,$transition);
$initial=$review();
foreach (['modules','lessons','quizzes','materials'] as $kind) { impactExpect($initial['changes'][$kind]['added']===0&&$initial['changes'][$kind]['changed']===0&&$initial['changes'][$kind]['removed']===0,'Unchanged working copy has no false changes for '.$kind); }
impactExpect(!$initial['rules_changed'],'Equivalent teaching rules remain unchanged');
impactExpect((int)$initial['enrollments'][0]['total']===1,'Review counts existing assignments without learner identities');
impactDenied(fn()=>$service->transition($actor,$course,$draft,(int)impactRow('courses',$course)->revision,(int)impactRow('course_versions',$draft)->revision,$transition),'COM_JLMS_PUBLICATION_REVIEW_REQUIRED');
$return=$findTransition('return');
$service->transition($actor,$course,$draft,(int)impactRow('courses',$course)->revision,(int)impactRow('course_versions',$draft)->revision,(int)$return->id);
$scope=static fn(): array => ['course_id'=>$course,'version_id'=>$draft,'expected_version_revision'=>impactRow('course_versions',$draft)->revision];
$module=$authoring->saveModule($actor,$scope()+['id'=>0,'title'=>'New impact module']);
$lesson=$authoring->saveLesson($actor,$scope()+['id'=>0,'module_id'=>$module,'title'=>'New impact lesson','kind'=>'text','required'=>'0','body'=>'<p>Publication review content</p>']);
$existingModule=$authoring->outline($actor,$course,$draft)['modules'][0];
$authoring->saveModule($actor,$scope()+['id'=>$existingModule->id,'revision'=>$existingModule->revision,'title'=>'Renamed existing module']);
$quiz=$db->setQuery('SELECT * FROM #__jlms_quizzes WHERE course_version_id='.$draft.' ORDER BY id')->loadObject();
$quizChange=(object)['id'=>(int)$quiz->id,'pass_basis_points'=>6500];$db->updateObject('#__jlms_quizzes',$quizChange,'id');
$versionChange=(object)['id'=>$draft,'revision'=>(int)impactRow('course_versions',$draft)->revision+1];$db->updateObject('#__jlms_course_versions',$versionChange,'id');
$submit=$findTransition('review');
$service->transition($actor,$course,$draft,(int)impactRow('courses',$course)->revision,(int)impactRow('course_versions',$draft)->revision,(int)$submit->id);
$transition=(int)$findTransition('publish')->id;
$updated=$review();
impactExpect($updated['changes']['modules']['changed']===1&&$updated['changes']['quizzes']['changed']===1,'Existing module and quiz definition changes are recognised without exposing questions');

impactExpect($updated['changes']['modules']['added']===1&&$updated['changes']['lessons']['added']===1,'New module and lesson classified as additions');
impactDenied(fn()=>$service->transition($actor,$course,$draft,(int)impactRow('courses',$course)->revision,(int)impactRow('course_versions',$draft)->revision,$transition,$initial['fingerprint']),'COM_JLMS_PUBLICATION_REVIEW_STALE');
impactExpect(impactRow('course_versions',$draft)->status==='draft'&&impactLearning()===$before,'Stale review causes no publication or learning mutation');
$return=$findTransition('return');
$service->transition($actor,$course,$draft,(int)impactRow('courses',$course)->revision,(int)impactRow('course_versions',$draft)->revision,(int)$return->id);
$authoring->saveLesson($actor,$scope()+['id'=>$lesson,'revision'=>impactRow('lessons',$lesson)->revision,'module_id'=>$module,'title'=>'Changed added lesson','kind'=>'text','required'=>'0','body'=>'<p>Changed body</p>']);
$submit=$findTransition('review');
$service->transition($actor,$course,$draft,(int)impactRow('courses',$course)->revision,(int)impactRow('course_versions',$draft)->revision,(int)$submit->id);
$transition=(int)$findTransition('publish')->id;
impactExpect($review()['fingerprint']!==$updated['fingerprint'],'Changed draft content invalidates the review');
$guest=new \Joomla\CMS\User\User(0);
impactDenied(fn()=>$service->updateImpact($guest,$course,$draft,$transition),'JERROR_ALERTNOAUTHOR');
impactDenied(fn()=>$service->updateImpact($actor,$course,(int)$original->id,$transition),'COM_JLMS_RESOURCE_NOT_FOUND');
$current=$review();$service->transition($actor,$course,$draft,(int)impactRow('courses',$course)->revision,(int)impactRow('course_versions',$draft)->revision,$transition,$current['fingerprint']);
impactExpect((int)impactRow('courses',$course)->current_version_id===$draft,'Confirmed current review activates the update');
impactExpect(impactLearning()===$before&&(int)impactRow('enrollments',$enrollment)->course_version_id===$old,'Existing assignments, attempts and progress remain byte-for-byte unchanged');
$version=$authoring->createDraft($actor,$course,(int)impactRow('courses',$course)->revision);
$outline=$authoring->outline($actor,$course,$version);$module=(int)$outline['modules'][0]->id;
$lesson=$authoring->saveLesson($actor,['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>$outline['version']->revision,'module_id'=>$module,'id'=>0,'title'=>'HTTP impact <script>safe</script>','kind'=>'text','required'=>'0','body'=>'<p>HTTP review body</p>']);
file_put_contents($fixture,json_encode(['course'=>$course,'version'=>$version,'module'=>$module,'lesson'=>$lesson,'source_version'=>$draft],JSON_THROW_ON_ERROR));
echo json_encode(['publication_impact_native_checks'=>$checks,'status'=>'passed']).PHP_EOL;
