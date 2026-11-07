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



require __DIR__.'/cloudflare-fixture.php';
[$config,$secrets,$transport,$remote]=cloudflareFixture();
$vimeoEnabled=true;
$registryFactory=static function () use ($config,$secrets,$transport,&$vimeoEnabled): VideoProviderRegistry {
 $registry=new VideoProviderRegistry();
 foreach ([new \Jlms\Plugin\Jlmsvideo\Youtube\Provider\Provider(true),new \Jlms\Plugin\Jlmsvideo\Vimeo\Provider\Provider($vimeoEnabled),new \Jlms\Plugin\Jlmsvideo\Cloudflarestream\Provider\Provider($config,$secrets,$transport)] as $provider) {
  $registry->register($provider->getDescriptor(),static fn()=>$provider);
 }
 return $registry;
};
\Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->set('video_external_policy','embed');
$authoring=new \Jlms\Component\Jlms\Administrator\Application\CurriculumService($db,static fn(string $body):string=>\Joomla\CMS\Component\ComponentHelper::filterText($body),$registryFactory);
$component->setCurriculumService($authoring);$publication=$component->getPublicationService();$actor=$app->getIdentity();
$checks=0;
function restoreExpect(bool $ok,string $message): void {global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
function restoreDenied(callable $work,string $key): void {try{$work();}catch(\DomainException|\RuntimeException $e){restoreExpect($e->getMessage()===$key,'Expected '.$key.', got '.$e->getMessage());return;}throw new RuntimeException('Expected rejection '.$key);}
function restoreRow(string $table,int $id): object {global $db;return $db->setQuery('SELECT * FROM #__jlms_'.$table.' WHERE id='.$id)->loadObject();}
function restoreLearning(): array {global $db;return [$db->setQuery('SELECT * FROM #__jlms_enrollments ORDER BY id')->loadAssocList(),$db->setQuery('SELECT * FROM #__jlms_attempts ORDER BY id')->loadAssocList(),$db->setQuery('SELECT * FROM #__jlms_lesson_progress ORDER BY enrollment_id,lesson_id')->loadAssocList()];}
function restoreTree(int $id): array {
 global $db;$out=['version'=>(array)restoreRow('course_versions',$id)];
 foreach(['modules','lessons','quizzes','attachments'] as $table){$out[$table]=$db->setQuery('SELECT * FROM #__jlms_'.$table.' WHERE course_version_id='.$id.' ORDER BY id')->loadAssocList();}
 $out['questions']=$db->setQuery('SELECT qq.* FROM #__jlms_quiz_questions AS qq JOIN #__jlms_quizzes AS q ON q.id=qq.quiz_id WHERE q.course_version_id='.$id.' ORDER BY qq.quiz_id,qq.ordering')->loadAssocList();
 return $out;
}
$fixture=dirname($root).'/programme-restore-fixture.json';$mode=$argv[2]??'';
if(in_array($mode,['state','checkpoint','retained'],true)){
 $f=json_decode(file_get_contents($fixture),true,32,JSON_THROW_ON_ERROR);$course=$f['course'];
 $ids=$db->setQuery('SELECT id FROM #__jlms_course_versions WHERE course_id='.$course.' ORDER BY version_no')->loadColumn();
 $state=['course'=>(array)restoreRow('courses',$course),'versions'=>array_map(static fn($id)=>restoreTree((int)$id),$ids),'learning'=>restoreLearning(),
 'audit'=>$db->setQuery("SELECT * FROM #__action_logs WHERE extension='com_jlms.programmerestore' AND item_id=".$course.' ORDER BY id')->loadAssocList()];
 if($mode==='checkpoint'){file_put_contents($fixture.'.snapshot',json_encode($state,JSON_THROW_ON_ERROR));return;}
 if($mode==='retained'){restoreExpect(json_decode(file_get_contents($fixture.'.snapshot'),true,64,JSON_THROW_ON_ERROR)===$state,'Recovered programmes, provider references, native audit and learning survive upgrade');echo json_encode(['programme_restore_retained_checks'=>$checks,'status'=>'passed']).PHP_EOL;return;}
 echo json_encode($state,JSON_THROW_ON_ERROR);return;
}
$source=(int)$db->setQuery("SELECT id FROM #__jlms_courses WHERE alias='quiz-runtime-fixture'")->loadResult();$record=restoreRow('courses',$source);$original=restoreRow('course_versions',(int)$record->current_version_id);
$model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
$course=$model->duplicate(['course_id'=>$source,'version_id'=>$original->id,'revision'=>$record->revision,'expected_version_revision'=>$original->revision,'title'=>'Historical programme fixture']);
$copy=$model->getItem($course);restoreExpect($model->save(['id'=>$course,'revision'=>$copy->revision,'catalog_access'=>1,'content_access'=>1,'enrollment_mode'=>'auto']),'Independent fixture has explicit learner access');
$version=(int)$authoring->outline($actor,$course)['version']->id;
$scope=static function()use($course,&$version):array{return ['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>restoreRow('course_versions',$version)->revision];};
$module=(int)$authoring->outline($actor,$course,$version)['modules'][0]->id;
foreach(['youtube'=>'dQw4w9WgXcQ','vimeo'=>'123456789','cloudflarestream'=>str_repeat('b',32)] as $key=>$reference){
 $authoring->saveLesson($actor,$scope()+['id'=>0,'module_id'=>$module,'title'=>'Historical '.$key,'kind'=>'video','required'=>'0','body'=>'','provider_key'=>$key,'source_reference'=>$reference,'privacy_hash'=>$key==='vimeo'?'abcdef1234':'','duration_seconds'=>'10','duration_confirmed'=>'1']);
}
$publish=static function(int $target)use($publication,$actor,$course):void{
 $transition=(int)$publication->transitions($actor,$course,$target)[0]->id;
 $impact=$publication->updateImpact($actor,$course,$target,$transition);
 $publication->transition($actor,$course,$target,(int)restoreRow('courses',$course)->revision,(int)restoreRow('course_versions',$target)->revision,$transition,$impact['fingerprint']??null);
};
$attachment=$db->setQuery("SELECT a.* FROM #__jlms_attachments AS a JOIN #__jlms_assets AS f ON f.id=a.asset_id WHERE f.status='ready' AND a.state=1 ORDER BY a.id",0,1)->loadAssoc();
restoreExpect(is_array($attachment),'Disposable fixture provides a ready material');
unset($attachment['id']);$attachment['course_version_id']=$version;$attachment['lesson_id']=null;$attachment=(object)$attachment;$db->insertObject('#__jlms_attachments',$attachment,'id');
$publish($version);$historical=$version;
$enrollment=$component->getLearningService()->start($actor,'en-GB',$course,bin2hex(random_bytes(20)));
$version=$authoring->createDraft($actor,$course,(int)restoreRow('courses',$course)->revision);
$module=(int)$authoring->outline($actor,$course,$version)['modules'][0]->id;
$authoring->saveLesson($actor,$scope()+['id'=>0,'module_id'=>$module,'title'=>'Current additional text','kind'=>'text','required'=>'0','body'=>'<p>Current edition</p>']);
$publish($version);$current=$version;
$existing=$authoring->createDraft($actor,$course,(int)restoreRow('courses',$course)->revision);
$trees=[$historical=>restoreTree($historical),$current=>restoreTree($current),$existing=>restoreTree($existing)];$before=restoreLearning();$courseBefore=restoreRow('courses',$course);
$input=['course_id'=>$course,'version_id'=>$historical,'revision'=>$courseBefore->revision,'expected_version_revision'=>restoreRow('course_versions',$historical)->revision,'confirmation'=>'1'];
restoreDenied(fn()=>$authoring->restoreVersion($actor,array_replace($input,['confirmation'=>'0'])),'COM_JLMS_PROGRAMME_RESTORE_CONFIRM_REQUIRED');
restoreDenied(fn()=>$authoring->restoreVersion(new \Joomla\CMS\User\User(0),$input),'JERROR_ALERTNOAUTHOR');
restoreDenied(fn()=>$authoring->restoreVersion($actor,array_replace($input,['version_id'=>$original->id,'expected_version_revision'=>$original->revision])),'COM_JLMS_RESOURCE_NOT_FOUND');
restoreDenied(fn()=>$authoring->restoreVersion($actor,array_replace($input,['version_id'=>$current,'expected_version_revision'=>restoreRow('course_versions',$current)->revision])),'COM_JLMS_PROGRAMME_RESTORE_SOURCE_INVALID');
restoreDenied(fn()=>$authoring->restoreVersion($actor,array_replace($input,['version_id'=>$existing,'expected_version_revision'=>restoreRow('course_versions',$existing)->revision])),'COM_JLMS_PROGRAMME_RESTORE_SOURCE_INVALID');
restoreDenied(fn()=>$authoring->restoreVersion($actor,array_replace($input,['expected_version_revision'=>(int)$input['expected_version_revision']+1])),'COM_JLMS_REVISION_CONFLICT');
$otherId=(int)$db->setQuery("SELECT id FROM #__users WHERE username='cilearner'")->loadResult();
foreach([['courses',$course],['course_versions',$historical]] as [$table,$id]){
 $old=restoreRow($table,$id)->checked_out;$locked=(object)['id'=>$id,'checked_out'=>$otherId];$db->updateObject('#__jlms_'.$table,$locked,'id',true);
 try{restoreDenied(fn()=>$authoring->restoreVersion($actor,$input),'JERROR_ALERTNOAUTHOR');}finally{$locked->checked_out=$old;$db->updateObject('#__jlms_'.$table,$locked,'id',true);}
}
$target=$authoring->restoreVersion($actor,$input);$restored=restoreTree($target);
restoreExpect($restored['version']['status']==='draft'&&(int)$restored['version']['version_no']===4&&$restored['version']['structure_hash']===null,'Recovery creates the next independent unfrozen draft');
foreach($trees as $id=>$tree){restoreExpect(restoreTree($id)===$tree,'Historical, active and preexisting draft programme remain unchanged: '.$id);}
foreach(['modules','lessons','quizzes','attachments'] as $table){
 $old=$trees[$historical][$table];$new=$restored[$table];restoreExpect(count($old)===count($new),'Complete '.$table.' count restored');
 foreach($old as $i=>$row){restoreExpect($row['id']!==$new[$i]['id'],'Fresh physical '.$table.' identifier');
  foreach(['asset_id','title','state','logical_key','provider_key','source_json','params_json','duration_ms','required','kind','body','source_revision'] as $field){if(array_key_exists($field,$row))restoreExpect($row[$field]===$new[$i][$field],'Preserved '.$table.' '.$field);}
 }
}
restoreExpect(count($restored['questions'])===count($trees[$historical]['questions'])&&count($restored['questions'])>0,'Quiz question references retained');
foreach($trees[$historical]['questions'] as $i=>$question){$new=$restored['questions'][$i];unset($question['quiz_id'],$new['quiz_id']);restoreExpect($question===$new,'Immutable question revision and ordering preserved');}
restoreExpect(count(array_filter($restored['lessons'],static fn($l)=>$l['kind']==='video'))===3,'All three external video providers are represented');
$courseAfter=restoreRow('courses',$course);foreach(['revision','modified_at','modified_by'] as $field){unset($courseBefore->$field,$courseAfter->$field);}
restoreExpect($courseBefore==$courseAfter,'Course presentation, teachers, access, state and current pointer are preserved');
restoreExpect(restoreLearning()===$before&&(int)restoreRow('enrollments',$enrollment)->course_version_id===$historical,'Recovery leaves all learning history unchanged');
restoreExpect($authoring->createDraft($actor,$course,(int)restoreRow('courses',$course)->revision)===$target,'Edit programme deterministically reuses newest draft');
restoreDenied(fn()=>$authoring->restoreVersion($actor,$input),'COM_JLMS_REVISION_CONFLICT');
restoreExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_course_versions WHERE course_id='.$course)->loadResult()===4,'Stale replay creates no second recovery');
$audit=$db->setQuery("SELECT message FROM #__action_logs WHERE extension='com_jlms.programmerestore' AND item_id=".$course.' ORDER BY id DESC')->loadResult();$audit=json_decode($audit,true,32,JSON_THROW_ON_ERROR);
restoreExpect((int)$audit['source_version_id']===$historical&&(int)$audit['target_version_id']===$target,'Native audit records historical source and new draft');
$vimeoEnabled=false;restoreDenied(fn()=>$publish($target),'COM_JLMS_VIDEO_EDITOR_CONFIG_REQUIRED');
restoreExpect(restoreRow('course_versions',$target)->status==='draft'&&(int)restoreRow('courses',$course)->current_version_id===$current&&restoreLearning()===$before,'Unavailable provider blocks publication without activating recovery');
$vimeoEnabled=true;$publish($target);
restoreExpect((int)restoreRow('courses',$course)->current_version_id===$target&&restoreLearning()===$before,'Normal impact-confirmed publication activates recovery without migrating learners');
restoreExpect(restoreTree($existing)===$trees[$existing],'Publishing recovered programme preserves another open draft');
restoreExpect(count($remote->calls)>0,'Cloudflare privacy and metadata validation uses controlled transport');
file_put_contents($fixture,json_encode(['course'=>$course,'historical'=>$historical,'current'=>$target,'existing'=>$existing],JSON_THROW_ON_ERROR));
echo json_encode(['programme_restore_native_checks'=>$checks,'status'=>'passed']).PHP_EOL;
