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
    $fixtureDb->exec("UPDATE joomla_extensions SET enabled=1 WHERE type='plugin' AND (folder='jlmsvideo' OR (folder='task' AND element='jlms'))");
    $fixtureDb->exec("UPDATE joomla_extensions SET params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.mail_to_user',0) WHERE type='plugin' AND folder='user' AND element='joomla'");
    $fixtureDb->exec("UPDATE joomla_extensions SET params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.playback_enabled',1) WHERE type='plugin' AND folder='jlmsvideo' AND element IN ('youtube','vimeo')");
    $fixtureDb->exec("UPDATE joomla_extensions SET params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.video_external_policy','click') WHERE type='component' AND element='com_jlms'");
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




// Executed against the installed previous package before installing the current package.
$actor=$app->getIdentity();$app->getSession()->set('user',$actor);$bank=$component->getQuestionBankService();$author=$component->getCurriculumService();$quizzes=$component->getQuizAuthoringService();
$categories=$app->bootComponent('com_categories')->getMVCFactory()->createModel('Category','Administrator',['ignore_request'=>true]);
if(!$categories->save(['id'=>0,'parent_id'=>1,'extension'=>'com_jlms.question','title'=>'Legacy quiz bank','alias'=>'legacy-time-bank','published'=>1,'language'=>'*','access'=>1]))throw new \RuntimeException($categories->getError());
$question=$bank->save($actor,['id'=>0,'revision'=>1,'catid'=>(int)$categories->getState('category.id'),'language'=>'*','state'=>'1','kind'=>'single','prompt'=>'Legacy question','options'=>[['option_id'=>'first','label'=>'Correct','correct'=>'1'],['option_id'=>'second','label'=>'Other','correct'=>'0']],'default_points'=>'1','explanation'=>'Legacy feedback']);
$qr=(int)$db->setQuery('SELECT current_revision_id FROM #__jlms_questions WHERE id='.$question)->loadResult();
$catid=(int)$db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
if(!$model->save(['id'=>0,'revision'=>1,'title'=>'Legacy time course','alias'=>'legacy-time-course','catid'=>$catid,'language'=>'*','catalog_access'=>1,'content_access'=>2,'enrollment_mode'=>'auto','summary'=>'Pre-upgrade fixture','presentation_description'=>'<p>Pre-upgrade 0.21 presentation</p>','presentation_objectives'=>'Existing objective']))throw new \RuntimeException($model->getError());
$course=(int)$model->getState('course.id');$version=$author->createDraft($actor,$course,1);
$scope=static fn():array=>['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$version)->loadResult()];
$module=$author->saveModule($actor,$scope()+['id'=>0,'title'=>'Legacy module']);
$lesson=$quizzes->save($actor,$scope()+['lesson_id'=>0,'revision'=>1,'module_id'=>$module,'title'=>'Legacy timed quiz','required'=>'1','assessment'=>'graded','threshold'=>'70','max_attempts'=>'3','time_minutes'=>'10','retry_minutes'=>'0','selection_mode'=>'fixed','shuffle_questions'=>'0','shuffle_options'=>'0','feedback_policy'=>'pass_or_exhausted','questions'=>[['revision_id'=>$qr,'points'=>'1']]]);
$publication=$component->getPublicationService();$publication->transition($actor,$course,$version,(int)$db->setQuery('SELECT revision FROM #__jlms_courses WHERE id='.$course)->loadResult(),$scope()['expected_version_revision'],(int)$publication->transitions($actor,$course)[0]->id);
$user=new \Joomla\CMS\User\User();$data=['name'=>'CI Legacy time learner','username'=>'cilegacytime','email'=>'cilegacytime@example.invalid','password'=>'Ci-only-Learner-Password-2026','password2'=>'Ci-only-Learner-Password-2026','groups'=>[2],'block'=>0,'activation'=>''];
if(!$user->bind($data)||!$user->save())throw new \RuntimeException($user->getError());$user=new \Joomla\CMS\User\User((int)$user->id);
$enrollment=$component->getLearningService()->start($user,'en-GB',$course,bin2hex(random_bytes(16)));
$key=static function():string{$s=bin2hex(random_bytes(16));return substr($s,0,8).'-'.substr($s,8,4).'-4'.substr($s,13,3).'-a'.substr($s,17,3).'-'.substr($s,20);};
$runtime=$component->getQuizAttemptService();$a=$runtime->start($user,'en-GB',$enrollment,$lesson,$key());
$runtime->mutate($user,'en-GB',$enrollment,$lesson,$a['attempt']['id'],1,$key(),json_encode([$a['questions'][0]['id']=>['first']],JSON_THROW_ON_ERROR),true);
$a=$runtime->start($user,'en-GB',$enrollment,$lesson,$key());$snapshot=$db->setQuery('SELECT rules_snapshot_json,deadline_at FROM #__jlms_attempts WHERE id='.(int)$a['attempt']['id'])->loadObject();
if((json_decode($snapshot->rules_snapshot_json,true,16,JSON_THROW_ON_ERROR)['extra_time_seconds']??0)!==0)throw new \RuntimeException('Unexpected allowance in legacy fixture');
file_put_contents(dirname($root).'/legacy-time-fixture.json',json_encode(['user_id'=>(int)$user->id,'enrollment_id'=>$enrollment,'lesson_id'=>$lesson,'attempt_id'=>$a['attempt']['id'],'snapshot_hash'=>hash('sha256',json_encode($snapshot,JSON_THROW_ON_ERROR)), 'course_id'=>$course, 'version_id'=>$version, 'presentation_before_upgrade'=>$db->setQuery('SELECT presentation_json,structure_hash,published_at FROM #__jlms_course_versions WHERE id='.$version)->loadAssoc()],JSON_THROW_ON_ERROR));
echo json_encode(['pre_upgrade_quiz_snapshots'=>'created_before_upgrade']).PHP_EOL;
