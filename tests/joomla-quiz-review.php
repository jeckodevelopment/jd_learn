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




$checks=0;
function reviewExpect(bool $ok,string $message):void{global$checks;if(!$ok)throw new \RuntimeException($message);$checks++;}
function reviewReject(callable $work,int $code,string $message):void{try{$work();}catch(\RuntimeException $error){reviewExpect($error->getCode()===$code,$message.': '.$error->getMessage().' / '.$error->getCode());return;}throw new \RuntimeException($message);}
function reviewKey():string{$s=bin2hex(random_bytes(16));return substr($s,0,8).'-'.substr($s,8,4).'-4'.substr($s,13,3).'-a'.substr($s,17,3).'-'.substr($s,20);}
function reviewAnswers(array $a):string{$r=[];foreach($a['questions']as$q)$r[$q['id']]=$q['kind']==='multiple'?['first','second']:['first'];return json_encode($r,JSON_THROW_ON_ERROR);}
function reviewUser(string $name,int $group):\Joomla\CMS\User\User{
 $u=new \Joomla\CMS\User\User();$d=['name'=>'CI '.$name,'username'=>$name,'email'=>$name.'@example.invalid','password'=>'Ci-only-Learner-Password-2026','password2'=>'Ci-only-Learner-Password-2026','groups'=>[2,$group],'block'=>0,'activation'=>''];
 if(!$u->bind($d)||!$u->save())throw new \RuntimeException((string)$u->getError());return new \Joomla\CMS\User\User((int)$u->id);
}
function reviewCommand(array $item,string $reason='PRIVATE_CANCEL_REASON',?string $key=null):array{
 $p=$item['impact'];return [$item['course_id'],$item['lesson_id'],(int)$item['enrollment']->id,(int)$item['selected']->id,$p['enrollment_revision'],$p['attempt_revision'],$item['refund'],$reason,$key??reviewKey(),$p['hash'],true];
}
$actor=$app->getIdentity();$review=$component->getQuizReviewService();$learning=$component->getLearningService();$now=time();
$runtime=new \Jlms\Component\Jlms\Administrator\Application\QuizAttemptService($db,$learning,$component->getQuizSnapshotService(),static function()use(&$now):int{return $now;},$component->getQuizTimeService());
$f=json_decode(file_get_contents(dirname($root).'/quiz-attempts-fixture.json'),true,16,JSON_THROW_ON_ERROR);$course=$f['course_id'];$lesson=$f['lesson_id'];$path=dirname($root).'/quiz-review-fixture.json';
if(($argv[2]??'')==='worker'){
 echo "ready\n";fflush(STDOUT);fgets(STDIN);$race=json_decode(file_get_contents(dirname($root).'/quiz-review-race.json'),true,16,JSON_THROW_ON_ERROR);
 try{if(($argv[3]??'')==='cancel')$review->cancel($actor,...$race['command']);else{$u=new \Joomla\CMS\User\User($race['user']);$runtime->mutate($u,'en-GB',$race['enrollment'],$lesson,$race['attempt'],1,reviewKey(),$race['answers'],true);}echo "ok\n";}
 catch(\RuntimeException $error){if($error->getCode()!==409)throw $error;echo "conflict\n";}exit(0);
}
if(($argv[2]??'')==='retained'){
 $t=json_decode(file_get_contents($path),true,16,JSON_THROW_ON_ERROR);$u=new \Joomla\CMS\User\User($t['user_id']);$r=$runtime->state($u,'en-GB',$t['enrollment_id'],$lesson);
 reviewExpect(!$r['course_completed']&&$r['used_attempts']===3,'effective result and attempt consumption survive upgrade');
 $raw=$db->setQuery('SELECT rules_snapshot_json,score_points,passed,submitted_at FROM #__jlms_attempts WHERE id='.(int)$t['passed_attempt'])->loadObject();
 reviewExpect(hash('sha256',json_encode($raw,JSON_THROW_ON_ERROR))===$t['snapshot_hash'],'invalidated original grading evidence survives upgrade');
 reviewExpect((int)$db->setQuery("SELECT COUNT(*) FROM #__action_logs WHERE extension='com_jlms.quizcancel' AND item_id=".(int)$t['passed_attempt'])->loadResult()===1,'cancellation audit survives upgrade');
 echo json_encode(['quiz_review_upgrade_checks'=>$checks,'status'=>'passed']).PHP_EOL;exit(0);
}
$group=(int)$db->setQuery("SELECT id FROM #__usergroups WHERE title='CI learning entitlement'")->loadResult();$user=reviewUser('ciquizreviewlearner',$group);$enrollment=$learning->start($user,'en-GB',$course,bin2hex(random_bytes(16)));
$startKey=reviewKey();$a=$runtime->start($user,'en-GB',$enrollment,$lesson,$startKey);$first=$a['attempt']['id'];$saveKey=reviewKey();$answers=reviewAnswers($a);
$runtime->mutate($user,'en-GB',$enrollment,$lesson,$first,1,$saveKey,$answers,false);
$questionEvidence=$db->setQuery('SELECT public_snapshot_json,grading_snapshot_json,answer_json FROM #__jlms_attempt_questions AS q LEFT JOIN #__jlms_attempt_answers AS a ON a.attempt_question_id=q.id WHERE q.attempt_id='.$first.' ORDER BY q.id')->loadAssocList();
$preview=$review->editor($actor,$course,$lesson,$enrollment,$first,true);$cmd=reviewCommand($preview);
reviewExpect($preview['impact']['used_after']===0&&!$preview['impact']['after_lesson_completed'],'preview shows refund without credit');
reviewReject(fn()=>$review->editor($user,$course,$lesson,$enrollment,$first),403,'learner cannot view cancellation management');
reviewReject(fn()=>$review->cancel($user,...$cmd),403,'learner cannot cancel own attempt');
$bad=$cmd;$bad[10]=false;reviewReject(fn()=>$review->cancel($actor,...$bad),400,'explicit impact confirmation required');
$bad=$cmd;$bad[7]='';reviewReject(fn()=>$review->cancel($actor,...$bad),422,'reason required');
reviewExpect($review->cancel($actor,...$cmd)===$first,'authorized open attempt invalidated');
reviewExpect($review->cancel($actor,...$cmd)===$first,'identical cancellation replay');
$bad=$cmd;$bad[7]='Other reason';reviewReject(fn()=>$review->cancel($actor,...$bad),409,'changed replay body rejected');
$evidence=$db->setQuery('SELECT public_snapshot_json,grading_snapshot_json,answer_json FROM #__jlms_attempt_questions AS q LEFT JOIN #__jlms_attempt_answers AS a ON a.attempt_question_id=q.id WHERE q.attempt_id='.$first.' ORDER BY q.id')->loadAssocList();
reviewExpect($evidence===$questionEvidence,'cancellation preserves public/private snapshots and saved answers');
$r=$runtime->state($user,'en-GB',$enrollment,$lesson);reviewExpect($r['used_attempts']===0&&$r['remaining_attempts']===3&&$r['attempt']['state']==='invalidated'&&!$r['feedback_available']&&$r['attempt']['score_points']===null,'refunded invalidation has no effective score or answer-key release');
reviewExpect((int)$db->setQuery('SELECT COUNT(*) FROM #__jlms_active_attempts WHERE enrollment_id='.$enrollment)->loadResult()===0,'invalidation releases active slot');
reviewReject(fn()=>$runtime->mutate($user,'en-GB',$enrollment,$lesson,$first,1,$saveKey,$answers,false),409,'saved mutation replay cannot write through invalidation');
reviewReject(fn()=>$runtime->mutate($user,'en-GB',$enrollment,$lesson,$first,2,reviewKey(),'{}',true),409,'submit cannot reopen invalidated attempt');
$a=$runtime->start($user,'en-GB',$enrollment,$lesson,reviewKey());$second=$a['attempt']['id'];reviewExpect($a['attempt']['number']===2,'refund never reuses immutable attempt number');
reviewExpect($runtime->start($user,'en-GB',$enrollment,$lesson,$startKey)['attempt']['id']===$first,'old start replay returns old invalidated attempt');
$stale=reviewCommand($review->editor($actor,$course,$lesson,$enrollment,$second,false));$saved=$runtime->mutate($user,'en-GB',$enrollment,$lesson,$second,1,reviewKey(),reviewAnswers($a),false);
reviewReject(fn()=>$review->cancel($actor,...$stale),409,'autosave invalidates stale preview');
$stale=reviewCommand($review->editor($actor,$course,$lesson,$enrollment,$second,false));$passed=$runtime->mutate($user,'en-GB',$enrollment,$lesson,$second,2,reviewKey(),'{}',true);reviewExpect($passed['course_completed'],'saved answers pass before review');
reviewReject(fn()=>$review->cancel($actor,...$stale),409,'submission invalidates open-attempt preview');
$raw=$db->setQuery('SELECT rules_snapshot_json,score_points,passed,submitted_at FROM #__jlms_attempts WHERE id='.$second)->loadObject();$snapshotHash=hash('sha256',json_encode($raw,JSON_THROW_ON_ERROR));
$now+=61;$a=$runtime->start($user,'en-GB',$enrollment,$lesson,reviewKey());$third=$a['attempt']['id'];$runtime->mutate($user,'en-GB',$enrollment,$lesson,$third,1,reviewKey(),reviewAnswers($a),true);
$item=$review->editor($actor,$course,$lesson,$enrollment,$second,false);reviewExpect($item['impact']['after_lesson_completed']&&$item['impact']['after_best']['best_attempt_id']===$third,'preview selects another valid passing attempt');
$review->cancel($actor,...reviewCommand($item));$r=$runtime->state($user,'en-GB',$enrollment,$lesson);reviewExpect($r['course_completed']&&$r['best']['best_attempt_id']===$third&&$r['used_attempts']===2,'invalidating one pass preserves completion through another pass');
$review->cancel($actor,...reviewCommand($review->editor($actor,$course,$lesson,$enrollment,$third,true)));
$r=$runtime->state($user,'en-GB',$enrollment,$lesson);reviewExpect(!$r['course_completed']&&$r['course_percent']===0&&$r['best']===null&&$r['used_attempts']===1,'invalidating final valid pass clears effective completion and best');
$rawAfter=$db->setQuery('SELECT rules_snapshot_json,score_points,passed,submitted_at FROM #__jlms_attempts WHERE id='.$second)->loadObject();reviewExpect(hash('sha256',json_encode($rawAfter,JSON_THROW_ON_ERROR))===$snapshotHash,'raw closed attempt score and submission remain unchanged');
$old=$runtime->state($user,'en-GB',$enrollment,$lesson,$second);reviewExpect($old['attempt']['passed']&&!$old['attempt']['valid']&&!$old['feedback_available'],'historical pass is explicitly invalid and never releases correction');
$a=$runtime->start($user,'en-GB',$enrollment,$lesson,reviewKey());$fourth=$a['attempt']['id'];reviewExpect($a['attempt']['number']===4,'number remains monotonic after multiple refunds');
// Audit failure must leave attempt, slot and projections unchanged.
$broken=new \Jlms\Component\Jlms\Administrator\Application\QuizReviewService($db,$component->getQuizTimeService(),static function():void{throw new \RuntimeException('COM_JLMS_QUIZ_REVIEW_AUDIT_FAILED',503);});
$item=$review->editor($actor,$course,$lesson,$enrollment,$fourth,false);$failure=reviewCommand($item);reviewReject(fn()=>$broken->cancel($actor,...$failure),503,'missing audit rolls cancellation back');
reviewExpect($runtime->state($user,'en-GB',$enrollment,$lesson)['attempt']['state']==='open','failed cancellation preserves active attempt');
$bad=$failure;$bad[9]=str_repeat('0',64);reviewReject(fn()=>$review->cancel($actor,...$bad),409,'forged impact hash rejected');
// Certificates require a dedicated review; no root/document may be silently modified.
$nowSql=gmdate('Y-m-d H:i:s');$template=(object)['title'=>'CI review guard','version_no'=>1,'template_json'=>'{}','hash'=>str_repeat('1',64),'state'=>1,'created_at'=>$nowSql,'modified_at'=>$nowSql];$db->insertObject('#__jlms_certificate_templates',$template,'id');
$version=(int)$db->setQuery('SELECT course_version_id FROM #__jlms_enrollments WHERE id='.$enrollment)->loadResult();
$certificate=(object)['enrollment_id'=>$enrollment,'course_version_id'=>$version,'template_id'=>(int)$template->id,'issued_at'=>$nowSql];$db->insertObject('#__jlms_certificates',$certificate,'id');
$item=$review->editor($actor,$course,$lesson,$enrollment,$fourth,false);reviewExpect($item['impact']['blocked']&&$item['impact']['certificate_count']===1,'certificate impact blocks destructive confirmation');
reviewReject(fn()=>$review->cancel($actor,...reviewCommand($item)),409,'direct cancellation cannot bypass certificate review');
$db->setQuery('DELETE FROM #__jlms_certificates WHERE id='.(int)$certificate->id)->execute();$db->setQuery('DELETE FROM #__jlms_certificate_templates WHERE id='.(int)$template->id)->execute();
$job=(object)['kind'=>'ci_review_guard','scope_json'=>'{}','payload_json'=>'{}','available_at'=>$nowSql,'created_at'=>$nowSql];$db->insertObject('#__jlms_jobs',$job,'id');
$correction=(object)['attempt_id'=>$second,'revision_no'=>1,'job_id'=>(int)$job->id,'reason'=>'CI review guard','rules_snapshot_json'=>'{}','previous_score_points'=>5,'previous_passed'=>1,'result_score_points'=>0,'result_passed'=>0,'created_at'=>$nowSql];$db->insertObject('#__jlms_assessment_revisions',$correction,'id');
$item=$review->editor($actor,$course,$lesson,$enrollment,$fourth,false);reviewExpect($item['impact']['blocked']&&$item['impact']['correction_count']===1,'prior administrative grades require their dedicated review workflow');
reviewReject(fn()=>$review->cancel($actor,...reviewCommand($item)),409,'corrected grading is never silently ignored on apply');
$db->setQuery('DELETE FROM #__jlms_assessment_revisions WHERE id='.(int)$correction->id)->execute();$db->setQuery('DELETE FROM #__jlms_jobs WHERE id='.(int)$job->id)->execute();
$review->cancel($actor,...reviewCommand($review->editor($actor,$course,$lesson,$enrollment,$fourth,false)));$a=$runtime->start($user,'en-GB',$enrollment,$lesson,reviewKey());$fifth=$a['attempt']['id'];
reviewExpect($a['attempt']['number']===5,'all historical numbers remain reserved');$review->cancel($actor,...reviewCommand($review->editor($actor,$course,$lesson,$enrollment,$fifth,false)));
reviewReject(fn()=>$runtime->start($user,'en-GB',$enrollment,$lesson,reviewKey()),409,'non-refunded invalidations consume finite quota');
$practice=$runtime->start($user,'en-GB',$enrollment,$f['practice_id'],reviewKey());$runtime->mutate($user,'en-GB',$enrollment,$f['practice_id'],$practice['attempt']['id'],1,reviewKey(),'{}',true);
$item=$review->editor($actor,$course,$f['practice_id'],$enrollment,$practice['attempt']['id'],true);reviewExpect($item['impact']['before_lesson_completed']&&!$item['impact']['after_lesson_completed'],'practice completion is recalculated too');$review->cancel($actor,...reviewCommand($item));
reviewExpect(!$runtime->state($user,'en-GB',$enrollment,$f['practice_id'])['course_completed'],'optional cancellation never invents required credit');
// Current native permissions apply even to an already successful replay.
$authorId=(int)$db->setQuery("SELECT id FROM #__users WHERE username='ciquizauthor'")->loadResult();$author=new \Joomla\CMS\User\User($authorId);
reviewReject(fn()=>$review->editor($author,$course,$lesson,$enrollment,$first),403,'quiz author role alone cannot invalidate attempts');
$managerGroup=(int)$db->setQuery("SELECT id FROM #__usergroups WHERE title='CI assessment time manager'")->loadResult();
$managerId=(int)$db->setQuery("SELECT id FROM #__users WHERE username='ciquiztimemanager'")->loadResult();
\Joomla\CMS\User\UserHelper::addUserToGroup($managerId,$managerGroup);$manager=new \Joomla\CMS\User\User($managerId);
$pa=$runtime->start($user,'en-GB',$enrollment,$f['practice_id'],reviewKey());$managed=reviewCommand($review->editor($manager,$course,$f['practice_id'],$enrollment,$pa['attempt']['id'],true));
$asset=new \Joomla\CMS\Table\Asset($db);$asset->load((int)$db->setQuery('SELECT asset_id FROM #__jlms_courses WHERE id='.$course)->loadResult());$oldRules=$asset->rules;
$asset->rules=json_encode(['jlms.assessment.correct'=>[(string)$managerGroup=>false]],JSON_THROW_ON_ERROR);$asset->store();
reviewReject(fn()=>$review->editor($manager,$course,$f['practice_id'],$enrollment,$pa['attempt']['id']),403,'course denial overrides global assessment permissions');
reviewReject(fn()=>$review->cancel($manager,...$managed),403,'direct cancellation respects course denial');$asset->rules=$oldRules;$asset->store();
\Joomla\CMS\User\UserHelper::removeUserFromGroup((int)$user->id,$group);$review->cancel($manager,...$managed);
reviewReject(fn()=>$runtime->state($user,'en-GB',$enrollment,$f['practice_id']),403,'administrative cancellation never grants learner access');
\Joomla\CMS\User\UserHelper::addUserToGroup((int)$user->id,$group);
\Joomla\CMS\User\UserHelper::removeUserFromGroup($managerId,$managerGroup);
reviewReject(fn()=>$review->cancel($manager,...$managed),403,'successful replay reloads revoked manager permissions');
// Prepare a learner-owned native HTTP target, without changing the previous quiz HTTP fixture's required result.
$now=time();$http=new \Joomla\CMS\User\User((int)$db->setQuery("SELECT id FROM #__users WHERE username='cilearnerhttp'")->loadResult());$httpA=$runtime->start($http,'en-GB',$f['http_enrollment'],$f['never_id'],reviewKey());
// Two processes queue on one enrollment: cancellation and submission have one effective winner.
$raceUser=reviewUser('ciquizreviewrace',$group);$raceEnrollment=$learning->start($raceUser,'en-GB',$course,bin2hex(random_bytes(16)));$raceA=$runtime->start($raceUser,'en-GB',$raceEnrollment,$lesson,reviewKey());
$race=['user'=>(int)$raceUser->id,'enrollment'=>$raceEnrollment,'attempt'=>$raceA['attempt']['id'],'answers'=>reviewAnswers($raceA),'command'=>reviewCommand($review->editor($actor,$course,$lesson,$raceEnrollment,$raceA['attempt']['id'],false))];
file_put_contents(dirname($root).'/quiz-review-race.json',json_encode($race,JSON_THROW_ON_ERROR));$workers=[];
foreach(['cancel','submit']as$mode){$pipes=[];$process=proc_open([PHP_BINARY,__FILE__,$root,'worker',$mode],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($process))throw new \RuntimeException('Worker failed');$workers[]=[$process,$pipes];}
foreach($workers as[$process,$pipes])if(trim((string)fgets($pipes[1]))!=='ready')throw new \RuntimeException('Worker barrier failed');
$db->transactionStart();$db->setQuery('SELECT id FROM #__jlms_enrollments WHERE id='.$raceEnrollment.' FOR UPDATE')->loadResult();foreach($workers as[$process,$pipes]){fwrite($pipes[0],"go\n");fclose($pipes[0]);}usleep(200000);$db->transactionCommit();
$results=[];foreach($workers as[$process,$pipes]){$out=trim(stream_get_contents($pipes[1]));$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($process)!==0)throw new \RuntimeException($error.$out);$results[]=$out;}
sort($results);reviewExpect($results===['conflict','ok'],'concurrent submit and cancellation have exactly one winner');
$raceState=$db->setQuery('SELECT status FROM #__jlms_attempts WHERE id='.(int)$raceA['attempt']['id'])->loadResult();$raceCount=(int)$db->setQuery('SELECT completed_total FROM #__jlms_enrollments WHERE id='.$raceEnrollment)->loadResult();
reviewExpect(($raceState==='invalidated'&&$raceCount===0)||($raceState==='submitted'&&$raceCount===1),'race leaves consistent effective completion');
$log=json_decode($db->setQuery("SELECT message FROM #__action_logs WHERE extension='com_jlms.quizcancel' AND item_id=".$second)->loadResult(),true,16,JSON_THROW_ON_ERROR);
reviewExpect($log['before_status']==='submitted'&&$log['after_status']==='invalidated'&&!str_contains(json_encode($log,JSON_THROW_ON_ERROR),'PRIVATE_CANCEL_REASON'),'native audit preserves minimal transition metadata, not free-text reason');
file_put_contents($path,json_encode(['course_id'=>$course,'lesson_id'=>$lesson,'user_id'=>(int)$user->id,'enrollment_id'=>$enrollment,'passed_attempt'=>$second,'first_attempt'=>$first,'snapshot_hash'=>$snapshotHash,'http_enrollment'=>$f['http_enrollment'],'http_lesson'=>$f['never_id'],'http_attempt'=>$httpA['attempt']['id']],JSON_THROW_ON_ERROR));
echo json_encode(['quiz_review_checks'=>$checks,'status'=>'passed']).PHP_EOL;
