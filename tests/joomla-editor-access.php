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


use Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess as EditorAccess;
$checks=0;
function editorExpect(bool $ok,string $message):void { global $checks;if(!$ok)throw new RuntimeException($message);$checks++; }
$fixture=dirname($root).'/editor-access-fixture.json';
if (($argv[2]??'')==='retained') {
 $f=json_decode(file_get_contents($fixture),true,32,JSON_THROW_ON_ERROR);$user=new \Joomla\CMS\User\User((int)$f['user']);
 $list=$factory->createModel('Courses','Administrator',['ignore_request'=>true]);$list->setCurrentUser($user);$list->setState('list.limit',100);$list->setState('list.start',0);
 $ids=array_map(static fn($r)=>(int)$r->id,$list->getItems());
 editorExpect(in_array($f['allowed'],$ids,true)&&!in_array($f['denied'],$ids,true),'Category grants and record denies survive reinstall');
 editorExpect((int)$db->setQuery('SELECT checked_out FROM #__jlms_courses WHERE id='.(int)$f['locked'])->loadResult()===$f['user'],'Other-operator checkout preserved');
 echo json_encode(['editor_access_retention_checks'=>$checks,'status'=>'passed']).PHP_EOL;exit;
}
$groupModel=$app->bootComponent('com_users')->getMVCFactory()->createModel('Group','Administrator',['ignore_request'=>true]);
if(!$groupModel->save(['id'=>0,'parent_id'=>2,'title'=>'CI category course editor']))throw new RuntimeException($groupModel->getError());$group=(int)$groupModel->getState('group.id');
if(!$groupModel->save(['id'=>0,'parent_id'=>2,'title'=>'CI editor backend entry']))throw new RuntimeException($groupModel->getError());$backend=(int)$groupModel->getState('group.id');
$asset=new \Joomla\CMS\Table\Asset($db);$asset->loadByName('root.1');$rules=json_decode($asset->rules,true);$rules['core.login.admin'][(string)$backend]=true;$asset->rules=json_encode($rules,JSON_THROW_ON_ERROR);$asset->store();
$asset->loadByName('com_jlms');$rules=json_decode($asset->rules,true);$rules['core.manage'][(string)$backend]=true;$asset->rules=json_encode($rules,JSON_THROW_ON_ERROR);$asset->store();
$user=new \Joomla\CMS\User\User();$userData=['name'=>'CI category editor','username'=>'cieditor','email'=>'cieditor@example.invalid','password'=>'Ci-only-Editor-Password-2026','password2'=>'Ci-only-Editor-Password-2026','groups'=>[2,$group,$backend],'block'=>0,'activation'=>''];
if(!$user->bind($userData)||!$user->save())throw new RuntimeException('Editor user failed');$user=new \Joomla\CMS\User\User((int)$user->id);
$categoryModel=$app->bootComponent('com_categories')->getMVCFactory()->createModel('Category','Administrator',['ignore_request'=>true]);$categories=[];
foreach(['allowed','other','owned','trash']as$name){
 if(!$categoryModel->save(['id'=>0,'parent_id'=>1,'extension'=>'com_jlms.course','title'=>'Editor '.$name,'alias'=>'editor-'.$name,'published'=>$name==='trash'?-2:1,'language'=>'*','access'=>1]))throw new RuntimeException($categoryModel->getError());
 $categories[$name]=(int)$categoryModel->getState('category.id');
}
foreach(['allowed'=>['core.create','core.edit'],'owned'=>['core.edit.own']]as$name=>$actions){
 $asset->loadByName('com_jlms.course.category.'.$categories[$name]);$rules=json_decode($asset->rules,true);foreach($actions as$action)$rules[$action][(string)$group]=true;$asset->rules=json_encode($rules,JSON_THROW_ON_ERROR);$asset->store();
}
$user=EditorAccess::fresh($user);
editorExpect(!$user->authorise('core.create','com_jlms')&&EditorAccess::canCreate($db,$user),'Category-only create grant reaches the editor');
editorExpect(EditorAccess::canCreate($db,$user,$categories['allowed'])&&!EditorAccess::canCreate($db,$user,$categories['other']),'Create scope remains category specific');
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);$base=['id'=>0,'revision'=>1,'title'=>'','alias'=>'','catid'=>0,'language'=>'*','catalog_access'=>1,'content_access'=>2,'enrollment_mode'=>'auto','presentation_description'=>'<p>Course presentation for the publication fixture.</p>', 'summary'=>'Editor access fixture'];$courses=[];
foreach(['allowed1'=>'allowed','allowed2'=>'allowed','denied'=>'allowed','foreign'=>'other','own'=>'owned','other_owner'=>'owned','locked'=>'other']as$name=>$category){
 editorExpect($model->save(array_replace($base,['title'=>'Editor '.$name,'alias'=>'editor-'.$name,'catid'=>$categories[$category]])),'Fixture course saved');$courses[$name]=(int)$model->getState('course.id');
}
$change=(object)['id'=>$courses['own'],'created_by'=>(int)$user->id];$db->updateObject('#__jlms_courses',$change,'id');
$asset->loadByName('com_jlms.course.'.$courses['denied']);$rules=json_decode($asset->rules,true);$rules['core.edit'][(string)$group]=false;$rules['core.edit.own'][(string)$group]=false;$asset->rules=json_encode($rules,JSON_THROW_ON_ERROR);$asset->store();
$change=(object)['id'=>$courses['locked'],'checked_out'=>(int)$user->id,'checked_out_time'=>gmdate('Y-m-d H:i:s')];$db->updateObject('#__jlms_courses',$change,'id');
$list=$factory->createModel('Courses','Administrator',['ignore_request'=>true]);$list->setCurrentUser($user);$list->setState('list.limit',2);$list->setState('list.start',0);
$expected=[$courses['allowed1'],$courses['allowed2'],$courses['own']];sort($expected);
$first=$list->getItems();editorExpect(count($first)===2&&(int)$list->getTotal()===3,'Pagination total contains only editable records');
$list->setState('list.start',2);$second=$list->getItems();$actual=array_map(static fn($r)=>(int)$r->id,array_merge($first,$second));sort($actual);
editorExpect($actual===$expected,'All pages honour category grant, edit-own and explicit record deny');
// The same model and user objects must not retain cached records after ACL revocation.
$asset->loadByName('com_jlms.course.category.'.$categories['allowed']);$savedRules=$asset->rules;$rules=json_decode($savedRules,true);$rules['core.edit'][(string)$group]=false;$asset->rules=json_encode($rules,JSON_THROW_ON_ERROR);$asset->store();
$list->setState('list.start',0);editorExpect((int)$list->getTotal()===1&&count($list->getItems())===1,'Same-session category revoke invalidates native list cache scope');
$asset->rules=$savedRules;$asset->store();editorExpect((int)$list->getTotal()===3,'Native grant restoration refreshes list total');
$before=$db->setQuery('SELECT revision,title FROM #__jlms_courses WHERE id='.$courses['locked'])->loadAssoc();
editorExpect(!$model->save(array_replace($base,['id'=>$courses['locked'],'revision'=>1,'title'=>'Forbidden lock overwrite','catid'=>$categories['other']])),'Other-operator checkout cannot be bypassed even by direct model save');
editorExpect($model->getError()===\Joomla\CMS\Language\Text::_('COM_JLMS_COURSE_CHECKED_OUT')&&$db->setQuery('SELECT revision,title FROM #__jlms_courses WHERE id='.$courses['locked'])->loadAssoc()===$before,'Checkout refusal is specific and preserves course');
$model->setCurrentUser($user);
editorExpect($model->save(array_replace($base,['title'=>'Editor delegated save','alias'=>'editor-delegated-save','catid'=>$categories['allowed'],'presentation_description'=>''])),'Native save accepts category delegate without global create');
$delegated=(int)$model->getState('course.id');
editorExpect(!$model->save(array_replace($base,['title'=>'Wrong category','alias'=>'wrong-category','catid'=>$categories['other']])),'Forged create outside category scope denied');
$model->setCurrentUser($actor);
editorExpect(!$model->save(array_replace($base,['title'=>'Trash category','alias'=>'trash-category','catid'=>$categories['trash']])),'Trashed category cannot accept course creation');
$imageEdit=array_replace($base,['id'=>$courses['allowed1'],'revision'=>1,'title'=>'Editor allowed1','alias'=>'editor-allowed1','catid'=>$categories['allowed'],'presentation_image'=>'https://example.invalid/image.png','presentation_image_alt'=>'Cover']);
editorExpect(!$model->save($imageEdit)&&$model->getError()===\Joomla\CMS\Language\Text::_('COM_JLMS_COURSE_IMAGE_INVALID'),'Image validation retains actionable error');
$validation = array_replace($base, [
    'title' => 'Editorial validation fixture',
    'alias' => 'editorial-validation-fixture',
    'catid' => $categories['allowed'],
    'presentation_description'=>'<p>Course presentation for the publication fixture.</p>', 'summary' => str_repeat('è', 500),
]);
editorExpect($model->save($validation), '500 Unicode characters accepted without truncation');
$validationId = (int) $model->getState('course.id');
$validation['id'] = $validationId;
editorExpect($model->getItem($validationId)->summary === $validation['summary'], 'Unicode summary stored verbatim');
$tooLong = array_replace($validation, ['presentation_description'=>'<p>Course presentation for the publication fixture.</p>', 'summary' => $validation['summary'] . 'è']);
editorExpect(!$model->save($tooLong) && $model->getError() === \Joomla\CMS\Language\Text::_('COM_JLMS_COURSE_SUMMARY_TOO_LONG'), '501 characters rejected with actionable message');
editorExpect((int) $model->getItem($validationId)->revision === 1, 'Invalid summary does not increment revision');
$duplicate = array_replace($validation, ['id' => 0, 'alias' => 'Editorial Validation Fixture']);
editorExpect(!$model->save($duplicate) && $model->getError() === \Joomla\CMS\Language\Text::_('COM_JLMS_COURSE_ALIAS_DUPLICATE'), 'Duplicate normalized alias rejected in the same category and language');
editorExpect($model->save(array_replace($duplicate, ['catid' => $categories['other']])), 'Alias can be reused in another category');
$legacy = str_repeat('è', 501) . "\nLegacy";
$legacyRecord = (object) ['id' => $validationId, 'presentation_description'=>'<p>Course presentation for the publication fixture.</p>', 'summary' => $legacy];
$db->updateObject('#__jlms_courses', $legacyRecord, 'id');
$validation['summary'] = str_replace("\n", "\r\n", $legacy);
editorExpect($model->save($validation) && $model->getItem($validationId)->summary === $legacy, 'An unchanged legacy summary survives a normal browser round-trip');
$validation['revision'] = 2;
$validation['summary'] = 'Shortened description';
editorExpect($model->save($validation) && $model->getItem($validationId)->summary === $validation['summary'], 'Legacy summary can be shortened');
// Preserve one record grant after the category is no longer a creation destination.
$asset->loadByName('com_jlms.course.'.$courses['own']);$rules=json_decode($asset->rules,true);$rules['core.edit'][(string)$group]=true;$asset->rules=json_encode($rules,JSON_THROW_ON_ERROR);$asset->store();
// Build a first-publication fixture whose editor has only category-level state rights.
$asset->loadByName('com_jlms.course.category.' . $categories['allowed']);
$rules = json_decode($asset->rules, true);
$rules['core.edit.state'][(string) $group] = true;
$asset->rules = json_encode($rules, JSON_THROW_ON_ERROR);
$asset->store();
$versionId = $authoring->createDraft($actor, $delegated, 1);
$scope = ['course_id' => $delegated, 'version_id' => $versionId, 'expected_version_revision' => 1];
$moduleId = $authoring->saveModule($actor, $scope + ['id' => 0, 'title' => 'Delegated chapter']);
$scope['expected_version_revision']++;
$authoring->saveLesson($actor, $scope + [
    'id' => 0, 'module_id' => $moduleId, 'title' => 'Delegated lesson',
    'kind' => 'text', 'required' => '1', 'body' => '<p>Lesson content</p>',
]);
$transitionId = (int) $publication->transitions($actor, $delegated)[0]->id;
$asset->loadByName('com_jlms.transition.' . $transitionId);
$rules = json_decode($asset->rules, true);
$rules['core.execute.transition'][(string) $group] = true;
$asset->rules = json_encode($rules, JSON_THROW_ON_ERROR);
$asset->store();
$user = EditorAccess::fresh($user);
editorExpect(!$user->authorise('core.edit.state', 'com_jlms')
    && $user->authorise('core.edit.state', 'com_jlms.course.' . $delegated)
    && $user->authorise('core.execute.transition', 'com_jlms.transition.' . $transitionId),
    'Publisher fixture has scoped rights only');
$list->setCurrentUser(new \Joomla\CMS\User\User());
try{$items=$list->getItems();editorExpect($items===false,'Anonymous administration does not return course metadata');}catch(RuntimeException $error){editorExpect($error->getCode()===403,'Anonymous administration denied');}
file_put_contents($fixture,json_encode(['user'=>(int)$user->id,'group'=>$group,'backend'=>$backend,'allowed'=>$courses['allowed1'],'denied'=>$courses['denied'],'owned'=>$courses['own'],'locked'=>$courses['locked'],'allowed_category'=>$categories['allowed'],'other_category'=>$categories['other'],'delegated'=>$delegated],JSON_THROW_ON_ERROR));
echo json_encode(['editor_access_checks'=>$checks,'status'=>'passed']).PHP_EOL;
