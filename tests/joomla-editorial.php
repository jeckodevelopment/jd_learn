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


use Jlms\Component\Jlms\Administrator\Application\TeacherProfiles;
$checks = 0;
function editorialExpect(bool $condition, string $message): void {
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    $checks++;
}
$authoring = $component->getCurriculumService();
$publication = $component->getPublicationService();
$actor = $app->getIdentity();
$factory = $component->getMVCFactory();
$course = (int) $db->setQuery("SELECT id FROM #__jlms_courses WHERE alias='quiz-runtime-fixture'")->loadResult();
$source = $db->setQuery('SELECT * FROM #__jlms_courses WHERE id=' . $course)->loadObject();
$oldVersion = (int) $source->current_version_id;
$oldRows = $db->setQuery('SELECT * FROM #__jlms_lessons WHERE course_version_id=' . $oldVersion . ' ORDER BY id')->loadAssocList();
$oldEnrollment = $db->setQuery('SELECT * FROM #__jlms_enrollments WHERE course_id=' . $course . ' ORDER BY id')->loadAssocList();
$draft = $authoring->createDraft($actor, $course, (int) $source->revision);
editorialExpect($draft !== $oldVersion, 'Published course gets an independent working copy');
$outline = $authoring->outline($actor, $course, $draft);
editorialExpect(count($outline['lessons']) === count($oldRows), 'Working copy contains all lessons');
$quizzes = $db->setQuery('SELECT * FROM #__jlms_quizzes WHERE course_version_id=' . $draft)->loadObjectList();
editorialExpect(count($quizzes) > 0, 'Working copy contains editable quizzes');
foreach ($quizzes as $quiz) {
    editorialExpect(!isset(json_decode($quiz->selection_json, true)['published_pool_hash']), 'Working quiz is unfrozen');
    editorialExpect((int) $db->setQuery('SELECT COUNT(*) FROM #__jlms_quiz_questions WHERE quiz_id=' . (int) $quiz->id)->loadResult() > 0, 'Question revision links are copied');
}
$revision = static fn(): int => (int) $db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id=' . $draft)->loadResult();
$authoring->saveLesson($actor, ['course_id'=>$course,'version_id'=>$draft,'expected_version_revision'=>$revision(),
    'module_id'=>(int)$outline['modules'][0]->id,'title'=>'New lesson in working copy','kind'=>'text','required'=>'0','body'=>'<p>New content</p>']);
$current = $db->setQuery('SELECT * FROM #__jlms_courses WHERE id=' . $course)->loadObject();
editorialExpect($authoring->createDraft($actor, $course, (int) $current->revision) === $draft, 'Opening edit again reuses the working copy');
$transitions = $publication->transitions($actor, $course, $draft);
editorialExpect(count($transitions) > 0, 'Working copy has a native publication transition');
$review = null;
foreach ($transitions as $transition) {
    if ((new \Joomla\Registry\Registry($transition->options))->get('jlms_action') === 'review') { $review = $transition; }
}
editorialExpect($review !== null, 'Working copy can enter native review');
$publication->transition($actor,$course,$draft,(int)$current->revision,$revision(),(int)$review->id);
editorialExpect((int)$db->setQuery('SELECT current_version_id FROM #__jlms_courses WHERE id='.$course)->loadResult() === $oldVersion, 'Review keeps the live edition assigned');
$current = $db->setQuery('SELECT * FROM #__jlms_courses WHERE id=' . $course)->loadObject();
$transitions = $publication->transitions($actor, $course, $draft);
$approval = null;
foreach ($transitions as $transition) {
    if ((new \Joomla\Registry\Registry($transition->options))->get('jlms_action') === 'publish') { $approval = $transition; }
}
editorialExpect($approval !== null, 'Reviewer has an explicit approval transition');
$impact = $publication->updateImpact($actor,$course,$draft,(int)$approval->id);
editorialExpect(is_array($impact) && isset($impact['fingerprint']), 'Versioned approval presents the publication impact');
$publication->transition($actor,$course,$draft,(int)$current->revision,$revision(),(int)$approval->id,$impact['fingerprint']);
editorialExpect((int)$db->setQuery('SELECT current_version_id FROM #__jlms_courses WHERE id='.$course)->loadResult() === $draft, 'Publication activates the edited version');
editorialExpect($oldRows === $db->setQuery('SELECT * FROM #__jlms_lessons WHERE course_version_id='.$oldVersion.' ORDER BY id')->loadAssocList(), 'Published source lessons retain all original data');
editorialExpect($oldEnrollment === $db->setQuery('SELECT * FROM #__jlms_enrollments WHERE course_id='.$course.' ORDER BY id')->loadAssocList(), 'Existing enrollments and results stay unchanged');
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
$item=$model->getItem($course);
editorialExpect($model->save(['id'=>$course,'revision'=>(int)$item->revision,'title'=>'Editorially updated course']), 'Normal save updates a published course');
$history=$db->setQuery("SELECT * FROM #__history WHERE item_id='com_jlms.course.".$course."' ORDER BY version_id DESC")->loadObjectList();
editorialExpect(count($history)>0, 'Native Joomla history stores course revisions');
editorialExpect((int)$model->getItem($course)->state===1, 'Normal save preserves publication state');
$oldHistory = end($history);
editorialExpect($model->loadHistory((int)$oldHistory->version_id), 'Native history restores editorial data through the guarded save');
editorialExpect((int)$model->getItem($course)->current_version_id === $draft, 'Restoring editorial history does not roll back teaching assignments');
$item=$model->getItem($course);
editorialExpect($model->save(['id'=>$course,'revision'=>(int)$item->revision,'title'=>'Editorially updated course']), 'Course stays editable after history restoration');

// Copy material associations and native MP4 references without copying blobs or progress.
foreach (['html5-fixture','materials-fixture'] as $alias) {
    $q=$db->createQuery()->select('*')->from('#__jlms_courses')->where('alias=:alias')->bind(':alias',$alias);
    $source=$db->setQuery($q)->loadObject();
    if (!$source || !$source->current_version_id) { continue; }
    $new=$authoring->createDraft($actor,(int)$source->id,(int)$source->revision);
    $oldAttachments=$db->setQuery('SELECT asset_id,title FROM #__jlms_attachments WHERE course_version_id='.(int)$source->current_version_id.' ORDER BY ordering,id')->loadAssocList();
    $newAttachments=$db->setQuery('SELECT asset_id,title FROM #__jlms_attachments WHERE course_version_id='.$new.' ORDER BY ordering,id')->loadAssocList();
    editorialExpect($oldAttachments===$newAttachments, 'Material associations share immutable stored files');
}

// Native teacher custom fields, with no category field in the profile editor.
PluginHelper::importPlugin('system', 'fields');
$fieldModel=$app->bootComponent('com_fields')->getMVCFactory()->createModel('Field','Administrator',['ignore_request'=>true]);
$field=['id'=>0,'context'=>'com_jlms.teacher','title'=>'Public teaching contact','name'=>'teaching-contact','type'=>'text',
    'state'=>1,'access'=>1,'language'=>'*','group_id'=>0,'required'=>0,'default_value'=>'','description'=>'','note'=>'','ordering'=>0,
    'params'=>['display'=>2],'fieldparams'=>['filter'=>'string']];
editorialExpect($fieldModel->save($field), 'Joomla creates a custom field in the teacher context');
$fieldId=(int)$fieldModel->getState('field.id');
$teacher=$factory->createModel('Teacher','Administrator',['ignore_request'=>true]);
$data=['id'=>0,'revision'=>1,'title'=>'Editorial teacher profile','alias'=>'editorial-teacher-profile','state'=>1,'access'=>1,'language'=>'*',
    'bio'=>'Teacher presentation','image'=>'','user_id'=>'','com_fields'=>['teaching-contact'=>'contact@example.invalid']];
editorialExpect($teacher->save($data), 'Teacher creation needs no category choice');
$teacherId=(int)$teacher->getState('teacher.id');
editorialExpect(!$teacher->getForm([],false)->getField('catid'), 'Teacher form has no category selector');
$q=$db->createQuery()->select('value')->from('#__fields_values')->where('field_id=:field AND item_id=:item')->bind(':field',$fieldId,\Joomla\Database\ParameterType::INTEGER)->bind(':item',$teacherId,\Joomla\Database\ParameterType::INTEGER);
editorialExpect($db->setQuery($q)->loadResult()==='contact@example.invalid', 'Native system fields plugin saves the teacher field value');
$editorFixture=json_decode(file_get_contents(dirname($root).'/editor-access-fixture.json'),true,32,JSON_THROW_ON_ERROR);
$linked=new \Joomla\CMS\User\User((int)$editorFixture['user']);
$asset=new \Joomla\CMS\Table\Asset($db);$asset->loadByName('com_jlms.teacher.'.$teacherId);
$rules=json_decode($asset->rules,true);$rules['core.edit.own'][(string)$editorFixture['group']]=true;
$asset->rules=json_encode($rules,JSON_THROW_ON_ERROR);$asset->store();
$asset->loadByName('com_jlms.field.'.$fieldId);$rules=json_decode($asset->rules,true);$rules['core.edit.value'][(string)$editorFixture['group']]=true;
$asset->rules=json_encode($rules,JSON_THROW_ON_ERROR);$asset->store();
$item=$teacher->getItem($teacherId);
editorialExpect($teacher->save(['id'=>$teacherId,'revision'=>(int)$item->revision,'user_id'=>(int)$linked->id]), 'Secretariat links a native account to its profile');
$teacher->setCurrentUser($linked);$app->loadIdentity($linked);$app->getSession()->set('user',$linked);
$item=$teacher->getItem($teacherId);
editorialExpect($teacher->save(['id'=>$teacherId,'revision'=>(int)$item->revision,'bio'=>'Teacher self-edited presentation','state'=>0,'user_id'=>(int)$actor->id,
    'com_fields'=>['teaching-contact'=>'own@example.invalid']]), 'Linked teacher edits their presentation and permitted custom fields');
$item=$teacher->getItem($teacherId);
editorialExpect((int)$item->state===1&&(int)$item->user_id===(int)$linked->id, 'Self editing cannot change publication or account ownership');
$app->loadIdentity($actor);$app->getSession()->set('user',$actor);$teacher->setCurrentUser($actor);
$item=$model->getItem($course);
editorialExpect($model->save(['id'=>$course,'revision'=>(int)$item->revision,'teacher_ids'=>[$teacherId]]), 'A published course can be assigned to the teacher');
file_put_contents(dirname($root).'/editorial-fixture.json',json_encode(['course'=>$course,'version'=>$draft,'teacher'=>$teacherId,'field'=>$fieldId],JSON_THROW_ON_ERROR));
echo json_encode(['editorial_checks'=>$checks,'status'=>'passed']).PHP_EOL;
