<?php

declare(strict_types=1);


/** Run after Joomla's CLI installer and extension:install in an isolated CI site. */
$root = realpath($argv[1] ?? '');
if (!$root || getenv('JLMS_TEST_DISPOSABLE') !== '1') {
    throw new \RuntimeException('Disposable Joomla site required.');
}
$fixtureDb = new \PDO(getenv('JLMS_TEST_DSN'), getenv('JLMS_TEST_USER'), getenv('JLMS_TEST_PASSWORD'), [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
$plugin = $fixtureDb->query("SELECT enabled, params FROM joomla_extensions WHERE type='plugin' AND folder='system' AND element='languagefilter'")->fetch(\PDO::FETCH_ASSOC);
$fixtureDb->exec("UPDATE joomla_extensions SET enabled=1, params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.item_associations',1) WHERE type='plugin' AND folder='system' AND element='languagefilter'");
register_shutdown_function(static function () use ($fixtureDb, $plugin): void {
    $statement = $fixtureDb->prepare("UPDATE joomla_extensions SET enabled=?, params=? WHERE type='plugin' AND folder='system' AND element='languagefilter'");
    $statement->execute([$plugin['enabled'], $plugin['params']]);
});

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

$checks = 0;
function assocExpect(bool $condition, string $message): void {
    global $checks;
    if (!$condition) { throw new \RuntimeException($message); }
    $checks++;
}
function assocData(int $id): array {
    global $db;
    return (array) $db->setQuery('SELECT id,title,alias,catid,language,catalog_access,content_access,enrollment_mode,summary,revision FROM #__jlms_courses WHERE id=' . $id)->loadObject();
}
function assocSave(array $data): bool {
    global $factory;
    return $factory->createModel('Course', 'Administrator', ['ignore_request' => true])->save($data);
}
function assocRows(): string {
    global $db;
    return json_encode($db->setQuery("SELECT * FROM #__associations WHERE context='com_jlms.item' ORDER BY id")->loadObjectList(), JSON_THROW_ON_ERROR);
}
if (($argv[2] ?? '') === 'worker') {
    $command = json_decode($argv[3], true, 16, JSON_THROW_ON_ERROR);
    echo "ready\n"; flush();
    if (trim((string) fgets(STDIN)) !== 'go') { throw new \RuntimeException('Association worker barrier missing'); }
    echo json_encode(['saved'=>assocSave(assocData($command['source']) + ['associations'=>['it-IT'=>$command['target']]])], JSON_THROW_ON_ERROR);
    exit;
}
$actor = $app->getIdentity();
$helper = $component->getAssociationsExtension();
assocExpect($component instanceof \Joomla\CMS\Association\AssociationServiceInterface && $helper->hasAssociationsSupport(), 'native association service registered');
assocExpect($helper->getItemTypes() === ['course'] && !$helper->getTypeSupport('course')['save2copy'], 'only implemented native association types advertised');
$fixturePath = dirname($root) . '/associations-fixture.json';
if (($argv[2] ?? '') === 'retained') {
    $fixture = json_decode(file_get_contents($fixturePath), true, 16, JSON_THROW_ON_ERROR);
    assocExpect(assocRows() === $fixture['rows'], 'native course association graph survives package update');
    assocExpect((int) $helper->getAssociations('course', $fixture['en'])['it-IT']->id === $fixture['it'], 'native API still resolves retained translation');
    assocExpect((int) $db->setQuery('SELECT current_version_id FROM #__jlms_courses WHERE id=' . $fixture['en'])->loadResult() === $fixture['version'], 'published teaching pointer retained');
    echo json_encode(['association_retention_checks' => $checks, 'status' => 'passed'], JSON_THROW_ON_ERROR) . PHP_EOL;
    exit;
}
$language = new \Joomla\CMS\Table\Language($db);
if (!$language->load(['lang_code' => 'it-IT'])) {
    $language->bind(['lang_code' => 'it-IT', 'title' => 'Italiano', 'title_native' => 'Italiano', 'sef' => 'it', 'image' => 'it_it', 'description' => '', 'published' => 1, 'access' => 1, 'ordering' => 2]);
    if (!$language->check() || !$language->store()) { throw new \RuntimeException('Content language fixture failed'); }
}
$catid = (int) $db->setQuery("SELECT id FROM #__categories WHERE extension='com_jlms.course' AND alias='ci-courses'")->loadResult();
$create = static function (string $name, string $tag) use ($catid, $factory): int {
    $model = $factory->createModel('Course', 'Administrator', ['ignore_request' => true]);
    if (!$model->save(['id'=>0, 'title'=>$name, 'alias'=>$name, 'catid'=>$catid, 'language'=>$tag,
        'catalog_access'=>1, 'content_access'=>2, 'enrollment_mode'=>'auto', 'summary'=>'Translation fixture', 'revision'=>1])) {
        throw new \RuntimeException('Association draft fixture: ' . $model->getError());
    }
    return (int) $model->getState('course.id');
};
$en = $create('association-en', 'en-GB');
$it = $create('association-it', 'it-IT');
$otherEn = $create('association-other-en', 'en-GB');
$otherIt = $create('association-other-it', 'it-IT');
$all = $create('association-all', '*');
assocExpect(assocSave(assocData($en) + ['associations'=>['it-IT'=>$it]]), 'translation saved through native AdminModel');
assocExpect((int) $helper->getAssociations('course', $en)['it-IT']->id === $it, 'native Associations API reads course translation without wrong category-extension join');
$item = $factory->createModel('Course', 'Administrator', ['ignore_request'=>true])->getItem($en);
assocExpect((int) $item->associations['it-IT'] === $it, 'native editor loads current associations');
$model = $factory->createModel('Course', 'Administrator', ['ignore_request'=>true]);
$model->setState('course.id', $en);
$form = $model->getForm();
assocExpect((bool) $form->getField('it-IT', 'associations'), 'native language-specific association form field loaded');
assocExpect(str_contains($form->getInput('it-IT', 'associations'), 'association-it'), 'native field renders editable language-filtered course title');
$baseline = assocRows();
foreach ([['it-IT'=>$otherEn], ['en-GB'=>$otherEn], ['not-a-language'=>$it], ['it-IT'=>-1], ['it-IT'=>'1 OR 1=1'], ['it-IT'=>999999999], ['it-IT'=>$en]] as $invalid) {
    assocExpect(!assocSave(assocData($en) + ['associations'=>$invalid]) && assocRows() === $baseline, 'invalid target/language rejected atomically');
}
assocExpect(!assocSave(assocData($all) + ['associations'=>['it-IT'=>$it]]) && assocRows() === $baseline, 'all-languages course cannot join a translation group');
$changed = assocData($en); $changed['language'] = 'it-IT';
assocExpect(!assocSave($changed) && assocRows() === $baseline, 'associated source language cannot change behind native graph');
assocExpect(assocSave(assocData($otherEn) + ['associations'=>['it-IT'=>$otherIt]]), 'second independent group created');
$baseline = assocRows();
assocExpect(!assocSave(assocData($en) + ['associations'=>['it-IT'=>$otherIt]]) && assocRows() === $baseline, 'member cannot be stolen from another group');
$update = static function (int $id, array $fields) use ($db): void {
    $record = (object) (['id'=>$id] + $fields); $db->updateObject('#__jlms_courses', $record, 'id', true);
};
$update($it, ['checked_out'=>999999]);
assocExpect(!assocSave(assocData($en) + ['associations'=>['it-IT'=>0]]) && assocRows() === $baseline, 'removing another editor checkout denied');
$update($it, ['checked_out'=>null]);
// A former super-user object must not retain permissions after a current account block.
$blocked = (object) ['id'=>(int)$actor->id, 'block'=>1]; $db->updateObject('#__users', $blocked, 'id');
try {
    assocExpect(!assocSave(assocData($en) + ['associations'=>['it-IT'=>0]]) && assocRows() === $baseline, 'fresh blocked actor denied before graph mutation');
} finally { $blocked->block=0; $db->updateObject('#__users', $blocked, 'id'); }
// Deny the target asset even when the actor can edit the source course.
$restricted = new \Joomla\CMS\User\User();
$userData = ['name'=>'Association editor', 'username'=>'association-editor', 'email'=>'association-editor@example.invalid',
    'password'=>'Ci-only-Association-Password-2026','password2'=>'Ci-only-Association-Password-2026','groups'=>[2,6],'block'=>0,'activation'=>''];
if (!$restricted->bind($userData) || !$restricted->save()) { throw new \RuntimeException('Editor fixture failed'); }
$assetId = (int) $db->setQuery('SELECT asset_id FROM #__jlms_courses WHERE id=' . $it)->loadResult();
$oldRules = $db->setQuery('SELECT rules FROM #__assets WHERE id=' . $assetId)->loadResult();
$asset = (object) ['id'=>$assetId, 'rules'=>'{"core.edit":{"6":false},"core.edit.own":{"6":false}}'];
$db->updateObject('#__assets', $asset, 'id');
$app->loadIdentity(new \Joomla\CMS\User\User((int)$restricted->id));
try {
    assocExpect(!assocSave(assocData($en) + ['associations'=>['it-IT'=>0]]) && assocRows() === $baseline, 'removing a linked target requires target ACL too');
} finally { $asset->rules=$oldRules; $db->updateObject('#__assets',$asset,'id'); $app->loadIdentity($actor); }
assocExpect(assocSave(assocData($otherEn) + ['associations'=>['it-IT'=>0]]), 'native zero selection clears whole old group');
assocExpect(!$db->setQuery("SELECT COUNT(*) FROM #__associations WHERE context='com_jlms.item' AND id IN ($otherEn,$otherIt)")->loadResult(), 'no dangling old group after clear');
// Two independent native model requests compete for the same unassociated target.
$raceEn1 = $create('association-race-en1', 'en-GB');
$raceEn2 = $create('association-race-en2', 'en-GB');
$raceIt = $create('association-race-it', 'it-IT');
$workers = [];
foreach ([$raceEn1,$raceEn2] as $source) {
    $pipes = [];
    $process = proc_open([PHP_BINARY,__FILE__,$root,'worker',json_encode(['source'=>$source,'target'=>$raceIt],JSON_THROW_ON_ERROR)],
        [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
    if (!is_resource($process)) { throw new \RuntimeException('Association worker failed to start'); }
    $workers[] = [$process,$pipes];
}
foreach ($workers as [$process,$pipes]) {
    stream_set_timeout($pipes[1],30);
    $ready = trim((string) fgets($pipes[1]));
    if ($ready !== 'ready') { throw new \RuntimeException('Association worker bootstrap: '.$ready.' '.stream_get_contents($pipes[2])); }
}
$db->transactionStart();
$db->setQuery("SELECT id FROM #__assets WHERE name='com_jlms' FOR UPDATE")->loadResult();
foreach ($workers as [$process,$pipes]) { fwrite($pipes[0],"go\n"); fclose($pipes[0]); }
usleep(150000); $db->transactionCommit();
$outcomes = [];
foreach ($workers as [$process,$pipes]) {
    $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($process) !== 0) { throw new \RuntimeException('Association worker failed: '.$err.$out); }
    $outcomes[] = json_decode($out,true,16,JSON_THROW_ON_ERROR)['saved'];
}
assocExpect(count(array_filter($outcomes)) === 1, 'concurrent groups cannot both claim one target');
$raceMembers = $db->setQuery("SELECT b.id FROM #__associations a JOIN #__associations b ON b.`key`=a.`key` AND b.context=a.context WHERE a.id=$raceIt AND a.context='com_jlms.item'")->loadColumn();
assocExpect(count($raceMembers) === 2 && count(array_intersect([$raceEn1,$raceEn2],array_map('intval',$raceMembers))) === 1, 'race leaves exactly one coherent native translation pair');
// Freeze and publish both courses through the actual native workflow.
$versions = [];
foreach ([$en,$it,$all] as $course) {
    $revision = (int) assocData($course)['revision'];
    $authoring = $component->getCurriculumService();
    $version = $authoring->createDraft($actor, $course, $revision);
    $scope = static function () use ($db,$version,$course): array {
        return ['course_id'=>$course,'version_id'=>$version,'expected_version_revision'=>(int)$db->setQuery('SELECT revision FROM #__jlms_course_versions WHERE id='.$version)->loadResult()];
    };
    $module = $authoring->saveModule($actor, $scope()+['id'=>0,'title'=>'Translated chapter']);
    $authoring->saveLesson($actor, $scope()+['id'=>0,'module_id'=>$module,'title'=>'Translated lesson','kind'=>'text','required'=>'1','body'=>'<p>PRIVATE_TRANSLATION_BODY</p>']);
    $publication = $component->getPublicationService();
    $choice = $publication->transitions($actor,$course);
    $publication->transition($actor,$course,$version,(int)assocData($course)['revision'],$scope()['expected_version_revision'],(int)$choice[0]->id);
    $versions[$course] = $version;
}
$app->loadIdentity(new \Joomla\CMS\User\User());
$links = $helper->getAssociationsForItem($en, 'course');
assocExpect(($links['it-IT'] ?? '') === 'index.php?option=com_jlms&view=course&id='.$it.'&lang=it', 'native language switch resolves published course sheet');
assocExpect($helper->getAssociationsForItem($en,'classroom') === [] && $helper->getAssociationsForItem($en,'quiz') === [], 'no attempt/enrollment or learning progress translation');
$update($it,['catalog_access'=>2]);
assocExpect($helper->getAssociationsForItem($en,'course') === [], 'target visibility rechecked without stale association cache');
$update($it,['catalog_access'=>1,'state'=>0]);
assocExpect($helper->getAssociationsForItem($en,'course') === [], 'unpublished target omitted');
$update($it,['state'=>1,'publish_up'=>'2099-01-01 00:00:00']);
assocExpect($helper->getAssociationsForItem($en,'course') === [], 'future target omitted');
$update($it,['publish_up'=>null]);
$category = (object) ['id'=>$catid,'published'=>0]; $db->updateObject('#__categories',$category,'id');
assocExpect($helper->getAssociationsForItem($en,'course') === [], 'hidden source category cannot expose association');
$category->published=1; $db->updateObject('#__categories',$category,'id');
assocExpect(isset($helper->getAssociationsForItem($en,'course')['it-IT']), 'restored category visibility reflected immediately');
$app->loadIdentity($actor);
$baseline = assocRows();
assocExpect(!assocSave(assocData($en)+['associations'=>['it-IT'=>0]]) && assocRows() === $baseline, 'published translation group immutable in draft editor');
file_put_contents($fixturePath,json_encode(['en'=>$en,'it'=>$it,'other_en'=>$otherEn,'other_it'=>$otherIt,'all'=>$all,
    'version'=>$versions[$en],'catid'=>$catid,'rows'=>$baseline],JSON_THROW_ON_ERROR));
echo json_encode(['native_association_checks'=>$checks,'status'=>'passed'],JSON_THROW_ON_ERROR).PHP_EOL;
