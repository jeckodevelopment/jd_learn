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



use Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess;
$checks = 0;
function copyExpect(bool $ok, string $message): void { global $checks; if (!$ok) { throw new RuntimeException($message); } $checks++; }
function copyDenied(callable $work, string $message): void {
    try { $work(); } catch (Throwable $e) { copyExpect(true, $message); return; }
    throw new RuntimeException($message);
}
$course = (int) $db->setQuery("SELECT id FROM #__jlms_courses WHERE alias='quiz-runtime-fixture'")->loadResult();
$source = $db->setQuery('SELECT * FROM #__jlms_courses WHERE id=' . $course)->loadObject();
$version = $db->setQuery('SELECT * FROM #__jlms_course_versions WHERE id=' . (int) $source->current_version_id)->loadObject();
$identityRows = static fn(): array => [
    $db->setQuery('SELECT * FROM #__jlms_enrollments ORDER BY id')->loadAssocList(),
    $db->setQuery('SELECT * FROM #__jlms_attempts ORDER BY id')->loadAssocList(),
    $db->setQuery('SELECT * FROM #__jlms_lesson_progress ORDER BY enrollment_id,lesson_id')->loadAssocList(),
];
$beforeLearning = $identityRows();
$sourceLessons = $db->setQuery('SELECT * FROM #__jlms_lessons WHERE course_version_id=' . (int) $version->id . ' ORDER BY id')->loadAssocList();
PluginHelper::importPlugin('system', 'fields');
$fields = $app->bootComponent('com_fields')->getMVCFactory()->createModel('Field', 'Administrator', ['ignore_request'=>true]);
copyExpect($fields->save(['id'=>0,'context'=>'com_jlms.course','title'=>'Copy course note','name'=>'copy-course-note','type'=>'text','state'=>1,'access'=>1,'language'=>'*','group_id'=>0,'default_value'=>'','description'=>'','params'=>[],'fieldparams'=>[]]), 'Native course field fixture');
$field = (int) $fields->getState('field.id');
copyExpect($fields->setFieldValue($field, $course, 'Preserved field value'), 'Native field value fixture');
$model = $factory->createModel('Course', 'Administrator', ['ignore_request'=>true]);
$input = ['course_id'=>$course,'version_id'=>(int)$version->id,'revision'=>(int)$source->revision,'expected_version_revision'=>(int)$version->revision,'title'=>'Independent course copy'];
$copy = $model->duplicate($input);
$target = $db->setQuery('SELECT * FROM #__jlms_courses WHERE id=' . $copy)->loadObject();
$draft = $db->setQuery('SELECT * FROM #__jlms_course_versions WHERE course_id=' . $copy)->loadObject();
copyExpect($copy !== $course && (int) $target->state===0 && $target->current_version_id===null, 'Independent draft identity, not a publication or pointer update');
copyExpect($draft && $draft->status==='draft' && (int)$draft->version_no===1 && $draft->structure_hash===null && $draft->published_at===null, 'Complete editable draft starts at version one');
copyExpect($target->params_json===$source->params_json && $target->catalog_access==$source->catalog_access && $target->content_access==$source->content_access, 'Metadata and access levels retained');
$asset=$db->setQuery('SELECT * FROM #__assets WHERE id='.(int)$target->asset_id)->loadObject();
copyExpect($asset->name==='com_jlms.course.'.$copy && $asset->rules==='{}', 'Independent native asset inherits category permissions');
copyExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__workflow_associations WHERE extension='com_jlms.course' AND item_id=".$copy)->loadResult()===1, 'Native workflow initialized on the new course');
copyExpect((string)$db->setQuery('SELECT value FROM #__fields_values WHERE field_id='.$field.' AND item_id='.$db->quote((string)$copy))->loadResult()==='Preserved field value', 'Native course field values copied');
$targetLessons=$db->setQuery('SELECT * FROM #__jlms_lessons WHERE course_version_id='.(int)$draft->id.' ORDER BY id')->loadAssocList();
copyExpect(count($sourceLessons)===count($targetLessons), 'Whole lesson outline copied');
foreach ($sourceLessons as $n=>$lesson) {
    copyExpect($lesson['id']!==$targetLessons[$n]['id'] && $lesson['logical_key']!==$targetLessons[$n]['logical_key'], 'New physical and logical lesson identity');
    copyExpect($lesson['body']===$targetLessons[$n]['body'] && $lesson['media_asset_id']===$targetLessons[$n]['media_asset_id'], 'Lesson content and media association copied');
}
copyExpect($identityRows()===$beforeLearning, 'Course duplication never copies or modifies learning data');
$outline=$authoring->outline($actor,$copy,(int)$draft->id);
$module=$outline['modules'][0];
$rev=static fn():int=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.(int)$draft->id)->loadResult();
$moduleInput=['course_id'=>$copy,'version_id'=>(int)$draft->id,'expected_version_revision'=>$rev(),'id'=>(int)$module->id,'revision'=>(int)$module->revision,'title'=>'Copied module'];
$moduleCopy=$authoring->duplicateItem($actor,'module',$moduleInput);
$oldCount=(int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_lessons WHERE module_id='.(int)$module->id)->loadResult();
copyExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_lessons WHERE module_id='.$moduleCopy)->loadResult()===$oldCount, 'Module duplicates its full lesson subtree');
$lesson=$db->setQuery('SELECT * FROM #__jlms_lessons WHERE module_id='.(int)$module->id.' ORDER BY id')->loadObject();
$lessonInput=['course_id'=>$copy,'version_id'=>(int)$draft->id,'expected_version_revision'=>$rev(),'id'=>(int)$lesson->id,'revision'=>(int)$lesson->revision,'module_id'=>$moduleCopy,'title'=>'Copied lesson in selected module'];
$lessonCopy=$authoring->duplicateItem($actor,'lesson',$lessonInput);
copyExpect((int)$db->setQuery('SELECT module_id FROM #__jlms_lessons WHERE id='.$lessonCopy)->loadResult()===$moduleCopy, 'Lesson copy uses the explicitly selected draft module');
if ($lesson->kind==='quiz') {
 $originalQuiz=$db->setQuery('SELECT id FROM #__jlms_quizzes WHERE lesson_id='.(int)$lesson->id)->loadResult();
 $newQuiz=$db->setQuery('SELECT * FROM #__jlms_quizzes WHERE lesson_id='.$lessonCopy)->loadObject();
 copyExpect($newQuiz && (int)$newQuiz->id!==(int)$originalQuiz && !isset(json_decode($newQuiz->selection_json,true)['published_pool_hash']), 'Quiz configuration is independent and unfrozen');
 copyExpect($db->setQuery('SELECT question_revision_id,ordering,points FROM #__jlms_quiz_questions WHERE quiz_id='.(int)$originalQuiz.' ORDER BY ordering')->loadAssocList()===$db->setQuery('SELECT question_revision_id,ordering,points FROM #__jlms_quiz_questions WHERE quiz_id='.(int)$newQuiz->id.' ORDER BY ordering')->loadAssocList(), 'Immutable question revision links retained');
}
copyDenied(fn()=>$authoring->duplicateItem($actor,'lesson',$lessonInput), 'Stale draft request rejected');
$bad=array_replace($lessonInput,['expected_version_revision'=>$rev(),'module_id'=>(int)$sourceLessons[0]['module_id']]);
copyDenied(fn()=>$authoring->duplicateItem($actor,'lesson',$bad), 'Foreign destination parent rejected');
$bad=array_replace($lessonInput,['version_id'=>(int)$version->id,'course_id'=>$course,'expected_version_revision'=>(int)$version->revision]);
copyDenied(fn()=>$authoring->duplicateItem($actor,'lesson',$bad), 'Published curriculum is immutable');

// Fresh ACL and checkout are enforced on actual source records.
$editorId=(int)$db->setQuery("SELECT id FROM #__users WHERE username='cieditor'")->loadResult();
$locked=(object)['id'=>$copy,'checked_out'=>$editorId];$db->updateObject('#__jlms_courses',$locked,'id');
copyDenied(fn()=>$authoring->duplicateItem($actor,'lesson',array_replace($lessonInput,['expected_version_revision'=>$rev()])), 'Other user checkout blocks copying');
$update=(object)['id'=>$copy,'checked_out'=>null];$db->updateObject('#__jlms_courses',$update,'id',true);
$sourceAsset=new \Joomla\CMS\Table\Asset($db);$sourceAsset->load((int)$source->asset_id);
$originalRules=$sourceAsset->rules;$rules=json_decode($originalRules,true)?:[];
$editorGroup=(int)$db->setQuery('SELECT group_id FROM #__user_usergroup_map WHERE user_id='.$editorId.' ORDER BY group_id DESC')->loadResult();
$rules['core.edit'][(string)$editorGroup]=0;$rules['core.edit.own'][(string)$editorGroup]=0;
$update=(object)['id'=>(int)$sourceAsset->id,'rules'=>json_encode($rules)];$db->updateObject('#__assets',$update,'id');
\Joomla\CMS\Access\Access::clearStatics();
$restricted=$factory->createModel('Course','Administrator',['ignore_request'=>true]);$restricted->setCurrentUser(new \Joomla\CMS\User\User($editorId));
copyDenied(fn()=>$restricted->duplicate($input),'Source edit deny is respected by the model');
$update=(object)['id'=>(int)$sourceAsset->id,'rules'=>$originalRules];$db->updateObject('#__assets',$update,'id');\Joomla\CMS\Access\Access::clearStatics();
$counts=static fn():array=>array_map(static fn(string $table):int=>(int)$db->setQuery('SELECT COUNT(*) FROM #__'.$table)->loadResult(),['jlms_courses','jlms_course_versions','jlms_modules','jlms_lessons','jlms_quizzes','assets','history','workflow_associations','fields_values']);
$before=$counts();
copyDenied(fn()=>$model->duplicate(array_replace($input,['revision'=>(int)$source->revision+1])), 'Stale source course rejected');
copyExpect($counts()===$before,'Failed copy leaves no draft, native asset, workflow, history or fields');
// Inject a late copy failure after the native course record has been created; all writes must roll back.
$quiz=$db->setQuery('SELECT * FROM #__jlms_quizzes WHERE course_version_id='.(int)$version->id.' ORDER BY id')->loadObject();
$badQuiz=(object)['id'=>(int)$quiz->id,'selection_json'=>'{'];$db->updateObject('#__jlms_quizzes',$badQuiz,'id');
copyDenied(fn()=>$model->duplicate(array_replace($input,['title'=>'Must roll back'])), 'Late copy failure is rejected');
$update=(object)['id'=>(int)$quiz->id,'selection_json'=>$quiz->selection_json];$db->updateObject('#__jlms_quizzes',$update,'id');
copyExpect($counts()===$before,'Late failure rolls back the complete course and removes its reserved native asset');
copyExpect($db->setQuery('SELECT * FROM #__jlms_courses WHERE id='.$course)->loadObject()==$source,'Source course unchanged');
copyExpect($db->setQuery('SELECT * FROM #__jlms_lessons WHERE course_version_id='.(int)$version->id.' ORDER BY id')->loadAssocList()===$sourceLessons,'Source published lessons unchanged');
copyExpect($identityRows()===$beforeLearning,'All duplication and denial paths preserve learning records');

foreach (['html5-fixture','materials-fixture'] as $alias) {
 $q=$db->createQuery()->select('*')->from('#__jlms_courses')->where('alias=:alias')->bind(':alias',$alias);$mediaSource=$db->setQuery($q)->loadObject();
 copyExpect($mediaSource && $mediaSource->current_version_id,'Media fixture exists and is published');
 $mediaVersion=$db->setQuery('SELECT * FROM #__jlms_course_versions WHERE id='.(int)$mediaSource->current_version_id)->loadObject();
 $mediaModel=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
 $new=$mediaModel->duplicate(['course_id'=>(int)$mediaSource->id,'version_id'=>(int)$mediaVersion->id,'revision'=>(int)$mediaSource->revision,'expected_version_revision'=>(int)$mediaVersion->revision,'title'=>'Copied '.$alias]);
 $newVersion=(int)$db->setQuery('SELECT id FROM #__jlms_course_versions WHERE course_id='.$new)->loadResult();
 copyExpect($db->setQuery('SELECT asset_id FROM #__jlms_attachments WHERE course_version_id='.(int)$mediaVersion->id.' ORDER BY id')->loadColumn()===$db->setQuery('SELECT asset_id FROM #__jlms_attachments WHERE course_version_id='.$newVersion.' ORDER BY id')->loadColumn(),'Copied attachments reuse immutable assets with new links');
 copyExpect($db->setQuery('SELECT media_asset_id FROM #__jlms_lessons WHERE course_version_id='.(int)$mediaVersion->id.' ORDER BY id')->loadColumn()===$db->setQuery('SELECT media_asset_id FROM #__jlms_lessons WHERE course_version_id='.$newVersion.' ORDER BY id')->loadColumn(),'Copied local video association retains the source asset');
}
file_put_contents(dirname($root).'/duplication-fixture.json',json_encode(['course'=>$copy,'version'=>(int)$draft->id,'source'=>$course,'source_version'=>(int)$version->id],JSON_THROW_ON_ERROR));
echo json_encode(['duplication_checks'=>$checks,'status'=>'passed']).PHP_EOL;
