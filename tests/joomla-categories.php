<?php

declare(strict_types=1);


/** Run after Joomla's CLI installer and extension:install in an isolated CI site. */
$root = realpath($argv[1] ?? '');
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new \RuntimeException('Disposable Joomla site required.');
}
// Only the parent configures the fixture; workers must not mutate shared plugin settings.
if (($argv[2] ?? '') !== 'worker') {
    $fixtureDb = new \PDO(getenv('JLMS_TEST_DSN'), getenv('JLMS_TEST_USER'), getenv('JLMS_TEST_PASSWORD'), [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    $plugin = $fixtureDb->query("SELECT enabled, params FROM joomla_extensions WHERE type='plugin' AND folder='system' AND element='languagefilter'")->fetch(\PDO::FETCH_ASSOC);
    $fixtureDb->exec("UPDATE joomla_extensions SET enabled=1, params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.item_associations',1) WHERE type='plugin' AND folder='system' AND element='languagefilter'");
    register_shutdown_function(static function () use ($fixtureDb, $plugin): void {
        $statement = $fixtureDb->prepare("UPDATE joomla_extensions SET enabled=?, params=? WHERE type='plugin' AND folder='system' AND element='languagefilter'");
        $statement->execute([$plugin['enabled'], $plugin['params']]);
    });
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

$checks=0;
function catExpect(bool $ok,string $message):void { global $checks; if(!$ok)throw new \RuntimeException($message);$checks++; }
function catHidden(int $id,string $language='en-GB'):bool {
    global $catalog,$guest;
    try{$catalog->category($guest,$language,$id);}catch(\RuntimeException $e){return $e->getCode()===404;}return false;
}
$catalog=$component->getCatalogService();$guest=new \Joomla\CMS\User\User();$actor=$app->getIdentity();
$helper=$component->getAssociationsExtension();$fixturePath=dirname($root).'/categories-fixture.json';
if(($argv[2]??'')==='retained'){
    $f=json_decode(file_get_contents($fixturePath),true,16,JSON_THROW_ON_ERROR);
    catExpect((int)$helper->getAssociations('course.category',$f['en'])['it-IT']->id===$f['it'],'native category translation retained');
    catExpect((int)$catalog->category($guest,'en-GB',$f['child'])->parent_id===$f['en'],'native hierarchy retained');
    catExpect((int)$catalog->course($guest,'en-GB',$f['course'])->catid===$f['child'],'course taxonomy retained');
    echo json_encode(['category_retention_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;exit;
}
$app->getInput()->set('extension','com_jlms.course');
$saveCategory=static function(array $data)use($app):int{
    $model=$app->bootComponent('com_categories')->getMVCFactory()->createModel('Category','Administrator',['ignore_request'=>true]);
    $model->setState('category.extension',$data['extension']??'com_jlms.course');
    $model->setState('category.component','com_jlms');$model->setState('category.section','course');
    if(!$model->save($data+['id'=>0,'parent_id'=>1,'extension'=>'com_jlms.course','published'=>1,'language'=>'*','access'=>1,'description'=>'']))throw new \RuntimeException('Native category save failed: '.$model->getError());
    return (int)$model->getState('category.id');
};
$en=$saveCategory(['title'=>'Taxonomy English','alias'=>'taxonomy-en','language'=>'en-GB','description'=>'<p>Category presentation</p><script>UNSAFE_CATEGORY_SCRIPT</script>']);
$it=$saveCategory(['title'=>'Tassonomia italiana','alias'=>'taxonomy-it','language'=>'it-IT','associations'=>['en-GB'=>$en]]);
$child=$saveCategory(['title'=>'Visible taxonomy child','alias'=>'taxonomy-child','parent_id'=>$en,'language'=>'en-GB']);
$hidden=$saveCategory(['title'=>'HIDDEN_TAXONOMY_CHILD','alias'=>'taxonomy-hidden','parent_id'=>$en,'access'=>2]);
$unpublished=$saveCategory(['title'=>'UNPUBLISHED_TAXONOMY_CHILD','alias'=>'taxonomy-unpublished','parent_id'=>$en,'published'=>0]);
$wrongLanguage=$saveCategory(['title'=>'WRONG_LANGUAGE_TAXONOMY_CHILD','alias'=>'taxonomy-language','parent_id'=>$en,'language'=>'it-IT']);
$grandchild=$saveCategory(['title'=>'HIDDEN_TAXONOMY_GRANDCHILD','alias'=>'taxonomy-grandchild','parent_id'=>$hidden]);
$otherSection=$saveCategory(['title'=>'PRIVATE_QUESTION_CATEGORY','alias'=>'taxonomy-question','extension'=>'com_jlms.question']);
catExpect($helper->getTypeTables('course.category')['a']==='#__categories','native manager category type registered');
catExpect((int)$helper->getAssociations('course.category',$en)['it-IT']->id===$it,'section-scoped core category association');
catExpect($helper->getItem('course.category',$otherSection)===null && $helper->getAssociations('course.category',$otherSection)===[],'question taxonomy excluded from course association helper');
catExpect(catHidden(1)&&catHidden($hidden)&&catHidden($unpublished)&&catHidden($wrongLanguage)&&catHidden($grandchild)&&catHidden($otherSection),'root, section, language and ancestor access denied');
$app->loadIdentity($guest);
$model=$factory->createModel('Categories','Site',['ignore_request'=>true]);$model->setState('filter.parent_id',$en);$model->setState('list.limit',20);$model->setState('list.start',0);
$ids=array_map(static fn($c)=>(int)$c->id,$model->getItems());
catExpect($ids===[$child] && (int)$model->getTotal()===1,'hidden children do not affect list or count');
catExpect(count($catalog->categoryPath($guest,'en-GB',$child))===2,'visible native ancestor path');
$update=static function(int $id,array $fields)use($db):void{$record=(object)(['id'=>$id]+$fields);$db->updateObject('#__categories',$record,'id',true);};
foreach([['access',2],['published',0],['language','it-IT']] as [$field,$value]){
    $update($en,[$field=>$value]);catExpect(catHidden($child),'ancestor '.$field.' hides direct child URL');
    $update($en,[$field=>match($field){'access','published'=>1,default=>'en-GB'}]);
}
catExpect(!catHidden($child),'restored access current without shared category cache');
$app->loadIdentity($actor);
// A native move under a restricted ancestor must hide an otherwise public child.
$move=new \Joomla\CMS\Table\Category($db);$move->load($child);$move->setLocation($hidden,'last-child');
if(!$move->store())throw new \RuntimeException('Native move failed');
catExpect(catHidden($child),'native category move changes current inherited visibility');
$move->load($child);$move->setLocation($en,'last-child');if(!$move->store())throw new \RuntimeException('Native move restore failed');
catExpect(!catHidden($child),'native move restore reflected');
$createCourse=static function(int $catid,string $tag,string $alias)use($factory,$component,$actor,$db):int{
    $model=$factory->createModel('Course','Administrator',['ignore_request'=>true]);
    if(!$model->save(['id'=>0,'title'=>$alias,'alias'=>$alias,'catid'=>$catid,'language'=>$tag,'catalog_access'=>1,'content_access'=>2,'enrollment_mode'=>'auto','presentation_description'=>'<p>Course presentation for the publication fixture.</p>', 'summary'=>'Category course summary','revision'=>1]))throw new \RuntimeException($model->getError());
    $course=(int)$model->getState('course.id');$authoring=$component->getCurriculumService();$version=$authoring->createDraft($actor,$course,1);
    $scope=static fn()=>['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$version)->loadResult()];
    $module=$authoring->saveModule($actor,$scope()+['id'=>0,'title'=>'Category chapter']);
    $authoring->saveLesson($actor,$scope()+['id'=>0,'module_id'=>$module,'title'=>'Category lesson','kind'=>'text','required'=>'1','body'=>'<p>PRIVATE_CATEGORY_LESSON</p>']);
    $publication=$component->getPublicationService();$choices=$publication->transitions($actor,$course);
    $publication->transition($actor,$course,$version,(int)$db->setQuery('SELECT revision FROM #__jlms_courses WHERE id='.$course)->loadResult(),$scope()['expected_version_revision'],(int)$choices[0]->id);
    return $course;
};
$course=$createCourse($child,'en-GB','taxonomy-visible-course');$italianCourse=$createCourse($it,'it-IT','taxonomy-corso-italiano');
$hiddenCourse=$createCourse($hidden,'en-GB','HIDDEN_TAXONOMY_COURSE');
$deleteRejected=false;
try{$db->setQuery('DELETE FROM #__categories WHERE id='.(int)$child)->execute();}
catch(\Throwable){$deleteRejected=true;}
catExpect($deleteRejected && (int)$db->setQuery('SELECT COUNT(*) FROM #__categories WHERE id='.(int)$child)->loadResult()===1,'permanent category deletion is restricted while a course references it');
$update($en,['published'=>-2]);
catExpect(catHidden($child) && (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_courses WHERE id='.(int)$course)->loadResult()===1,'trashing a category hides its course without deleting teaching history');
$update($en,['published'=>1]);
catExpect(!catHidden($child) && (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_courses WHERE id='.(int)$course)->loadResult()===1,'restoring a category restores visibility and retains the course');
$app->getInput()->set('id',$en);
$app->loadIdentity($guest);
$categoryModel=$factory->createModel('Category','Site',['ignore_request'=>true]);$categoryModel->setState('filter.category_id',$en);$categoryModel->setState('list.limit',20);$categoryModel->setState('list.start',0);
catExpect(array_map(static fn($c)=>(int)$c->id,$categoryModel->getItems())===[$course],'category course list includes visible descendants only');
catExpect((int)$categoryModel->getTotal()===1,'course pagination excludes hidden descendants');
$app->loadIdentity($guest);
catExpect(str_contains($helper->getAssociationsForItem($en,'category')['it-IT']??'','view=category&id='.$it),'category language switch targets associated visible category');
$update($it,['access'=>2]);catExpect($helper->getAssociationsForItem($en,'category')===[],'target category access checked live');$update($it,['access'=>1]);
$update($it,['published'=>0]);catExpect($helper->getAssociationsForItem($en,'category')===[],'unpublished category translation omitted');$update($it,['published'=>1]);
$app->loadIdentity($actor);
\Joomla\CMS\Form\Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_categories/forms');
// Native category form must own the association fields; no replacement editor.
$native=$app->bootComponent('com_categories')->getMVCFactory()->createModel('Category','Administrator',['ignore_request'=>true]);
$native->setState('category.id',$en);$native->setState('category.extension','com_jlms.course');$native->setState('category.component','com_jlms');$native->setState('category.section','course');
catExpect((bool)$native->getForm()->getField('it-IT','associations'),'native category language form available');
// More than one page of visible children, with no hidden-child count disclosure.
for($i=0;$i<21;$i++)$saveCategory(['title'=>'Taxonomy page child '.$i,'alias'=>'taxonomy-page-'.$i,'parent_id'=>$en,'language'=>'en-GB']);
$app->loadIdentity($guest);
$listed=$factory->createModel('Categories','Site',['ignore_request'=>true]);$listed->setState('filter.parent_id',$en);$listed->setState('list.start',20);$listed->setState('list.limit',20);
catExpect(count($listed->getItems())===2 && (int)$listed->getTotal()===22,'bounded second page counts visible children only');
$description=static fn($id)=>(string)$db->setQuery('SELECT description FROM #__categories WHERE id='.(int)$id)->loadResult();
$app->loadIdentity($actor);
$componentId=(int)$db->setQuery("SELECT extension_id FROM #__extensions WHERE type='component' AND element='com_jlms'")->loadResult();
$menus=[];
foreach(['en-GB'=>$en,'it-IT'=>$it] as $tag=>$categoryId){
    foreach(['categories','category'] as $view){
        $alias='taxonomy-menu-'.$view.'-'.substr($tag,0,2);
        $table=new \Joomla\CMS\Table\Menu($db);$table->setLocation(1,'last-child');
        $table->bind(['title'=>$alias,'alias'=>$alias,'menutype'=>'mainmenu','type'=>'component','link'=>'index.php?option=com_jlms&view='.$view.($view==='category'?'&id='.$categoryId:''),
            'component_id'=>$componentId,'published'=>1,'access'=>1,'language'=>$tag,'home'=>0,'params'=>'{}','browserNav'=>0]);
        if(!$table->check()||!$table->store())throw new \RuntimeException('Native category menu failed: '.$table->getError());
        $menus[$alias]=(int)$table->id;
    }
}
catExpect(count($menus)===4,'native category and category-index menus in both languages');
// Explicit dependency review and atomic recategorisation preserve the teaching version.
$moveSource=$saveCategory(['title'=>'Reassignment source','alias'=>'reassignment-source','language'=>'en-GB']);
$moveTarget=$saveCategory(['title'=>'Reassignment destination','alias'=>'reassignment-destination','language'=>'en-GB']);
$moveChild=$saveCategory(['title'=>'Reassignment child','alias'=>'reassignment-child','parent_id'=>$moveSource,'language'=>'en-GB']);
$db->setQuery('UPDATE #__jlms_courses SET catid='.(int)$moveSource.' WHERE id='.(int)$course)->execute();
$versionBefore=(int)$db->setQuery('SELECT current_version_id FROM #__jlms_courses WHERE id='.(int)$course)->loadResult();
$reassignment=$component->getCategoryReassignmentService();
$inspection=$reassignment->inspect($actor,$moveSource);
catExpect($inspection['references']['courses']===1 && $inspection['targets']['courses']!==[],'category dependency review counts courses and offers permitted sibling destinations');
catExpect(!in_array($moveChild,array_map(static fn($target)=>(int)$target->id,$inspection['targets']['courses']),true),'descendant categories are excluded as relocation targets');
$descendantRejected=false;try{$reassignment->reassign($actor,$moveSource,['courses'=>$moveChild]);}catch(\InvalidArgumentException){$descendantRejected=true;}
catExpect($descendantRejected,'server rejects a manually submitted descendant destination');
$reassignment->reassign($actor,$moveSource,['courses'=>$moveTarget]);
catExpect((int)$db->setQuery('SELECT catid FROM #__jlms_courses WHERE id='.(int)$course)->loadResult()===$moveTarget,'explicit reassignment changes the course category');
catExpect((int)$db->setQuery('SELECT current_version_id FROM #__jlms_courses WHERE id='.(int)$course)->loadResult()===$versionBefore,'category reassignment keeps the active teaching version');
file_put_contents($fixturePath,json_encode(compact('en','it','child','hidden','unpublished','wrongLanguage','grandchild','otherSection','course','italianCourse','hiddenCourse','menus')+['description'=>$description($en)],JSON_THROW_ON_ERROR));
echo json_encode(['native_category_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;
