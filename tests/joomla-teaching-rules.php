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



$actor = $app->getIdentity();
$authoring = $component->getCurriculumService();
$publication = $component->getPublicationService();
$learning = $component->getLearningService();
$checks = 0;
function ruleExpect(bool $ok,string $message):void{global $checks;
if (!$ok)throw new RuntimeException($message);
$checks++;
}
function ruleDenied(callable $work,string $key):void{try {
$work();

} catch (DomainException $error) {
ruleExpect($error->getMessage() === $key, 'Expected ' . $key . ', got ' . $error->getMessage());
return;

}throw new RuntimeException('Expected ' . $key);
}
function ruleRow(string $table,int $id):object{global $db;
return $db->setQuery('SELECT * FROM #__jlms_' . $table . ' WHERE id=' . $id)->loadObject();
}
function ruleLearning(?int $course=null):array{global $db;
if ($course !== null) {
return [$db->setQuery('SELECT * FROM #__jlms_enrollments WHERE course_id=' . $course . ' ORDER BY id')->loadAssocList(),$db->setQuery('SELECT a.* FROM #__jlms_attempts a JOIN #__jlms_enrollments e ON e.id=a.enrollment_id WHERE e.course_id=' . $course . ' ORDER BY a.id')->loadAssocList(),$db->setQuery('SELECT p.* FROM #__jlms_lesson_progress p JOIN #__jlms_enrollments e ON e.id=p.enrollment_id WHERE e.course_id=' . $course . ' ORDER BY p.enrollment_id,p.lesson_id')->loadAssocList()];

}return [$db->setQuery('SELECT * FROM #__jlms_enrollments ORDER BY id')->loadAssocList(),$db->setQuery('SELECT * FROM #__jlms_attempts ORDER BY id')->loadAssocList(),$db->setQuery('SELECT * FROM #__jlms_lesson_progress ORDER BY enrollment_id,lesson_id')->loadAssocList()];
}
$fixture = dirname($root) . '/teaching-rules-fixture.json';
$mode = $argv[2] ?? '';

if (in_array($mode, ['asset-checkpoint','asset-retained'], true)) {
 // File backup is independent from the SQL-only snapshots. A ready PDF must
 // survive the loss and restoration of its guarded encrypted storage blob.
 $pdf = $db->setQuery("SELECT d.id AS document_id,c.enrollment_id,a.* FROM #__jlms_certificate_documents d " .
     "JOIN #__jlms_certificates c ON c.id=d.certificate_id " .
     "JOIN #__jlms_assets a ON a.id=d.pdf_asset_id " .
     "WHERE d.status='ready' AND a.mime_type='application/pdf' AND a.storage='local' " .
     "AND a.storage_key LIKE '%.php' " .
     "ORDER BY d.id LIMIT 1")->loadObject();
 ruleExpect($pdf !== null, 'At least one real rendered certificate PDF exists');
 $certificateParams = \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms');
 $certificateParams->set('material_storage_root', '');
 $storage = new \Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedMaterialStorage(
     static fn()=>$certificateParams);
 $path = $storage->root() . DIRECTORY_SEPARATOR . $pdf->storage_key;
 ruleExpect(preg_match('/\A[a-f0-9]{64}\.php\z/D', (string)$pdf->storage_key) === 1
     && is_file($path) && !is_link($path), 'PDF is a managed opaque regular file');
 $manifest = dirname($root) . '/aut04-pdf-file-manifest.json';
 if ($mode === 'asset-checkpoint') {
     $handle = $storage->open($pdf);
     try {
$payload = stream_get_contents($handle);

     } finally {
fclose($handle);
     }
     ruleExpect(is_string($payload) && str_starts_with($payload, '%PDF-'), 'Encrypted asset can be opened as PDF before backup');
     $data = ['key' => $pdf->storage_key,'on_disk_sha256' => hash_file('sha256', $path),
       'plaintext_sha256' => hash('sha256', $payload),'asset_id' => (int)$pdf->id,
       'document_id' => (int)$pdf->document_id,'enrollment_id' => (int)$pdf->enrollment_id];
     file_put_contents($manifest, json_encode($data, JSON_THROW_ON_ERROR));
 } else {
     $saved = json_decode(file_get_contents($manifest), true, 32, JSON_THROW_ON_ERROR);
     ruleExpect(hash_equals($saved['key'], (string)$pdf->storage_key) &&
         $saved['asset_id'] === (int)$pdf->id && $saved['document_id'] === (int)$pdf->document_id,
         'Restored SQL references the identical historical document and asset');
     ruleExpect(hash_equals($saved['on_disk_sha256'], hash_file('sha256', $path)),
         'Restored protected PDF file bytes match the original backup');
     $handle = $storage->open($pdf);
     try {
$payload = stream_get_contents($handle);

     } finally {
fclose($handle);
     }
     ruleExpect(is_string($payload) && str_starts_with($payload, '%PDF-')
         && hash_equals($saved['plaintext_sha256'], hash('sha256', $payload)),
         'Restored encrypted PDF decrypts to the original certificate');
     $download = $component->getCertificateService()->download($actor, (int)$pdf->enrollment_id);
     try {
$downloaded = stream_get_contents($download['handle']);

     } finally {
fclose($download['handle']);
     }
     ruleExpect(is_string($downloaded) && hash_equals($saved['plaintext_sha256'], hash('sha256', $downloaded)),
         'Historical certificate remains downloadable after SQL and file recovery');
 }
 echo json_encode(['aut04_certificate_file_checks' => $checks,'status' => 'passed']) . PHP_EOL;
 return;
}

if (in_array($mode, ['state','checkpoint','retained'], true)) {
 $f = json_decode(file_get_contents($fixture), true, 32, JSON_THROW_ON_ERROR);
 $state = ['course' => (array)ruleRow('courses', $f['course']),'version' => (array)ruleRow('course_versions', $f['version']),'original' => (array)ruleRow('course_versions', $f['original']),'learning' => ruleLearning($f['course'])];
 if ($mode === 'checkpoint') {
file_put_contents($fixture . '.snapshot', json_encode($state, JSON_THROW_ON_ERROR));
return;
 }
 if ($mode === 'retained') {
ruleExpect(json_decode(file_get_contents($fixture . '.snapshot'), true, 64, JSON_THROW_ON_ERROR) === $state, 'Teaching rules and assigned history survive upgrade');
echo json_encode(['teaching_rules_retained_checks' => $checks,'status' => 'passed']) . PHP_EOL;
return;
 }
 echo json_encode($state, JSON_THROW_ON_ERROR);
return;
}
\Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->set('video_external_policy', 'embed');
$source = (int)$db->setQuery("SELECT id FROM #__jlms_courses WHERE alias='video-fixture'")->loadResult();
$sourceCourse = ruleRow('courses', $source);
$sourceVersion = ruleRow('course_versions', (int)$sourceCourse->current_version_id);
$model = $factory->createModel('Course', 'Administrator', ['ignore_request' => true]);
$course = $model->duplicate(['course_id' => $source,'version_id' => $sourceVersion->id,'revision' => $sourceCourse->revision,'expected_version_revision' => $sourceVersion->revision,'title' => 'Teaching rules fixture']);
$item = $model->getItem($course);
ruleExpect($model->save(['id' => $course,'revision' => $item->revision,'catalog_access' => 1,'content_access' => 1,'enrollment_mode' => 'auto']), 'Independent rules fixture has explicit learner access');
$version = (int)$authoring->outline($actor, $course)['version']->id;
$publish = static function(int $id)use($publication,$actor,$course):void{$transition = (int)$publication->transitions($actor, $course, $id)[0]->id;
$impact = $publication->updateImpact($actor, $course, $id, $transition);
$publication->transition($actor, $course, $id, (int)ruleRow('courses', $course)->revision, (int)ruleRow('course_versions', $id)->revision, $transition, $impact['fingerprint'] ?? null);

};
ruleExpect($authoring->teachingRules($actor, $course, $version)['video_threshold_basis_points'] === 9000, 'Default is90 percent');
$publish($version);
$original = $version;
$oldLesson = (int)$db->setQuery('SELECT id FROM #__jlms_lessons WHERE course_version_id=' . $original . " AND kind='video' ORDER BY id")->loadResult();
$oldEnrollment = $learning->start($actor, 'en-GB', $course, bin2hex(random_bytes(20)));
$old = ruleRow('course_versions', $original);
// Explicit consultation mode permits an optional-only programme without inventing completion credit.
$consultationCourse = $model->duplicate(['course_id' => $source,'version_id' => $sourceVersion->id,'revision' => $sourceCourse->revision,'expected_version_revision' => $sourceVersion->revision,'title' => 'Consultation rules fixture']);
$consultationItem = $model->getItem($consultationCourse);
ruleExpect($model->save(['id' => $consultationCourse,'revision' => $consultationItem->revision,'catalog_access' => 1,'content_access' => 1,'enrollment_mode' => 'auto']), 'Consultation fixture has valid learner access');
$consultationVersion = (int)$authoring->outline($actor, $consultationCourse)['version']->id;
$db->setQuery('UPDATE #__jlms_lessons SET required=0 WHERE course_version_id=' . (int)$consultationVersion)->execute();
$db->setQuery("UPDATE #__jlms_lessons SET duration_ms=NULL, params_json='{}' WHERE course_version_id=" . (int)$consultationVersion . " AND kind='video'")->execute();
$authoring->saveTeachingRules($actor, ['course_id' => $consultationCourse,'version_id' => $consultationVersion,'expected_version_revision' => ruleRow('course_versions', $consultationVersion)->revision,'mode' => 'consultation','sequence_mode' => 'free','video_threshold' => '90']);
$consultationOutline = $authoring->outline($actor, $consultationCourse, $consultationVersion);
$consultationReadiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(ruleRow('courses', $consultationCourse), $consultationOutline['modules'], $consultationOutline['lessons'], [], false, $authoring->teachingRules($actor, $consultationCourse, $consultationVersion));
ruleExpect(!in_array('COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED', $consultationReadiness['blockers'], true), 'Consultation video can be published without tracking-only duration metadata');
$consultationTransition = (int)$publication->transitions($actor, $consultationCourse, $consultationVersion)[0]->id;
$publication->transition($actor, $consultationCourse, $consultationVersion, (int)ruleRow('courses', $consultationCourse)->revision, (int)ruleRow('course_versions', $consultationVersion)->revision, $consultationTransition);
$consultationEnrollment = $learning->start($actor, 'en-GB', $consultationCourse, bin2hex(random_bytes(20)));
$consultationLesson = (int)$db->setQuery('SELECT id FROM #__jlms_lessons WHERE course_version_id=' . (int)$consultationVersion . ' ORDER BY ordering,id')->loadResult();
$consultationClassroom = $learning->classroom($actor, 'en-GB', $consultationEnrollment, $consultationLesson);
ruleExpect($consultationClassroom['progress_applicable'] === false && $consultationClassroom['percent'] === null && $consultationClassroom['completed_at'] === null, 'Consultation enrollment exposes no progress or course completion');
try {
$learning->complete($actor, 'en-GB', $consultationEnrollment, $consultationLesson, bin2hex(random_bytes(20)));
throw new RuntimeException('Consultation must not grant lesson completion');

} catch (RuntimeException $error) {
ruleExpect($error->getMessage() === 'COM_JLMS_CONSULTATION_COMPLETION_UNAVAILABLE', 'Consultation lesson completion is explicitly denied');
}
ruleExpect((int)ruleRow('enrollments', $consultationEnrollment)->required_total === 0 && (int)ruleRow('enrollments', $consultationEnrollment)->completed_total === 0, 'Consultation enrollment stores zero completion requirements and credit');
$version = $authoring->createDraft($actor, $course, (int)ruleRow('courses', $course)->revision);
$courseBefore = ruleRow('courses', $course);
$before = ruleLearning();
$input = ['course_id' => $course,'version_id' => $version,'expected_version_revision' => ruleRow('course_versions', $version)->revision,'video_threshold' => '50.25'];
ruleDenied(fn()=>$authoring->saveTeachingRules(new \Joomla\CMS\User\User(0), $input), 'JERROR_ALERTNOAUTHOR');
ruleDenied(fn()=>$authoring->saveTeachingRules($actor, array_replace($input, ['version_id' => $sourceVersion->id,'expected_version_revision' => $sourceVersion->revision])), 'COM_JLMS_RESOURCE_NOT_FOUND');
ruleDenied(fn()=>$authoring->saveTeachingRules($actor, array_replace($input, ['version_id' => $original,'expected_version_revision' => $old->revision])), 'COM_JLMS_VERSION_IMMUTABLE');
ruleDenied(fn()=>$authoring->saveTeachingRules($actor, array_replace($input, ['video_threshold' => '0'])), 'COM_JLMS_TEACHING_THRESHOLD_INVALID');
$other = (int)$db->setQuery("SELECT id FROM #__users WHERE username='cilearner'")->loadResult();
foreach ([['courses',$course],['course_versions',$version]] as [$table,$id]) {
$oldCheckout = ruleRow($table, $id)->checked_out;
$locked = (object)['id' => $id,'checked_out' => $other];
$db->updateObject('#__jlms_' . $table, $locked, 'id', true);
try {
ruleDenied(fn()=>$authoring->saveTeachingRules($actor, $input), 'JERROR_ALERTNOAUTHOR');

} finally {
$locked->checked_out = $oldCheckout;
$db->updateObject('#__jlms_' . $table, $locked, 'id', true);
}
}
$authoring->saveTeachingRules($actor, $input + ['mode' => 'consultation','certificate_enabled' => true,'rules_json' => '{}']);
$rules = $authoring->teachingRules($actor, $course, $version);
ruleExpect($rules['video_threshold_basis_points'] === 5025 && $rules['mode'] === 'consultation' && $rules['certificate_enabled'] === false, 'Supported mode and threshold fields change; unsupported fields are ignored');
ruleExpect((int)ruleRow('course_versions', $version)->revision === (int)$input['expected_version_revision'] + 1, 'Rule save increments version CAS');
ruleExpect(ruleRow('courses', $course) == $courseBefore && ruleRow('course_versions', $original) == $old && ruleLearning() === $before, 'Draft rule change preserves metadata, frozen source and all learning');
ruleDenied(fn()=>$authoring->saveTeachingRules($actor, $input), 'COM_JLMS_REVISION_CONFLICT');
$t = (int)$publication->transitions($actor, $course, $version)[0]->id;
$review = $publication->updateImpact($actor, $course, $version, $t);
ruleExpect($review['rules_changed'], 'Publication review recognises teaching rule changes');
ruleExpect($review['video_threshold_change'] === ['before' => 9000,'after' => 5025], 'Review exposes exact before/after threshold');
$authoring->saveTeachingRules($actor, array_replace($input, ['expected_version_revision' => ruleRow('course_versions', $version)->revision,'mode' => 'learning','video_threshold' => '50.26']));
ruleDenied(fn()=>$publication->transition($actor, $course, $version, (int)ruleRow('courses', $course)->revision, (int)ruleRow('course_versions', $version)->revision, $t, $review['fingerprint']), 'COM_JLMS_PUBLICATION_REVIEW_STALE');
$authoring->saveTeachingRules($actor, array_replace($input, ['expected_version_revision' => ruleRow('course_versions', $version)->revision]));
$valid = ruleRow('course_versions', $version);
$invalid = (object)['id' => $version,'rules_json' => json_encode(array_replace($rules, ['video_threshold_basis_points' => 10001]), JSON_THROW_ON_ERROR)];
$db->updateObject('#__jlms_course_versions', $invalid, 'id');
ruleDenied(fn()=>$publish($version), 'COM_JLMS_PUBLICATION_UNSUPPORTED');
ruleExpect((int)ruleRow('courses', $course)->current_version_id === $original && ruleLearning() === $before, 'Invalid frozen threshold cannot be published');
$invalid->rules_json = $valid->rules_json;
$db->updateObject('#__jlms_course_versions', $invalid, 'id');
$publish($version);
$newEnrollment = $learning->start($actor, 'en-GB', $course, bin2hex(random_bytes(20)));
$newLesson = (int)$db->setQuery('SELECT id FROM #__jlms_lessons WHERE course_version_id=' . $version . " AND kind='video' ORDER BY id")->loadResult();
ruleExpect((int)ruleRow('enrollments', $oldEnrollment)->course_version_id === $original && (int)ruleRow('enrollments', $newEnrollment)->course_version_id === $version, 'New admissions use updated rules while historical assignments remain');
$now = (int)floor(microtime(true) * 1000);
$video = new \Jlms\Component\Jlms\Administrator\Application\VideoProgressService($db, $learning, $authoring, static function()use(&$now):int{return $now;

});
$event = static fn(int $id,int $p,int $time,string $type='position'):array=>['id' => $id,'type' => $type,'position_ms' => $p,'client_ms' => $time,'rate_milli' => 1000,'playing' => true];
$oldGrant = $video->playback($actor, 'en-GB', $oldEnrollment, $oldLesson, true);
$newGrant = $video->playback($actor, 'en-GB', $newEnrollment, $newLesson, true);
$now += 5000;
$batch = json_encode([$event(1, 0, 0, 'play'),$event(2, 5000, 5000)], JSON_THROW_ON_ERROR);
$oldResult = $video->save($actor, 'en-GB', $oldEnrollment, $oldLesson, $oldGrant['session_id'], $oldGrant['source_revision'], 1, 10000, $batch);
$newResult = $video->save($actor, 'en-GB', $newEnrollment, $newLesson, $newGrant['session_id'], $newGrant['source_revision'], 1, 10000, $batch);
ruleExpect(!$oldResult['lesson_completed'] && !$newResult['lesson_completed'], '5000ms cannot round up to50.25 percent');
$now += 25;
$batch = json_encode([$event(3, 5025, 5025)], JSON_THROW_ON_ERROR);
$newResult = $video->save($actor, 'en-GB', $newEnrollment, $newLesson, $newGrant['session_id'], $newGrant['source_revision'], 2, 10000, $batch);
$oldResult = $video->save($actor, 'en-GB', $oldEnrollment, $oldLesson, $oldGrant['session_id'], $oldGrant['source_revision'], 2, 10000, $batch);
ruleExpect($newResult['lesson_completed'] && $newResult['course_completed'] && !$oldResult['lesson_completed'], 'Exact configured threshold applies only to its assigned version');
$now += 3975;
$oldResult = $video->save($actor, 'en-GB', $oldEnrollment, $oldLesson, $oldGrant['session_id'], $oldGrant['source_revision'], 3, 10000, json_encode([$event(4, 9000, 9000)], JSON_THROW_ON_ERROR));
ruleExpect($oldResult['lesson_completed'] && $oldResult['course_completed'], 'Existing student still completes at original90percent');

$certificateParams = \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms');
$previousCertificateSetting = $certificateParams->get('certificate_enabled', 0);
$certificateParams->set('certificate_enabled', 1);
$previousMaterialRoot = $certificateParams->get('material_storage_root', '');
$certificateParams->set('material_storage_root', '');

$certificateService = $component->getCertificateService();
$historicRules = ruleRow('course_versions', $original)->rules_json;
ruleExpect(\Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode($historicRules)['certificate_enabled'] === false, 'Historical published fixture originally excluded certificates');
// Explicitly exercise SQL DATETIME(6) from real completion history, which must
// remain renderable without overwriting the immutable certificate snapshot.
$completedSql = (string)ruleRow('enrollments', $oldEnrollment)->completed_at;
$microsecondDate = substr($completedSql, 0, 19) . '.123456';
$db->setQuery('UPDATE #__jlms_enrollments SET completed_at=' . $db->quote($microsecondDate) . ' WHERE id=' . (int)$oldEnrollment)->execute();
$certificateService->adminRecoverHistorical($actor, $oldEnrollment, 'Administrator approved historical certificate recovery');
$recovered = (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_certificates WHERE enrollment_id=' . (int)$oldEnrollment)->loadResult();
ruleExpect($recovered === 1, 'Completed historical enrollment receives exactly one certificate');
$recoveryAudit = (int)$db->setQuery("SELECT COUNT(*) FROM #__jlms_certificate_audit WHERE action='historical_recovery' AND certificate_id IN (SELECT id FROM #__jlms_certificates WHERE enrollment_id=" . (int)$oldEnrollment . ')')->loadResult();
ruleExpect($recoveryAudit === 1, 'Historical recovery records a durable audit entry');

$historicalDocument = $db->setQuery('SELECT d.id, d.status FROM #__jlms_certificate_documents AS d JOIN #__jlms_certificates AS c ON c.current_document_id=d.id WHERE c.enrollment_id=' . (int)$oldEnrollment)->loadObject();
ruleExpect($historicalDocument->status === 'pending_render', 'Recovered historical certificate is queued for on-demand PDF rendering');
$certificateDownload = $certificateService->download($actor, $oldEnrollment);
try {
ruleExpect($certificateDownload['mime'] === 'application/pdf' && $certificateDownload['size'] > 100
    && str_starts_with((string)fread($certificateDownload['handle'], 8), '%PDF-'),
    'Historical certificate PDF renders and downloads from SQL DATETIME(6)');
} finally {
fclose($certificateDownload['handle']);
}
$renderedDocument = ruleRow('certificate_documents', (int)$historicalDocument->id);
ruleExpect($renderedDocument->status === 'ready' && (int)$renderedDocument->pdf_asset_id > 0,
    'Recovered historical certificate is persisted ready after first download');

$storedAsset = ruleRow('assets', (int)$renderedDocument->pdf_asset_id);
ruleExpect(preg_match('/\\A[a-f0-9]{64}\\.php\\z/D', (string)$storedAsset->storage_key) === 1,
    'Shared-hosting storage uses an opaque guarded PHP filename by default');
$storageRoot = (new \Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedMaterialStorage(static fn()=>$certificateParams))->root();
$rawAsset = file_get_contents($storageRoot . DIRECTORY_SEPARATOR . $storedAsset->storage_key);
ruleExpect(is_string($rawAsset) && str_starts_with($rawAsset, '<?php http_response_code(404); exit; __halt_compiler(); ?>')
    && !str_contains($rawAsset, '%PDF-'),
    'Managed asset is guarded and encrypted, never a publicly readable PDF');
ruleExpect(is_file($storageRoot . '/.htaccess') && is_file($storageRoot . '/index.php'),
    'Managed private storage provisions native server access guards');
$again = $certificateService->download($actor, $oldEnrollment);
try {
ruleExpect($again['mime'] === 'application/pdf' && $again['size'] === $certificateDownload['size'],
    'Historical certificate is repeatably downloadable without re-render');
} finally {
fclose($again['handle']);
}
ruleExpect((int)ruleRow('certificate_documents', (int)$historicalDocument->id)->pdf_asset_id === (int)$renderedDocument->pdf_asset_id,
    'Repeated download preserves the original private PDF asset');

$serviceSource = file_get_contents(JPATH_ADMINISTRATOR . '/components/com_jlms/src/Application/CertificateService.php');
$sourceTokens = token_get_all((string) $serviceSource);
$temporaryCalls = array_filter($sourceTokens, static fn($token):bool => is_array($token)
    && $token[0] === T_STRING && in_array(strtolower($token[1]), ['tempnam', 'sys_get_temp_dir'], true));
ruleExpect(is_string($serviceSource) && $temporaryCalls === [],
    'Certificate creation and regeneration do not depend on global PHP temporary paths');
$storageForCertificates = new \Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedMaterialStorage(static fn()=>$certificateParams);
try {
$storageForCertificates->stageGeneratedPdf('invalid PDF');
throw new \RuntimeException('Invalid generated PDF accepted');
} catch (\RuntimeException $error) {
ruleExpect($error->getMessage() === 'COM_JLMS_CERTIFICATE_RENDER_FAILED', 'Generated PDF content is strictly validated before storage');
}


ruleExpect(ruleRow('course_versions', $original)->rules_json === $historicRules, 'Historical recovery never mutates the published rules');
try {
$certificateService->adminRecoverHistorical($actor, $oldEnrollment, 'Duplicate historical certificate recovery');
throw new \RuntimeException('Duplicate certificate was issued');
} catch (\RuntimeException $error) {
ruleExpect($error->getMessage() === 'COM_JLMS_CERTIFICATE_UNAVAILABLE' && $error->getCode() === 409, 'Duplicate historical issuance is rejected with HTTP 409');
}
// Disposable integration fixture: simulate a completed enrollment whose published
// snapshot requires a certificate but whose issuance event was missed.
// All rules are restored after exercising recovery; no production backfill occurs.
$eligibleRulesOriginal = (string)ruleRow('course_versions', $version)->rules_json;
$eligibleRules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode($eligibleRulesOriginal);
$eligibleRules['mode'] = 'learning';
$eligibleRules['certificate_enabled'] = true;
$eligibleRulesJson = json_encode($eligibleRules, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
$db->setQuery('UPDATE #__jlms_course_versions SET rules_json=' . $db->quote($eligibleRulesJson) . ' WHERE id=' . (int)$version)->execute();
try {
    ruleExpect(ruleRow('enrollments', $newEnrollment)->completed_at !== null,
        'Eligible issuance recovery fixture has a persisted completion');
    ruleExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_certificates WHERE enrollment_id=' . (int)$newEnrollment)->loadResult() === 0,
        'Eligible completion has no existing certificate before recovery');
    $certificateService->adminRecoverMissing($actor, $newEnrollment, 'Administrative recovery of missed eligible issuance');
    $eligibleCertificate = $db->setQuery('SELECT c.id, c.current_document_id, c.issued_at FROM #__jlms_certificates AS c WHERE c.enrollment_id=' . (int)$newEnrollment)->loadObject();
    ruleExpect($eligibleCertificate && (int)$eligibleCertificate->current_document_id > 0
        && (string)$eligibleCertificate->issued_at === (string)ruleRow('enrollments', $newEnrollment)->completed_at,
        'Eligible recovery preserves completion timestamp and issues exactly one certificate');
    ruleExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__jlms_certificate_audit WHERE action='missing_issuance_recovery' AND certificate_id=" . (int)$eligibleCertificate->id)->loadResult() === 1,
        'Eligible recovery creates one durable audit entry');
    try {
$certificateService->adminRecoverMissing($actor, $newEnrollment, 'Attempt duplicate eligible issuance');
        throw new \RuntimeException('Duplicate eligible issuance accepted');
    } catch (\RuntimeException $error) {
        ruleExpect($error->getMessage() === 'COM_JLMS_CERTIFICATE_UNAVAILABLE' && $error->getCode() === 409,
            'Duplicate eligible issuance rejected without new documents');
    }
    ruleExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_certificates WHERE enrollment_id=' . (int)$newEnrollment)->loadResult() === 1,
        'Eligible recovery is idempotent for existing issuance');
} finally {
    $db->setQuery('UPDATE #__jlms_course_versions SET rules_json=' . $db->quote($eligibleRulesOriginal) . ' WHERE id=' . (int)$version)->execute();
}

$certificateParams->set('material_storage_root', $previousMaterialRoot);
$certificateParams->set('certificate_enabled', $previousCertificateSetting);

$consultationVideo = (int)$db->setQuery('SELECT id FROM #__jlms_lessons WHERE course_version_id=' . (int)$consultationVersion . " AND kind='video' ORDER BY id")->loadResult();
$progressBeforeConsultationPlayback = (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_lesson_progress WHERE lesson_id=' . (int)$consultationVideo)->loadResult();
$sessionsBeforeConsultationPlayback = (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_progress_sessions WHERE lesson_id=' . (int)$consultationVideo)->loadResult();
$consultationGrant = $video->playback($actor, 'en-GB', $consultationEnrollment, $consultationVideo, true);
ruleExpect($consultationGrant['tracking'] === false && $consultationGrant['session_id'] === null && $consultationGrant['duration_ms'] === null, 'Consultation video receives a playback descriptor without telemetry requirements');
ruleExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_lesson_progress WHERE lesson_id=' . (int)$consultationVideo)->loadResult() === $progressBeforeConsultationPlayback
    && (int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_progress_sessions WHERE lesson_id=' . (int)$consultationVideo)->loadResult() === $sessionsBeforeConsultationPlayback, 'Consultation video playback creates no progress rows or telemetry sessions');
$version = $authoring->createDraft($actor, $course, (int)ruleRow('courses', $course)->revision);
file_put_contents($fixture, json_encode(['course' => $course,'version' => $version,'original' => $original], JSON_THROW_ON_ERROR));
echo json_encode(['teaching_rules_native_checks' => $checks,'status' => 'passed']) . PHP_EOL;
