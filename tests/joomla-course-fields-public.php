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



$checks=0;
function publicFieldsExpect(bool $ok,string $message): void { global $checks; if(!$ok){throw new RuntimeException($message);} $checks++; }
PluginHelper::importPlugin('system','fields');
$fixture=dirname($root).'/course-fields-public-fixture.json';
$mode=$argv[2]??'';
function publicFieldsState(array $f): array {
 global $db;
 $c=(int)$f['course'];$v=(int)$f['version'];$e=(int)$f['enrollment'];
 return ['teaching'=>[
  'version'=>$db->setQuery('SELECT * FROM #__jlms_course_versions WHERE id='.$v)->loadAssoc(),
  'modules'=>$db->setQuery('SELECT * FROM #__jlms_modules WHERE course_version_id='.$v.' ORDER BY id')->loadAssocList(),
  'lessons'=>$db->setQuery('SELECT * FROM #__jlms_lessons WHERE course_version_id='.$v.' ORDER BY id')->loadAssocList(),
  'enrollment'=>$db->setQuery('SELECT * FROM #__jlms_enrollments WHERE id='.$e)->loadAssoc(),
  'progress'=>$db->setQuery('SELECT * FROM #__jlms_lesson_progress WHERE enrollment_id='.$e.' ORDER BY lesson_id')->loadAssocList()],
  'course'=>$db->setQuery('SELECT * FROM #__jlms_courses WHERE id='.$c)->loadAssoc(),
  'values'=>$db->setQuery('SELECT field_id,value FROM #__fields_values WHERE item_id='.$db->quote((string)$c).' AND field_id IN ('.implode(',',array_map('intval',array_values($f['fields']))).') ORDER BY field_id,value')->loadAssocList(),
  'groups'=>$db->setQuery('SELECT * FROM #__fields_groups WHERE id='.(int)$f['group'])->loadAssocList(),
  'fields'=>$db->setQuery('SELECT * FROM #__fields WHERE id IN ('.implode(',',array_map('intval',array_values($f['fields']))).') ORDER BY id')->loadAssocList()];
}
if($mode!==''){
 $f=json_decode(file_get_contents($fixture),true,32,JSON_THROW_ON_ERROR);
 if($mode==='state'){echo json_encode(publicFieldsState($f),JSON_THROW_ON_ERROR);return;}
 if($mode==='checkpoint'){file_put_contents($fixture.'.snapshot',json_encode(publicFieldsState($f),JSON_THROW_ON_ERROR));return;}
 if($mode==='retained'){
  publicFieldsExpect(json_decode(file_get_contents($fixture.'.snapshot'),true,64,JSON_THROW_ON_ERROR)===publicFieldsState($f),'Editorial fields and assigned teaching survive package upgrade');
  echo json_encode(['course_fields_public_retained_checks'=>$checks,'status'=>'passed']);return;
 }
 if($mode==='edit'){
  $model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);$item=$model->getItem($f['course']);
  publicFieldsExpect($model->save(['id'=>$f['course'],'revision'=>$item->revision,'com_fields'=>['public-fields-after-title'=>'PUB_UPDATED_VALUE']]),'Native course model changes current field');
  publicFieldsExpect($db->setQuery('SELECT value FROM #__fields_values WHERE field_id='.(int)$f['fields']['after-title'].' AND item_id='.$db->quote((string)$f['course']))->loadResult()==='PUB_UPDATED_VALUE','Native fields plugin persists updated value');
 }elseif($mode==='revoke'){
  $db->setQuery('DELETE FROM #__user_usergroup_map WHERE user_id='.(int)$f['reader'])->execute();
 }elseif($mode==='group-public'){
  $change=(object)['id'=>$f['group'],'access'=>1]; $db->updateObject('#__fields_groups',$change,'id');
 }elseif($mode==='group-hidden'){
  $change=(object)['id'=>$f['group'],'state'=>0]; $db->updateObject('#__fields_groups',$change,'id');
 }elseif($mode==='field-hidden'){
  $change=(object)['id'=>$f['fields']['before-content'],'state'=>0]; $db->updateObject('#__fields',$change,'id');
 }elseif($mode==='withdraw'){
  $change=(object)['id'=>$f['course'],'state'=>0]; $db->updateObject('#__jlms_courses',$change,'id');
 }elseif($mode==='restore'){
  $change=(object)['id'=>$f['course'],'state'=>1]; $db->updateObject('#__jlms_courses',$change,'id');
 }elseif(in_array($mode,['override','remove-override'],true)){
  $path=$root.'/templates/cassiopeia/html/layouts/com_jlms/fields/render.php';
  if($mode==='override'){
   if(!is_dir(dirname($path))){mkdir(dirname($path),0755,true);}
   file_put_contents($path,'<?php defined("_JEXEC") or die; if (!empty($displayData["fields"])) { echo "<div class=\"native-fields-override-proof\">"; foreach ($displayData["fields"] as $field) { echo $field->value; } echo "</div>"; }');
  }elseif(is_file($path)){unlink($path);}
 }else{throw new RuntimeException('Unknown disposable fixture operation');}
 echo json_encode(['status'=>'passed']);return;
}
$actor=$app->getIdentity();PluginHelper::importPlugin('system','fields');
$categoryModel=$app->bootComponent('com_categories')->getMVCFactory()->createModel('Category','Administrator',['ignore_request'=>true]);
publicFieldsExpect($categoryModel->save(['id'=>0,'parent_id'=>1,'extension'=>'com_jlms.course','title'=>'Public fields category','alias'=>'public-fields-category','published'=>1,'language'=>'*','access'=>1]),'Dedicated native category created');
$cat=(int)$categoryModel->getState('category.id');
$other=(int)$db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
publicFieldsExpect($model->save(['id'=>0,'revision'=>1,'title'=>'Public course fields fixture','alias'=>'public-course-fields-fixture','catid'=>$cat,'language'=>'en-GB','catalog_access'=>1,'content_access'=>1,'enrollment_mode'=>'auto','summary'=>'Public course field introduction','presentation_description'=>'<p>Public presentation body.</p>']),'Independent course created');
$course=(int)$model->getState('course.id');
$authoring=$component->getCurriculumService();$service=$component->getPublicationService();
$version=$authoring->createDraft($actor,$course,1);
$revision=static fn(): int=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$version)->loadResult();
$module=$authoring->saveModule($actor,['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>$revision(),'title'=>'Public fields module']);
$authoring->saveLesson($actor,['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>$revision(),'module_id'=>$module,'title'=>'Protected text lesson','kind'=>'text','required'=>'1','body'=>'<p>FIELDS_PRIVATE_LESSON_BODY</p>']);
$transition=(int)$service->transitions($actor,$course,$version)[0]->id;
$item=$model->getItem($course);$service->transition($actor,$course,$version,(int)$item->revision,$revision(),$transition);
$enrollment=$component->getLearningService()->start($actor,'en-GB',$course,str_repeat('b',40));
$groupModel=$app->bootComponent('com_fields')->getMVCFactory()->createModel('Group','Administrator',['ignore_request'=>true]);
publicFieldsExpect($groupModel->save(['id'=>0,'context'=>'com_jlms.course','title'=>'Public fields restricted group','description'=>'','note'=>'','params'=>[],'state'=>1,'access'=>2,'language'=>'*']),'Native field group created: '.(string)$groupModel->getError());
$group=(int)$groupModel->getState('group.id');
$fieldModel=$app->bootComponent('com_fields')->getMVCFactory()->createModel('Field','Administrator',['ignore_request'=>true]);
$fields=[];
$definitions=[
 'after-title'=>[1,'text','PUB_AFTER_TITLE',[]],
 'before-content'=>[2,'textarea',"PUB_BEFORE_CONTENT\nsecond line",[]],
 'after-content'=>[3,'list','choice-1',['options'=>[['name'=>'PUB_NATIVE_CHOICE_LABEL','value'=>'choice-1']]]],
 'manual'=>[0,'text','FIELDS_MANUAL_SECRET',[]],
 'private'=>[2,'text','FIELDS_PRIVATE_VALUE',[],'access'=>2],
 'group'=>[2,'text','FIELDS_GROUP_VALUE',[],'group_id'=>$group],
 'unpublished'=>[2,'text','FIELDS_UNPUBLISHED_SECRET',[],'state'=>0],
 'foreign'=>[2,'text','FIELDS_FOREIGN_SECRET',[],'language'=>'it-IT'],
 'other-category'=>[2,'text','FIELDS_OTHER_CATEGORY_SECRET',[],'assigned_cat_ids'=>[$other]],
 'literal'=>[2,'text','PUB_LITERAL <script>FIELD_XSS()</script>',[]],
 'default'=>[2,'text',null,[],'default_value'=>'PUB_NATIVE_DEFAULT'],
 'empty'=>[2,'text','',[]],
];
foreach($definitions as $key=>$definition){
 $data=['id'=>0,'context'=>'com_jlms.course','title'=>'Public field '.$key,'label'=>'Public field '.$key,'required'=>0,'name'=>'public-fields-'.$key,'type'=>$definition[1],
  'state'=>1,'access'=>1,'language'=>'*','group_id'=>0,'default_value'=>'','description'=>'','assigned_cat_ids'=>[$cat],
  'params'=>['display'=>$definition[0],'showlabel'=>1],'fieldparams'=>$definition[3]];
 foreach($definition as $name=>$value){if(is_string($name)){$data[$name]=$value;}}
 publicFieldsExpect($fieldModel->save($data),'Native field created: '.$key.' '.(string)$fieldModel->getError());
 $id=(int)$fieldModel->getState('field.id');$fields[$key]=$id;
 if($definition[2]!==null){publicFieldsExpect($fieldModel->setFieldValue($id,$course,$definition[2]),'Native field value saved: '.$key);}
}
$reader=new \Joomla\CMS\User\User();
$readerData=['name'=>'Public fields reader','username'=>'publicfieldsreader','email'=>'publicfieldsreader@example.invalid','password'=>'Ci-only-Fields-Password-2026','password2'=>'Ci-only-Fields-Password-2026','groups'=>[2]];
publicFieldsExpect($reader->bind($readerData) && $reader->save(),'Native registered reader created');
$f=['course'=>$course,'version'=>$version,'enrollment'=>$enrollment,'reader'=>(int)$reader->id,'fields'=>$fields,'group'=>$group];
file_put_contents($fixture,json_encode($f,JSON_THROW_ON_ERROR));
$frozen=publicFieldsState($f)['teaching'];
publicFieldsExpect($model->save(['id'=>$course,'revision'=>$model->getItem($course)->revision,'com_fields'=>['public-fields-after-title'=>'PUB_AFTER_TITLE']]),'Existing published course accepts editorial field save');
publicFieldsExpect(publicFieldsState($f)['teaching']===$frozen,'Editorial save leaves assigned programme, enrollment and progress unchanged');
echo json_encode(['course_fields_public_native_checks'=>$checks,'status'=>'passed']);
