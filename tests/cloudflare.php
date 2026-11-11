<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/cloudflare-fixture.php';
use Jlms\Plugin\Jlmsvideo\Cloudflarestream\Provider\Provider;
use Jlms\Contracts\Video\VideoSource;
use Jlms\Contracts\Video\PlaybackContext;
use Jlms\Contracts\Video\MetadataContext;

$checks = 0;
function cfExpect(bool $value, string $message): void { global $checks; if (!$value) { throw new \RuntimeException($message); } $checks++; }
function cfDenied(callable $work, string $key): void {
    try { $work(); } catch (\RuntimeException $error) { cfExpect($error->getMessage() === $key, $key . ': received ' . $error->getMessage()); return; }
    throw new \RuntimeException('Expected: ' . $key);
}
[$config, $secret, $transport, $state] = cloudflareFixture();
$p = new Provider($config, $secret, $transport);
$source = new VideoSource('cloudflarestream', str_repeat('b',32), 1);
$now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
$context = new PlaybackContext(1,2,3,'it-IT',0,'grant',$now);
$metadataContext = new MetadataContext(1,'it-IT',true);
cfExpect(!(new Provider())->getDescriptor()->configured,'provider closed by default');
cfExpect($p->getDescriptor()->configured && count($state->calls) === 0,'configuration check performs no HTTP');
cfExpect((new Provider(array_replace($config,['signing_mode'=>'local_key']),$secret,$transport))->getDescriptor()->configured,'legacy local signing mode is ignored');
foreach (['account_id'=>'evil/../account','playback_domain'=>'attacker.example','site_host'=>'https://school.example','token_ttl_seconds'=>10] as $field=>$value) {
    cfExpect(!(new Provider(array_replace($config,[$field=>$value]),$secret,$transport))->getDescriptor()->configured,'bad config closed: '.$field);
}
$secrets = new \Jlms\Contracts\Security\EnvironmentSecretProvider();
cfDenied(fn()=>$secrets->resolve('raw-api-secret'),'COM_JLMS_VIDEO_CONFIGURATION');
cfDenied(fn()=>$secrets->resolve('env:PATH'),'COM_JLMS_VIDEO_CONFIGURATION');
cfDenied(fn()=>$secrets->resolve('env:JLMS_SECRET_ABSENT_FIXTURE'),'COM_JLMS_VIDEO_CONFIGURATION');
putenv('JLMS_SECRET_CI_FIXTURE=ci-disposable-value');cfExpect($secrets->resolve('env:JLMS_SECRET_CI_FIXTURE')==='ci-disposable-value','environment reference resolved');putenv('JLMS_SECRET_CI_FIXTURE');
$state->protected=false;cfDenied(fn()=>$p->createPlaybackDescriptor($source,$context),'COM_JLMS_VIDEO_REMOTE_UNPROTECTED');
cfExpect(!in_array('POST',array_column($state->calls,'method'),true),'unprotected video never receives a signing request');$state->protected=true;
$state->ready=false;cfDenied(fn()=>$p->fetchMetadata($source,$metadataContext),'COM_JLMS_VIDEO_REMOTE_NOT_READY');$state->ready=true;
$state->origins=['*'];cfDenied(fn()=>$p->fetchMetadata($source,$metadataContext),'COM_JLMS_VIDEO_ORIGIN_REJECTED');$state->origins=['school.example'];
foreach ([401=>'COM_JLMS_VIDEO_CREDENTIAL_REJECTED',404=>'COM_JLMS_VIDEO_REMOTE_NOT_FOUND',429=>'COM_JLMS_VIDEO_PROVIDER_BUSY',302=>'COM_JLMS_VIDEO_PROVIDER_UNAVAILABLE',500=>'COM_JLMS_VIDEO_PROVIDER_UNAVAILABLE'] as $status=>$key) {
    $state->status=$status;cfDenied(fn()=>$p->fetchMetadata($source,$metadataContext),$key);
}
$state->status=200;$grant=$p->createPlaybackDescriptor($source,$context);
cfExpect($grant->expiresAt->getTimestamp()===$now->getTimestamp()+60 && $grant->playerOptions['renew_after_seconds']===40,'bounded lifetime and early renewal');
cfExpect(!str_contains(json_encode($grant,JSON_THROW_ON_ERROR),'ci-disposable-not-a-real-secret'),'server secret absent from browser descriptor');
$request=end($state->calls);cfExpect($request['url']==='https://api.cloudflare.com/client/v4/accounts/'.str_repeat('a',32).'/stream/'.str_repeat('b',32).'/token','fixed API account/video path');
cfExpect(json_decode($request['body'],true)['downloadable']===false,'downloads disabled');
foreach (['none','foreign','long','download'] as $mode) { $state->tokenMode=$mode;cfDenied(fn()=>$p->createPlaybackDescriptor($source,$context),'COM_JLMS_VIDEO_PROVIDER_UNAVAILABLE'); }
$state->tokenMode='valid';$deadline=$now->modify('+25 seconds');
$grant=$p->createPlaybackDescriptor($source,new PlaybackContext(1,2,3,'it-IT',0,'grant',$now,$deadline));
cfExpect($grant->expiresAt->getTimestamp()===$deadline->getTimestamp(),'access window clamps token expiry');
echo json_encode(['cloudflare_checks'=>$checks,'status'=>'passed','transport'=>'injected fixture; no live provider request'],JSON_THROW_ON_ERROR).PHP_EOL;
