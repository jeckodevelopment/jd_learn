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
    $fixtureDb->exec("UPDATE joomla_extensions SET enabled=1 WHERE type='plugin' AND folder='jlmsvideo'");
    $fixtureDb->exec("UPDATE joomla_extensions SET params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.mail_to_user',0) WHERE type='plugin' AND folder='user' AND element='joomla'");
    $fixtureDb->exec("UPDATE joomla_extensions SET params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.playback_enabled',1) WHERE type='plugin' AND folder='jlmsvideo' AND element IN ('youtube','vimeo')");
    $fixtureDb->exec("UPDATE joomla_extensions SET params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.video_external_policy','embed') WHERE type='component' AND element='com_jlms'");
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





$bank=$component->getQuestionBankService();$quizzes=$component->getQuizAuthoringService();$authoring=$component->getCurriculumService();$actor=$app->getIdentity();
$checks=0;
function quizExpect(bool $ok,string $message):void{global$checks;if(!$ok)throw new \RuntimeException($message);$checks++;}
function quizReject(callable $work,int $code,string $message):void{try{$work();}catch(\RuntimeException $error){quizExpect($error->getCode()===$code,$message.': '.$error->getMessage().' / '.$error->getCode());return;}throw new \RuntimeException($message);}
$fixturePath=dirname($root).'/quiz-authoring-fixture.json';
if(($argv[2]??'')==='retained'){
 $f=json_decode(file_get_contents($fixturePath),true,16,JSON_THROW_ON_ERROR);
 $q=$bank->editor($actor,$f['question_id']);quizExpect($q['revision']>=2,'question history survives upgrade');
 $config=$quizzes->editor($actor,$f['course_id'],$f['version_id'],$f['lesson_id']);
 quizExpect((int)$config['data']['questions'][0]['revision_id']===$f['first_revision'],'quiz keeps pinned revision after upgrade');
 quizExpect($db->setQuery('SELECT content_hash FROM #__jlms_question_revisions WHERE id='.$f['first_revision'])->loadResult()===$f['first_hash'],'original question content is immutable after upgrade');
 echo json_encode(['quiz_authoring_upgrade'=>'passed','checks'=>$checks],JSON_THROW_ON_ERROR).PHP_EOL;exit(0);
}
$categoryModel=$app->bootComponent('com_categories')->getMVCFactory()->createModel('Category','Administrator',['ignore_request'=>true]);
if(!$categoryModel->save(['id'=>0,'parent_id'=>1,'extension'=>'com_jlms.question','title'=>'CI Question bank','alias'=>'ci-questions','published'=>1,'language'=>'*','access'=>1]))throw new \RuntimeException($categoryModel->getError());
$category=(int)$categoryModel->getState('category.id');
$question=['id'=>0,'revision'=>1,'catid'=>$category,'language'=>'*','state'=>'1','kind'=>'single','prompt'=>'<p>PRIVATE_QUIZ_PROMPT_V1</p>',
 'options'=>[['option_id'=>'first','label'=>'First answer','correct'=>'1'],['option_id'=>'second','label'=>'Second answer','correct'=>'0']],'default_points'=>'3','explanation'=>'<p>PRIVATE_CORRECT_EXPLANATION</p>'];
$id=$bank->save($actor,$question);$edit=$bank->editor($actor,$id);
quizExpect($edit['revision']===1&&$edit['options'][0]['correct']==='1','real question saved with private answer key');
$record=$db->setQuery('SELECT * FROM #__jlms_questions WHERE id='.$id)->loadObject();$firstRevision=(int)$record->current_revision_id;
$firstHash=$db->setQuery('SELECT content_hash FROM #__jlms_question_revisions WHERE id='.$firstRevision)->loadResult();
$firstContent=$db->setQuery('SELECT * FROM #__jlms_question_revisions WHERE id='.$firstRevision)->loadObject();
quizExpect(str_contains($firstContent->prompt,'PRIVATE_QUIZ_PROMPT_V1'),'core-filtered question text was persisted');
$parent=(int)$db->setQuery('SELECT parent_id FROM #__assets WHERE id='.(int)$record->asset_id)->loadResult();
quizExpect($parent===(int)$db->setQuery('SELECT asset_id FROM #__categories WHERE id='.$category)->loadResult(),'native question asset inherits its category');
$bank->lock($actor,$id,false);quizExpect((int)$db->setQuery('SELECT checked_out FROM #__jlms_questions WHERE id='.$id)->loadResult()===(int)$actor->id,'native checkout stored');
$bank->lock($actor,$id,true);quizExpect(!(int)$db->setQuery('SELECT checked_out FROM #__jlms_questions WHERE id='.$id)->loadResult(),'native checkin stored');
$question['id']=$id;$question['prompt']='<p>PRIVATE_QUIZ_PROMPT_V2</p>';$bank->save($actor,$question);
quizExpect($bank->editor($actor,$id)['revision']===2,'editing creates a new revision');
quizExpect(json_encode($db->setQuery('SELECT * FROM #__jlms_question_revisions WHERE id='.$firstRevision)->loadObject(),JSON_THROW_ON_ERROR)===json_encode($firstContent,JSON_THROW_ON_ERROR),'entire old revision remains byte-for-byte unchanged after native HTML filtering');
$currentDefinition=$bank->editor($actor,$id);$copyId=$bank->duplicate($actor,$id);$copy=$bank->editor($actor,$copyId);
quizExpect($copyId!==$id&&$copy['revision']===1&&$copy['state']===0&&$copy['prompt']===$currentDefinition['prompt'],'question duplication creates a separate unpublished draft from the current definition');
quizExpect($copy['options'][0]['option_id']!==$currentDefinition['options'][0]['option_id'],'duplicated question receives fresh answer option identities');
$bank->delete($actor,$copyId);
quizExpect(!(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_questions WHERE id='.$copyId)->loadResult()&&!(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_question_revisions WHERE question_id='.$copyId)->loadResult(),'unused question and revisions delete atomically');
$list=$bank->adminListing($actor,['search'=>'id:'.$id,'published'=>'1'],0,20,'q.id','DESC');
quizExpect($list['total']===1&&(int)$list['items'][0]->id===$id,'question administration list searches by ID and filters state');
$filtered=$bank->adminListing($actor,['catid'=>(string)$category,'published'=>'1','language'=>'*','search'=>'id:'.$id],0,20,'q.id','DESC');
quizExpect($filtered['total']===1&&(int)$filtered['items'][0]->id===$id,'question bank combines category, state, language and search filters');
$emptyFilter=$bank->adminListing($actor,['catid'=>(string)$category,'published'=>'0','language'=>'*'],0,20,'q.id','DESC');
quizExpect($emptyFilter['total']===0,'question bank state filter excludes published questions when drafts are selected');
quizReject(fn()=>$bank->save($actor,$question),409,'stale question revision rejected');
quizReject(fn()=>$bank->save(new \Joomla\CMS\User\User(),$question),401,'guest bank mutation rejected');
$otherCategory=(int)$db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
quizReject(fn()=>$bank->save($actor,array_replace($question,['catid'=>$otherCategory,'revision'=>2])),422,'course category cannot be used as a bank category');
$multiple=$question;$multiple['id']=0;$multiple['revision']=1;$multiple['kind']='multiple';$multiple['options'][1]['correct']='1';$otherQuestion=$bank->save($actor,$multiple);
$otherRevision=(int)$db->setQuery('SELECT current_revision_id FROM #__jlms_questions WHERE id='.$otherQuestion)->loadResult();
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
if(!$model->save(['id'=>0,'revision'=>1,'title'=>'Quiz draft fixture','alias'=>'quiz-draft-fixture','catid'=>$otherCategory,'language'=>'*','catalog_access'=>1,'content_access'=>2,'enrollment_mode'=>'auto','presentation_description'=>'<p>Course presentation for the publication fixture.</p>', 'summary'=>'Draft quiz fixture']))throw new \RuntimeException($model->getError());
$course=(int)$model->getState('course.id');$version=$authoring->createDraft($actor,$course,1);
$scope=static fn():array=>['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$version)->loadResult()];
$module=$authoring->saveModule($actor,$scope()+['id'=>0,'title'=>'Quiz chapter']);
$input=$scope()+['lesson_id'=>0,'revision'=>1,'module_id'=>$module,'title'=>'Quiz draft','required'=>'1','assessment'=>'graded','threshold'=>'70.25','max_attempts'=>'3','time_minutes'=>'15','retry_minutes'=>'2',
 'selection_mode'=>'fixed','shuffle_questions'=>'0','shuffle_options'=>'1','feedback_policy'=>'pass_or_exhausted','questions'=>[['revision_id'=>$firstRevision,'points'=>'3'],['revision_id'=>$otherRevision,'points'=>'2']]];
$lesson=$quizzes->save($actor,$input);$config=$quizzes->editor($actor,$course,$version,$lesson);
quizReject(fn()=>$bank->delete($actor,$id),409,'question used in quiz cannot be deleted');
quizExpect($config['data']['threshold']==='70.25'&&$config['data']['time_minutes']===15,'exact basis-point threshold and time configuration');
quizExpect((int)$config['data']['questions'][0]['revision_id']===$firstRevision,'explicit older immutable revision remains pinned');
quizExpect(!str_contains(json_encode($config,JSON_THROW_ON_ERROR),'PRIVATE_CORRECT_EXPLANATION')&&!str_contains(json_encode($config,JSON_THROW_ON_ERROR),'"correct"'),'quiz editor DTO contains no answer key');
quizReject(fn()=>$quizzes->save($actor,$input),409,'stale aggregate revision rejected');
$invalid=array_replace($input,$scope(),['lesson_id'=>$lesson,'revision'=>1,'selection_mode'=>'random','draw_count'=>'3']);
quizReject(fn()=>$quizzes->save($actor,$invalid),400,'oversized extraction rejected');
quizReject(fn()=>$quizzes->save($actor,array_replace($invalid,['draw_count'=>'1','questions'=>[['revision_id'=>$firstRevision,'points'=>1],['revision_id'=>$firstRevision,'points'=>2]]])),422,'duplicate pool revision rejected');
quizReject(fn()=>$quizzes->save($actor,array_replace($input,$scope(),['lesson_id'=>$lesson,'revision'=>1,'feedback_policy'=>'after_submit'])),422,'graded tests cannot enable immediate answer feedback');
// Native asset rules can deny a single question while leaving course authoring available.
$group=$app->bootComponent('com_users')->getMVCFactory()->createModel('Group','Administrator',['ignore_request'=>true]);
if(!$group->save(['id'=>0,'parent_id'=>2,'title'=>'CI Quiz author']))throw new \RuntimeException($group->getError());$groupId=(int)$group->getState('group.id');
$rules=json_decode($db->setQuery("SELECT rules FROM #__assets WHERE name='com_jlms'")->loadResult(),true);
foreach(['core.manage','core.create','core.edit','core.edit.state','jlms.quiz.manage']as$action)$rules[$action][(string)$groupId]=true;
$asset=new \Joomla\CMS\Table\Asset($db);$asset->loadByName('com_jlms');$asset->rules=json_encode($rules,JSON_THROW_ON_ERROR);if(!$asset->store())throw new \RuntimeException($asset->getError());
$user=new \Joomla\CMS\User\User();$userData=['name'=>'CI Quiz Author','username'=>'ciquizauthor','email'=>'ciquizauthor@example.invalid','password'=>'Ci-only-Learner-Password-2026','password2'=>'Ci-only-Learner-Password-2026','groups'=>[2,$groupId],'block'=>0,'activation'=>''];
if(!$user->bind($userData)||!$user->save())throw new \RuntimeException($user->getError());$editor=new \Joomla\CMS\User\User((int)$user->id);
$asset->load((int)$record->asset_id);$asset->rules=json_encode(['jlms.quiz.manage'=>[(string)$groupId=>false]],JSON_THROW_ON_ERROR);$asset->store();
quizReject(fn()=>$bank->editor($editor,$id),403,'question ACL overrides component permission');
quizReject(fn()=>$quizzes->save($editor,array_replace($input,$scope(),['lesson_id'=>$lesson,'revision'=>1])),403,'course permission cannot borrow a denied bank question');
$asset->rules='{}';$asset->store();
$bank->lock($actor,$id,false);quizReject(fn()=>$bank->save($editor,array_replace($question,['revision'=>2])),409,'another native checkout blocks save');$bank->lock($actor,$id,true);
$publication=$component->getPublicationService();$blocked=false;
$quizId=(int)$db->setQuery('SELECT id FROM #__jlms_quizzes WHERE lesson_id='.$lesson)->loadResult();
$selectionBefore=$db->setQuery('SELECT selection_json FROM #__jlms_quizzes WHERE id='.$quizId)->loadResult();
$db->setQuery("UPDATE #__jlms_quizzes SET selection_json=JSON_SET(selection_json,'$._schema',99) WHERE id=".$quizId)->execute();
try{$publication->transition($actor,$course,$version,(int)$db->setQuery('SELECT revision FROM #__jlms_courses WHERE id='.$course)->loadResult(),$scope()['expected_version_revision'],(int)$publication->transitions($actor,$course)[0]->id);}catch(\RuntimeException $error){$blocked=$error->getMessage()==='COM_JLMS_QUIZ_INVALID';}
$db->setQuery('UPDATE #__jlms_quizzes SET selection_json='.$db->quote($selectionBefore).' WHERE id='.$quizId)->execute();
quizExpect($blocked&&$db->setQuery('SELECT status FROM #__jlms_course_versions WHERE id='.$version)->loadResult()==='draft','invalid quiz publication fails atomically');
// Dedicated draft removal removes configuration and links, preserving immutable bank revisions.
$temporary=$quizzes->save($actor,array_replace($input,$scope(),['title'=>'Temporary quiz']));$quizzes->delete($actor,$course,$version,$temporary,$scope()['expected_version_revision']);
quizExpect(!(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_lessons WHERE id='.$temporary)->loadResult(),'dedicated draft quiz removal');
quizExpect($db->setQuery('SELECT content_hash FROM #__jlms_question_revisions WHERE id='.$firstRevision)->loadResult()===$firstHash,'pool changes never mutate question revisions');
file_put_contents($fixturePath,json_encode(['question_id'=>$id,'first_revision'=>$firstRevision,'first_hash'=>$firstHash,'other_revision'=>$otherRevision,'category_id'=>$category,'course_id'=>$course,'version_id'=>$version,'module_id'=>$module,'lesson_id'=>$lesson,'author_id'=>(int)$editor->id],JSON_THROW_ON_ERROR));
echo json_encode(['quiz_authoring_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;
